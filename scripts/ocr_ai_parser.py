#!/usr/bin/env python3
"""OCR + IA local para extraer campos estructurados de certificados PDF."""
from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path
from typing import Any, Dict, List

try:
    from pdf2image import convert_from_path
    from paddleocr import PaddleOCR
    import numpy as np
    import requests
    from PIL import ImageEnhance, ImageOps
except Exception as exc:  # pragma: no cover
    print(json.dumps({
        "success": False,
        "error": "No se pudieron importar las dependencias de Python",
        "details": repr(exc)
    }, ensure_ascii=True))
    sys.exit(1)

FIELD_KEYS = [
    "rut",
    "nombre_completo",
    "anio_egreso",
    "titulo",
    "numero_certificado",
    "fecha_entrega",
]


def build_response(success: bool, **kwargs: Any) -> str:
    payload: Dict[str, Any] = {"success": success}
    payload.update(kwargs)
    return json.dumps(payload, ensure_ascii=True)


def ensure_pdf_exists(pdf_path: str) -> None:
    if not os.path.isfile(pdf_path):
        print(build_response(False, error="El archivo PDF no existe", path=pdf_path))
        sys.exit(1)


def render_first_page_only(pdf_path: str, poppler_path: str | None) -> List[Any]:
    convert_kwargs: Dict[str, Any] = {"dpi": 300, "first_page": 1, "last_page": 1}
    if poppler_path:
        convert_kwargs["poppler_path"] = poppler_path

    images = convert_from_path(pdf_path, **convert_kwargs)
    if not images:
        raise RuntimeError("No se pudo renderizar el PDF")
    return images


