#!/usr/bin/env python3
"""Extrae texto de la primera página de un PDF usando PaddleOCR.

Este script se ejecuta desde PHP y devuelve un JSON en stdout con el
texto agregado y cada línea detectada.
"""
import argparse
import json
import os
import sys
from typing import Any, Dict, List

try:
    from pdf2image import convert_from_path
    from paddleocr import PaddleOCR
    import numpy as np
except Exception as exc:  # pragma: no cover
    print(json.dumps({
        "success": False,
        "error": "No se pudieron importar las dependencias de Python",
        "details": repr(exc)
    }, ensure_ascii=False))
    sys.exit(1)


def build_response(success: bool, **kwargs: Any) -> str:
    payload: Dict[str, Any] = {"success": success}
    payload.update(kwargs)
    return json.dumps(payload, ensure_ascii=False)


def main() -> None:
    parser = argparse.ArgumentParser(description="OCR para expedientes usando PaddleOCR")
    parser.add_argument("--pdf", required=True, help="Ruta absoluta del PDF a procesar")
    parser.add_argument("--poppler", default=os.environ.get("POPPLER_PATH"),
                        help="Ruta al binario de Poppler para Windows (opcional)")
    parser.add_argument("--lang", default="es", help="Código de idioma para PaddleOCR")
    args = parser.parse_args()

    pdf_path = args.pdf
    if not os.path.isfile(pdf_path):
        print(build_response(False, error="El archivo PDF no existe", path=pdf_path))
        sys.exit(1)

    try:
        convert_kwargs: Dict[str, Any] = {
            "dpi": 300,
            "first_page": 1,
            "last_page": 1,
        }
        if args.poppler:
            convert_kwargs["poppler_path"] = args.poppler
        images = convert_from_path(pdf_path, **convert_kwargs)
        if not images:
            print(build_response(False, error="No se pudo renderizar la página 1 del PDF"))
            sys.exit(1)
        page_image = images[0]
    except Exception as exc:
        print(build_response(False, error="Error convirtiendo PDF a imagen", details=repr(exc)))
        sys.exit(1)

    try:
        ocr = PaddleOCR(use_angle_cls=True, lang=args.lang, show_log=False)
        result = ocr.ocr(np.array(page_image), cls=True)
    except Exception as exc:
        print(build_response(False, error="Error ejecutando PaddleOCR", details=repr(exc)))
        sys.exit(1)

    lines: List[Dict[str, Any]] = []
    aggregated: List[str] = []

    for page in result:
        if not page:
            continue
        for entry in page:
            text = entry[1][0]
            score = float(entry[1][1])
            lines.append({"text": text, "confidence": score})
            aggregated.append(text.strip())

    print(build_response(True, text="\n".join(aggregated).strip(), lines=lines))


if __name__ == "__main__":
    main()