def run_tesseract_cli(image: Any, *, lang: str = "eng", psm: str = "6", extra_args: List[str] | None = None) -> str:
    tmp_img = tempfile.NamedTemporaryFile(suffix=".png", delete=False)
    tmp_path = Path(tmp_img.name)
    tmp_img.close()
    txt_path = None
    try:
        image.save(tmp_path, format="PNG")
        output_base = str(tmp_path)
        txt_path = Path(output_base + ".txt")
        cmd = ["tesseract", output_base, output_base, "-l", lang, "--psm", psm]
        if extra_args:
            cmd.extend(extra_args)
        try:
            subprocess.run(cmd, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        except Exception:
            return ""
        if txt_path.exists():
            return txt_path.read_text(encoding="utf-8", errors="ignore")
        return ""
    finally:
        try:
            tmp_path.unlink(missing_ok=True)
        except TypeError:
            if os.path.exists(tmp_path):
                os.remove(tmp_path)
        if txt_path:
            try:
                txt_path.unlink(missing_ok=True)
            except TypeError:
                if txt_path.exists():
                    txt_path.unlink()


def collect_handwritten_snippets(images: List[Any]) -> str:
    fragments: List[str] = []
    for image in images:
        width, height = image.size

        regions = [
            (0, int(height * 0.55), width, height),  # parte inferior (firmas)
            (int(width * 0.5), 0, width, int(height * 0.35)),  # esquina superior derecha (anotaciones)
            (0, 0, width, int(height * 0.25)),  # margen superior completo
            (0, 0, int(width * 0.45), int(height * 0.3)),  # margen superior izquierdo (números manuscritos)
        ]

        for box in regions:
            cropped = image.crop(box)
            grayscale = ImageOps.autocontrast(cropped.convert("L"))
            equalized = ImageOps.equalize(grayscale)
            enhancer = ImageEnhance.Contrast(grayscale)
            high_contrast = enhancer.enhance(3.0)
            threshold = high_contrast.point(lambda p: 255 if p > 135 else 0)
            inverted = ImageOps.invert(threshold)

            candidate_images = [grayscale, equalized, high_contrast, threshold, inverted]
            enriched_candidates: List[Any] = []
            for candidate in candidate_images:
                enriched_candidates.append(candidate)
                if candidate.width > 0 and candidate.height > 0:
                    scale = 1.8
                    enlarged = candidate.resize(
                        (max(1, int(candidate.width * scale)), max(1, int(candidate.height * scale))),
                        resample=getattr(ImageOps, "Resampling", ImageOps).__dict__.get("LANCZOS", 1)
                        if hasattr(ImageOps, "Resampling") else 1,
                    )
                    enriched_candidates.append(enlarged)

            for candidate_image in enriched_candidates:
                for psm in ("6", "11"):
                    text_general = run_tesseract_cli(candidate_image, psm=psm)
                    if text_general:
                        fragments.append(text_general)

                for psm in ("7", "13"):
                    text_digits = run_tesseract_cli(
                        candidate_image,
                        psm=psm,
                        extra_args=["-c", "tessedit_char_whitelist=0123456789-"],
                    )
                    if text_digits:
                        fragments.append(text_digits)
    return "\n".join(fragments).strip()


def run_paddle_ocr(images: List[Any], lang: str) -> Dict[str, Any]:
    ocr = PaddleOCR(use_angle_cls=True, lang=lang, show_log=False)
    lines: List[Dict[str, Any]] = []
    aggregated: List[str] = []

    for image in images:
        result = ocr.ocr(np.array(image), cls=True)
        for page in result or []:
            if not page:
                continue
            for entry in page:
                text = entry[1][0]
                score = float(entry[1][1])
                lines.append({"text": text, "confidence": score})
                aggregated.append(text.strip())

    return {"text": "\n".join(aggregated).strip(), "lines": lines}


def call_ollama(prompt: str, model: str, endpoint: str) -> str:
    payload = {"model": model, "prompt": prompt, "stream": False}
    response = requests.post(endpoint.rstrip("/") + "/api/generate", json=payload, timeout=300)
    response.raise_for_status()
    data = response.json()
    if "response" not in data:
        raise RuntimeError(f"Respuesta de Ollama inesperada: {data}")
    return str(data["response"]).strip()


def extract_json_block(text: str) -> Dict[str, Any]:
    # Intenta ubicar el primer bloque JSON en la respuesta
    json_regex = re.compile(r"\{.*\}", re.DOTALL)
    match = json_regex.search(text)
    candidate = match.group(0) if match else text
    return json.loads(candidate)


def normalize_fields(data: Dict[str, Any]) -> Dict[str, str]:
    normalized: Dict[str, str] = {}
    for key in FIELD_KEYS:
        value = data.get(key, "") if isinstance(data, dict) else ""
        if value is None:
            value = ""
        normalized[key] = str(value).strip()
    return normalized


def try_parse_iso_date(value: str) -> str:
    if not value:
        return ""
    cleaned = value.replace(".", "/").replace("-", "/")
    parts = [p for p in cleaned.split("/") if p]
    if len(parts) != 3:
        return ""
    day, month, year = parts
    if len(year) == 2:
        year = ("20" if int(year) < 50 else "19") + year
    try:
        day_i = int(day)
        month_i = int(month)
        year_i = int(year)
    except ValueError:
        return ""
    return f"{year_i:04d}-{month_i:02d}-{day_i:02d}"


def normalize_handwriting_text(text: str) -> str:
    if not text:
        return ""

    replacements = str.maketrans({
        "I": "1",
        "l": "1",
        "|": "1",
        "!": "1",
        "O": "0",
        "o": "0",
        "S": "5",
        "s": "5",
        "B": "8",
        "b": "8",
        "Z": "2",
        "z": "2",
        "–": "-",
        "—": "-",
    })

    cleaned = text.translate(replacements)
    cleaned = re.sub(r"[^0-9A-Za-z\-\/\s]", " ", cleaned)
    return re.sub(r"\s+", " ", cleaned)


def extract_handwritten_meta(text: str) -> Dict[str, str]:
    if not text:
        return {}

    meta: Dict[str, str] = {}
    normalized_text = normalize_handwriting_text(text)

    number_patterns = [
        re.compile(r"\b(\d{1,2})\s*[-]\s*(\d{3,4})\b"),
        re.compile(r"numero\s*(?:certificado|doc(?:umento)?)?[:\-\s]*?(\d{1,2})\s*[-]\s*(\d{3,4})", re.IGNORECASE),
    ]

    for pattern in number_patterns:
        num_match = pattern.search(normalized_text)
        if num_match:
            prefix = int(num_match.group(1))
            suffix = int(num_match.group(2))
            meta["numero_certificado"] = f"{prefix:02d}-{suffix:03d}"
            break

    date_match = re.search(r"\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})\b", normalized_text)
    if date_match:
        iso_date = try_parse_iso_date("/".join(date_match.groups()))
        if iso_date:
            meta["fecha_entrega"] = iso_date

    return meta


def sanitize_text(text: str) -> str:
    replacements = {
        "Ã¡": "á", "Ã©": "é", "Ã­": "í", "Ã³": "ó", "Ãº": "ú",
        "Ã": "Á", "Ã‰": "É", "Ã": "Í", "Ã“": "Ó", "Ãš": "Ú",
        "Ã±": "ñ", "Ã‘": "Ñ", "Â": "",
    }
    fixed = text
    for bad, good in replacements.items():
        fixed = fixed.replace(bad, good)
    return fixed


def build_prompt(extracted_text: str) -> str:
    cleaned_text = sanitize_text(extracted_text)
    return (
        "Eres un analista que recibe el texto completo de un certificado de práctica profesional.\n"
        "Debes responder **únicamente** con un JSON válido (sin comentarios o texto adicional) que incluya exactamente estas claves:\n"
        "rut, nombre_completo, anio_egreso, titulo, numero_certificado, fecha_entrega.\n"
        "Reglas obligatorias:\n"
        "1. nombre_completo debe conservar todos los nombres y apellidos separados por espacios.\n"
        "2. titulo debe copiar literalmente el título indicado en el certificado (por ejemplo 'Técnico de nivel medio en Administración'). No devuelvas abreviaturas ni una sola letra.\n"
        "3. numero_certificado debe tener el formato NN-NNN (rellena con ceros a la izquierda si es necesario).\n"
        "4. fecha_entrega es obligatoria cuando se mencione una fecha manuscrita (esquina superior) o la frase 'Se emite la presente Acta con fecha ...'. Convierte siempre al formato DD-MM-AAAA.\n"
        "5. Si un dato verdaderamente no existe en el certificado, usa cadena vacía, pero nunca inventes información.\n"
        "6. No incluyas texto adicional fuera del JSON.\n"
        "Texto del certificado (primera página):\n" + cleaned_text
    )


def main() -> None:
    parser = argparse.ArgumentParser(description="OCR + IA local para expedientes")
    parser.add_argument("--pdf", required=True, help="Ruta absoluta del PDF a procesar")
    parser.add_argument("--poppler", default=os.environ.get("POPPLER_PATH"), help="Ruta a Poppler")
    parser.add_argument("--lang", default="es", help="Código de idioma para PaddleOCR")
    parser.add_argument("--ollama-endpoint", default="http://localhost:11434", help="Endpoint de Ollama")
    parser.add_argument("--ollama-model", default="deepseek-coder-v2", help="Nombre del modelo en Ollama")
    args = parser.parse_args()

    ensure_pdf_exists(args.pdf)

    try:
        page_images = render_first_page_only(args.pdf, args.poppler)
        ocr_payload = run_paddle_ocr(page_images, args.lang)
        handwriting_extra = collect_handwritten_snippets(page_images)
    except Exception as exc:  # pragma: no cover
        print(build_response(False, error="OCR falló", details=repr(exc)))
        sys.exit(1)

    if not ocr_payload.get("text"):
        print(build_response(False, error="OCR no devolvió texto utilizable"))
        sys.exit(1)

    debug_handwriting_file = os.environ.get("OCR_DEBUG_HANDWRITING_FILE")
    if debug_handwriting_file:
        try:
            Path(debug_handwriting_file).write_text(handwriting_extra or "", encoding="utf-8")
        except Exception:
            pass

    if handwriting_extra:
        combined_text = (ocr_payload["text"] + "\n" + handwriting_extra).strip()
        ocr_payload["text"] = combined_text

    prompt = build_prompt(ocr_payload["text"])

    try:
        ollama_raw = call_ollama(prompt, args.ollama_model, args.ollama_endpoint)
        parsed = extract_json_block(ollama_raw)
        fields = normalize_fields(parsed)
        handwritten = extract_handwritten_meta(ocr_payload["text"])
        for key, value in handwritten.items():
            if not value:
                continue
            current = fields.get(key, "")
            placeholder = {"", "00-000", "00-0000", "0-000"}
            if not current or current in placeholder:
                fields[key] = value
    except Exception as exc:  # pragma: no cover
        print(build_response(False, error="IA local falló", details=repr(exc), raw_response=ollama_raw if 'ollama_raw' in locals() else None))
        sys.exit(1)

    print(build_response(True, text=ocr_payload["text"], lines=ocr_payload["lines"], fields=fields, model=args.ollama_model))


if __name__ == "__main__":
    main()
