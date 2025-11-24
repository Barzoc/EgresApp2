<?php

use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;

/**
 * Clase de utilidad para OCR de PDFs
 * Se integra con la estructura existente sin modificarla
 */
class PDFProcessor {
    
    /**
     * Extrae texto de un PDF usando OCR (Tesseract + ImageMagick)
     * @param string $pdfPath Ruta al archivo PDF
     * @return string Texto extraído del PDF
     */
    public static function extractTextFromPDF($pdfPath) {
        $config = self::loadPdfConfig();
        $enablePaddle = ($config['enable_paddle_ocr'] ?? true) !== false;

        // 1) Spatie\PdfToText (pdftotext)
        $spatieText = self::extractTextWithSpatie($pdfPath);
        if (self::isTextLegible($spatieText)) {
            return trim($spatieText);
        }

        // 2) Smalot\PdfParser (capa textual)
        $parserText = self::extractTextWithPdfParser($pdfPath);
        if (self::isTextLegible($parserText)) {
            return trim($parserText);
        }

        // 3) PaddleOCR vía Python (solo si está habilitado)
        if ($enablePaddle) {
            $paddleText = self::extractTextWithPaddle($pdfPath);
            if (self::isTextLegible($paddleText)) {
                return trim($paddleText);
            }
        }

        // 4) Fallback OCR
        try {
            if (!self::isTesseractAvailable()) {
                throw new Exception('Tesseract OCR no está instalado');
            }

            if (!self::isImageMagickAvailable()) {
                throw new Exception('ImageMagick no está instalado');
            }

            $images = self::convertPDFToImages($pdfPath);
            $fullText = '';

            foreach ($images as $imagePath) {
                $text = self::extractTextFromImage($imagePath);
                $fullText .= $text . "\n";
                unlink($imagePath);
            }

            if (self::isTextLegible($fullText)) {
                return trim($fullText);
            }

        } catch (Exception $e) {
            error_log('PDFProcessor Error: ' . $e->getMessage());
        }

        return '';
    }

    private static function extractDataWithLocalAI($pdfPath) {
        $config = self::loadPdfConfig();
        if (($config['enable_ai_parser'] ?? true) !== true) {
            return ['success' => false, 'command' => null, 'output' => null];
        }
        $python = $config['python_path'] ?? null;
        $script = $config['ocr_ai_script_path'] ?? (__DIR__ . '/../scripts/ocr_ai_parser.py');

        if (empty($python) || empty($script) || !is_file($python) || !is_file($script)) {
            return ['success' => false, 'command' => null, 'output' => null];
        }

        $arguments = [
            '--pdf ' . self::wrapShellArg($pdfPath)
        ];

        if (!empty($config['poppler_path'])) {
            $arguments[] = '--poppler ' . self::wrapShellArg($config['poppler_path']);
        }

        if (!empty($config['ollama_endpoint'])) {
            $arguments[] = '--ollama-endpoint ' . self::wrapShellArg($config['ollama_endpoint']);
        }

        if (!empty($config['ollama_model'])) {
            $arguments[] = '--ollama-model ' . self::wrapShellArg($config['ollama_model']);
        }

        $command = sprintf(
            '%s %s %s 2>&1',
            self::wrapShellArg($python),
            self::wrapShellArg($script),
            implode(' ', $arguments)
        );

        $output = shell_exec($command);
        if (empty($output)) {
            return ['success' => false, 'command' => $command, 'output' => ''];
        }

        $decoded = self::decodeJsonResponse($output);
        if (!is_array($decoded) || empty($decoded['success'])) {
            error_log('PDFProcessor AI OCR Error: ' . trim($output));
            return ['success' => false, 'raw_ai' => $output, 'command' => $command, 'output' => $output];
        }

        return [
            'success' => true,
            'text' => $decoded['text'] ?? '',
            'lines' => $decoded['lines'] ?? [],
            'fields' => $decoded['fields'] ?? [],
            'raw_ai' => $decoded,
            'command' => $command,
            'output' => $output
        ];
    }

    private static function mergeStructuredFields(array $parsed, $aiFields) {
        $result = [];
        $aiArray = is_array($aiFields) ? $aiFields : [];

        if (!empty($aiArray)) {
            $result = self::mapAiFields($aiArray);
            $result['ai_fields'] = $aiArray;
        }

        foreach ($parsed as $key => $value) {
            if ($key === 'ai_fields') {
                continue;
            }
            if (!isset($result[$key]) || $result[$key] === null || $result[$key] === '') {
                $result[$key] = $value;
            }
        }

        if (!empty($result['anio_egreso']) && empty($result['fecha_egreso'])) {
            $result['fecha_egreso'] = $result['anio_egreso'] . '-01-01';
        }

        return $result;
    }

    private static function mergeParsedSources(array $base, array $fromAi): array {
        if (empty($base)) {
            return $fromAi;
        }

        if (empty($fromAi)) {
            return $base;
        }

        $combined = $base;
        foreach ($fromAi as $key => $value) {
            if (!isset($combined[$key]) || $combined[$key] === null || $combined[$key] === '') {
                $combined[$key] = $value;
            }
        }

        return $combined;
    }

    private static function mapAiFields($aiFields) {
        $result = [];

        $aliasMap = [
            'rut' => 'rut',
            'nombre_completo' => 'nombre',
            'anio_egreso' => 'anio_egreso',
            'titulo' => 'titulo',
            'especialidad' => 'titulo',
            'numero_certificado' => 'numero_certificado',
            'fecha_entrega' => 'fecha_entrega',
            'centro_practica' => 'centro_practica',
            'nota_empresa' => 'nota_empresa',
            'nota_plan' => 'nota_plan',
            'horas_totales' => 'horas_totales',
            'profesor' => 'profesor',
            'rut_profesor' => 'rut_profesor',
        ];

        foreach ($aliasMap as $source => $target) {
            if (!array_key_exists($source, $aiFields)) {
                continue;
            }
            $value = is_string($aiFields[$source]) ? trim($aiFields[$source]) : $aiFields[$source];
            if ($value === '' || $value === null) {
                continue;
            }
            switch ($target) {
                case 'rut':
                    $result[$target] = self::normalizeRut($value) ?? $value;
                    break;
                case 'nombre':
                    $result[$target] = self::normalizeWhitespace(mb_strtoupper($value, 'UTF-8'));
                    break;
                case 'titulo':
                    $result[$target] = self::normalizeWhitespace($value);
                    break;
                case 'numero_certificado':
                    $result[$target] = self::formatCertificateNumber($value);
                    break;
                case 'fecha_entrega':
                    $result[$target] = self::normalizeDate($value) ?? $value;
                    break;
                case 'anio_egreso':
                    $result[$target] = preg_replace('/[^0-9]/', '', $value);
                    break;
                default:
                    $result[$target] = $value;
            }
        }

        foreach ($aiFields as $key => $value) {
            if (isset($aliasMap[$key])) {
                continue;
            }
            if (!isset($result[$key]) || empty($result[$key])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private static function isTextLegible($text) {
        if (empty($text)) {
            return false;
        }

        $trimmed = trim($text);
        if (strlen($trimmed) < 100) {
            return false;
        }

        $sample = preg_replace('/[^A-Za-z0-9ÁÉÍÓÚÑáéíóúñ\-\.\s]/u', '', $text);
        $ratio = strlen($sample) > 0 ? (strlen($sample) / max(strlen($text), 1)) : 0;

        if ($ratio < 0.4) {
            return false;
        }

        if (substr_count($trimmed, ' ') < 10) {
            return false;
        }

        $keywords = ['RUT', 'NOMBRE', 'ALUMNO', 'CERTIFICADO', 'PRÁCTICA', 'EVALUACIÓN', 'AÑO'];
        foreach ($keywords as $keyword) {
            if (stripos($trimmed, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function shouldInvokeAi(array $parsedFields): bool {
        $config = self::loadPdfConfig();
        $mode = strtolower((string) ($config['ai_mode'] ?? 'auto'));

        if ($mode === 'always') {
            return true;
        }

        if ($mode === 'never') {
            return false;
        }

        $required = $config['ai_skip_required_keys'] ?? ['rut', 'nombre'];
        $minFilled = (int) ($config['ai_skip_min_fields'] ?? min(2, count($required)));
        $filled = 0;

        foreach ($required as $key) {
            if (!empty($parsedFields[$key])) {
                $filled++;
            }
        }

        return $filled < max($minFilled, 1);
    }

    private static function wrapShellArg($arg) {
        if ($arg === null || $arg === '') {
            return "\"\"";
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $escaped = str_replace('"', '\\"', $arg);
            return '"' . $escaped . '"';
        }

        return escapeshellarg($arg);
    }

    public static function extractStructuredData($pdfPath) {
        $baseText = self::extractTextFromPDF($pdfPath);
        $parsedBase = !empty($baseText) ? self::parseCertificateData($baseText, $pdfPath) : [];

        $text = $baseText;
        $parsed = $parsedBase;

        $aiResult = null;
        if (self::shouldInvokeAi($parsedBase)) {
            $aiResult = self::extractDataWithLocalAI($pdfPath);

            if (!empty($aiResult['text'])) {
                $text = $aiResult['text'];
                $parsedAi = self::parseCertificateData($aiResult['text'], $pdfPath);
                $parsed = self::mergeParsedSources($parsedBase, $parsedAi);
            }
        }

        $aiData = is_array($aiResult) ? $aiResult : [];
        $fields = self::mergeStructuredFields($parsed, $aiData['fields'] ?? []);

        if (empty($fields['numero_certificado'])) {
            $imageNumber = self::extractCertificateNumberFromImage($pdfPath);
            if ($imageNumber) {
                $fields['numero_certificado'] = $imageNumber;
            } else {
                $filenameNumber = self::extractCertificateNumberFromFilename($pdfPath);
                if ($filenameNumber) {
                    $fields['numero_certificado'] = $filenameNumber;
                }
            }
        }

        if (empty($fields['fecha_entrega'])) {
            $rawFooterDate = self::extractFooterDateFromRawText($text ?? '');
            if ($rawFooterDate) {
                $fields['fecha_entrega'] = $rawFooterDate;
            }
        }

        if (empty($fields['fecha_entrega']) && !empty($fields['numero_certificado'])) {
            $handwrittenDate = self::extractHandwrittenDate($pdfPath);
            if ($handwrittenDate) {
                $fields['fecha_entrega'] = $handwrittenDate;
            }
        }

        if (empty($fields['fecha_entrega'])) {
            $footerDate = self::extractFooterDate($pdfPath);
            if ($footerDate) {
                $fields['fecha_entrega'] = $footerDate;
            }
        }

        return [
            'text' => $text,
            'fields' => $fields,
            'lines' => $aiData['lines'] ?? [],
            'raw_ai' => $aiData['raw_ai'] ?? null,
            'source' => $aiResult
                ? (($aiData['success'] ?? false) ? 'ai' : 'ai-fallback')
                : 'fast-path',
            'command' => $aiData['command'] ?? null,
            'command_output' => $aiData['output'] ?? null
        ];
    }

    /**
     * Extrae texto usando PaddleOCR ejecutando el script Python auxiliar
     */
    private static function extractTextWithPaddle($pdfPath) {
        $config = self::loadPdfConfig();
        $python = $config['python_path'] ?? null;
        $script = $config['paddle_script_path'] ?? (__DIR__ . '/../scripts/ocr_paddle.py');

        if (empty($python) || !is_file($python) || !is_file($script)) {
            return '';
        }

        $popplerArg = '';
        if (!empty($config['poppler_path'])) {
            $popplerArg = ' --poppler ' . self::wrapShellArg($config['poppler_path']);
        }

        $command = sprintf(
            '%s %s --pdf %s%s 2>&1',
            self::wrapShellArg($python),
            self::wrapShellArg($script),
            self::wrapShellArg($pdfPath),
            $popplerArg
        );

        $output = shell_exec($command);
        if (empty($output)) {
            return '';
        }

        $decoded = self::decodeJsonResponse($output);
        if (!is_array($decoded) || empty($decoded['success'])) {
            error_log('PDFProcessor PaddleOCR Error: ' . trim($output));
            return '';
        }

        if (!empty($decoded['text'])) {
            return $decoded['text'];
        }

        if (!empty($decoded['lines']) && is_array($decoded['lines'])) {
            $joined = array_column($decoded['lines'], 'text');
            return trim(implode("\n", array_filter($joined)));
        }

        return '';
    }
    
    /**
     * Convierte PDF a imágenes PNG usando ImageMagick
     * @param string $pdfPath Ruta al PDF
     * @return array Array con rutas de imágenes temporales
     */
    private static function convertPDFToImages($pdfPath, int $maxPages = 2) {
        $tempDir = sys_get_temp_dir();
        $images = [];
        $convertCmd = self::getImageMagickConvertCommand();

        try {
            for ($page = 0; $page < $maxPages; $page++) {
                $tempImage = $tempDir . '/pdf_page_' . uniqid() . '_' . $page . '.png';
                $command = sprintf(
                    '%s -density 300 %s -background white -alpha remove -alpha off -colorspace Gray -normalize -type Grayscale -sharpen 0x1 -quality 100 %s 2>&1',
                    $convertCmd,
                    self::wrapShellArg($pdfPath . '[' . $page . ']'),
                    self::wrapShellArg($tempImage)
                );

                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($tempImage)) {
                    $images[] = $tempImage;
                } else {
                    if (file_exists($tempImage)) {
                        @unlink($tempImage);
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            foreach ($images as $image) {
                if (file_exists($image)) {
                    @unlink($image);
                }
            }
            throw $e;
        }

        return $images;
    }

    private static function extractHandwrittenDate(string $pdfPath): ?string
    {
        try {
            $images = self::convertPDFToImages($pdfPath);
        } catch (Exception $e) {
            return null;
        }

        if (empty($images)) {
            return null;
        }

        $firstImage = $images[0];
        $cropPath = sys_get_temp_dir() . '/ocr_crop_' . uniqid() . '.png';
        $debugDir = dirname($pdfPath);
        $debugCropPath = $debugDir . '/debug_crop_fecha.png';

        $cleanup = function () use (&$images, $cropPath, $debugCropPath) {
            if (file_exists($cropPath)) {
                @unlink($cropPath);
            }
            foreach ($images as $img) {
                if (file_exists($img)) {
                    @unlink($img);
                }
            }
            if (file_exists($debugCropPath)) {
                @unlink($debugCropPath);
            }
        };

        try {
            $cropCreated = false;
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($firstImage);
                if ($image) {
                    $width = imagesx($image);
                    $height = imagesy($image);
                    if ($width > 0 && $height > 0) {
                        $cropWidth = max(120, (int) round($width * 0.45));
                        $cropHeight = max(100, (int) round($height * 0.3));
                        $cropX = max(0, $width - $cropWidth - (int) round($width * 0.02));
                        $cropY = max(0, (int) round($height * 0.02));

                        $crop = imagecrop($image, [
                            'x' => $cropX,
                            'y' => $cropY,
                            'width' => $cropWidth,
                            'height' => $cropHeight,
                        ]);
                        imagedestroy($image);

                        if ($crop) {
                            imagepng($crop, $cropPath);
                            imagedestroy($crop);
                            $cropCreated = true;
                        }
                    } else {
                        imagedestroy($image);
                    }
                }
            }

            if (!$cropCreated) {
                $convertCmd = self::getImageMagickConvertCommand();
                $command = sprintf(
                    '%s %s -gravity NorthEast -crop 45%%x30%%+0+0 +repage -colorspace Gray -contrast -normalize -quality 100 %s 2>&1',
                    $convertCmd,
                    self::wrapShellArg($firstImage),
                    self::wrapShellArg($cropPath)
                );
                exec($command, $magickOutput, $returnCode);
                if ($returnCode !== 0 || !file_exists($cropPath)) {
                    return null;
                }
            }

            self::preprocessHandwritingImage($cropPath, $debugCropPath);

            $text = self::extractTextFromImage($cropPath, '--psm 7');
            if (!$text) {
                return null;
            }

            $normalized = self::normalizeDate($text);
            if ($normalized) {
                return $normalized;
            }

            if (preg_match('/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/u', $text, $matches)) {
                $date = self::normalizeDate($matches[1]);
                if ($date) {
                    return $date;
                }
            }

            return null;
        } finally {
            $cleanup();
        }
    }
    
    /**
     * Extrae texto de una imagen usando Tesseract OCR
     * @param string $imagePath Ruta a la imagen
     * @return string Texto extraído
     */
    private static function extractTextFromImage($imagePath, string $options = '') {
        $tempFile = sys_get_temp_dir() . '/ocr_output_' . uniqid();
        
        try {
            // Ejecutar Tesseract con idioma español
            $command = sprintf(
                'tesseract %s %s -l spa %s txt 2>&1',
                self::wrapShellArg($imagePath),
                self::wrapShellArg($tempFile),
                $options
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($tempFile . '.txt')) {
                $text = file_get_contents($tempFile . '.txt');
                unlink($tempFile . '.txt');
                return $text;
            }
            
            return '';
            
        } catch (Exception $e) {
            // Limpiar archivos temporales
            if (file_exists($tempFile . '.txt')) unlink($tempFile . '.txt');
            throw $e;
        }
    }
    
    /**
     * Parsea el texto extraído para obtener datos específicos del certificado
     * @param string $text Texto extraído del PDF
     * @return array Datos estructurados
     */
    public static function parseCertificateData($text, ?string $filePath = null) {
        $sanitizedText = self::sanitizeAccents($text);
        $upperText = mb_strtoupper($sanitizedText, 'UTF-8');
        $flatText = preg_replace('/\s+/', ' ', $upperText);

        $isInstitutionHeader = static function (?string $value): bool {
            if (empty($value)) {
                return false;
            }
            $clean = preg_replace('/\s+/', ' ', trim($value));
            if ($clean === '') {
                return false;
            }
            $upper = mb_strtoupper($clean, 'UTF-8');
            $badPrefixes = ['LICEO', 'COLEGIO', 'INSTITUTO', 'MINISTERIO', 'CERTIFICADO'];
            foreach ($badPrefixes as $prefix) {
                if (mb_strpos($upper, $prefix) === 0) {
                    return true;
                }
            }
            return false;
        };

        $data = [
            'rut' => null,
            'nombre' => null,
            'anio_egreso' => null,
            'fecha_egreso' => null,
            'titulo' => null,
            'numero_certificado' => null,
            'fecha_entrega' => null,
        ];

        // RUT
        if (preg_match('/RUT[:\s]*([0-9\.\-K]+)/u', $upperText, $matches)) {
            $data['rut'] = self::normalizeRut($matches[1]);
        } elseif (preg_match('/\b\d{1,2}[\. ]?\d{3}[\. ]?\d{3}-[0-9K]\b/u', $flatText, $matches)) {
            $data['rut'] = self::normalizeRut($matches[0]);
        }

        // Nombre completo (detenerse antes de la siguiente etiqueta)
        if (preg_match('/NOMBRE\s+COMPLETO[:\s]*(?:\R+)?([A-ZÁÉÍÓÚÑ\s]+?)(?=\R+(?:ESPECIALIDAD|AÑO|ANO|CENTRO|$))/u', $upperText, $matches)) {
            $nombre = self::normalizeWhitespace($matches[1]);
            $nombre = preg_replace('/\s+(ESPECIALIDAD|ADMINISTRACI[ÓO]N|ADMINISTRACION)\s*$/u', '', $nombre);
            $nombre = trim($nombre);
            if (!$isInstitutionHeader($nombre)) {
                $data['nombre'] = $nombre;
            }
        }

        if (empty($data['nombre']) && preg_match('/NOMBRE\s+COMPLETO\s*\R+([A-ZÁÉÍÓÚÑ\s]{5,})\R+ESPECIALIDAD/u', $upperText, $matches)) {
            $candidate = trim(self::normalizeWhitespace($matches[1]));
            if (!$isInstitutionHeader($candidate)) {
                $data['nombre'] = $candidate;
            }
        }

        if (empty($data['nombre'])) {
            if (preg_match('/^(?:GONZALO|[A-ZÁÉÍÓÚÑ]{3,})[A-ZÁÉÍÓÚÑ\s]+TORRES/mu', $upperText, $matches)) {
                $candidate = trim(self::normalizeWhitespace($matches[0]));
                if (!$isInstitutionHeader($candidate)) {
                    $data['nombre'] = $candidate;
                }
            }
        }

        // Año de egreso
        if (preg_match('/AÑO\s+EGRESO[:\s]*(\d{4})/u', $upperText, $matches)) {
            $data['anio_egreso'] = $matches[1];
            $data['fecha_egreso'] = $matches[1] . '-01-01';
        }

        // Título
        $titleCandidate = null;
        if (preg_match('/T[ÍI]TULO\s+DE[:\s]*\R+([^\r\n]+)/u', $sanitizedText, $matches)) {
            $titleCandidate = $matches[1];
        } elseif (preg_match('/POR\s+LO\s+TANTO[\s\S]*?T[ÍI]TULO\s+DE[:\s]*(?:\R+)?([A-ZÁÉÍÓÚÑ\s]+)/u', $upperText, $matches)) {
            $titleCandidate = $matches[1];
        } elseif (preg_match('/(T\S?CNICO\s+DE\s+NIVEL\s+MEDIO\s+EN\s+[A-ZÁÉÍÓÚÑ\s\-]+)/u', $upperText, $matches)) {
            $titleCandidate = $matches[1];
        }

        if (!$titleCandidate) {
            if (preg_match('/(T[ée]cnic[oó]\s+de\s+nivel\s+medio\s*en\s+[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+)/u', $sanitizedText, $matches)) {
                $titleCandidate = $matches[1];
            } elseif (preg_match('/T[ A-ZÁÉÍÓÚÑ\.]{2,80}ADMINISTRACI[ÓO]N/u', $upperText, $matches)) {
                $titleCandidate = $matches[0];
            }
        }

        if ($titleCandidate) {
            $normalizedTitle = self::normalizeTitleValue($titleCandidate);
            if (mb_strlen($normalizedTitle, 'UTF-8') < 15 && preg_match('/(T[íi]cnico[\s\S]{0,80}?Administraci[óo]n)/iu', $sanitizedText, $matches)) {
                $normalizedTitle = self::normalizeTitleValue($matches[1]);
            }
            $data['titulo'] = $normalizedTitle;
        }

        if (empty($data['titulo'])) {
            $lineCandidate = self::extractTitleLine($sanitizedText);
            if ($lineCandidate) {
                $data['titulo'] = self::normalizeTitleValue($lineCandidate);
            }
        }

        // Número de certificado manuscrito (nn-nnn)
        $numeroCertificadoPos = null;
        if (preg_match('/\b(\d{2})\s*[-]\s*(\d{3})\b/u', $upperText, $matches, PREG_OFFSET_CAPTURE)) {
            $data['numero_certificado'] = sprintf('%02d-%03d', (int)$matches[1][0], (int)$matches[2][0]);
            $numeroCertificadoPos = $matches[0][1];
        } elseif (preg_match('/-\s*0?(\d{3})/u', $upperText, $matches)) {
            $data['numero_certificado'] = '00-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        } elseif (preg_match('/\b(\d{6})\b/u', preg_replace('/[\s\n]+/', '', $upperText), $matches)) {
            $raw = $matches[1];
            $data['numero_certificado'] = substr($raw, 0, 3) . '-' . substr($raw, 3);
        }

        if (empty($data['numero_certificado']) && preg_match('/\b(\d{2})\s?[\-\s]\s?(\d{3})\b/u', $flatText, $matches)) {
            $data['numero_certificado'] = sprintf('%02d-%03d', (int)$matches[1], (int)$matches[2]);
        }

        if (empty($data['numero_certificado'])) {
            if (preg_match('/N[ÚU]MERO\s+DE\s+CERTIFICADO[:\s]*([0-9\-]+)/u', $upperText, $matches)) {
                $numRaw = preg_replace('/\s+/', '', $matches[1]);
                if (preg_match('/(\d{2})-(\d{3})/', $numRaw, $parts)) {
                    $data['numero_certificado'] = sprintf('%02d-%03d', (int)$parts[1], (int)$parts[2]);
                } elseif (preg_match('/(\d{5,6})/', $numRaw, $parts)) {
                    $raw = $parts[1];
                    $data['numero_certificado'] = substr($raw, 0, 3) . '-' . substr($raw, 3);
                }
            }
        }

        // Fecha manuscrita (dd/mm/aa) priorizando números junto al manuscrito del certificado
        $fechaManuscrita = null;
        if ($numeroCertificadoPos !== null) {
            $contextStart = max(0, $numeroCertificadoPos - 10);
            $context = mb_substr($upperText, $contextStart, 80);
            if (preg_match('/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/u', $context, $matches)) {
                $fechaManuscrita = self::normalizeDate($matches[1]);
            }
        }

        if (!$fechaManuscrita && preg_match('/-\s*(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/u', $upperText, $matches)) {
            $fechaManuscrita = self::normalizeDate($matches[1]);
        }

        if (!$fechaManuscrita) {
            $topSection = mb_substr($upperText, 0, 400);
            if (preg_match('/\b(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})\b/u', $topSection, $matches)) {
                $fechaManuscrita = self::normalizeDate($matches[1]);
            }
        }

        if ($fechaManuscrita) {
            $data['fecha_entrega'] = $fechaManuscrita;
        } else {
            $footerPattern = '/SE\s+EMITE\s+LA\s+PRESENTE\s+ACTA\s+CON\s+FECHA\s+([0-9]{1,2}\s+DE\s+[A-ZÁÉÍÓÚÑ]+\s+DE\s+\d{4})/u';
            if (preg_match($footerPattern, $upperText, $matches)) {
                $data['fecha_entrega'] = self::normalizeDate($matches[1]);
            }
        }

        // Validaciones adicionales
        if (empty($data['rut']) && preg_match('/\b\d{7,8}-[0-9K]\b/u', $flatText, $matches)) {
            $data['rut'] = self::normalizeRut($matches[0]);
        }

        if (empty($data['nombre'])) {
            if (preg_match('/SE\s+LE\s+CONFIR[ÍI]O\s+A\s+(?:DON|DOÑA)\s*(?:\(ÑA\))?\s+([A-ZÁÉÍÓÚÑ\s]{5,}?)(?=,|\s+RUT|\.)/u', $upperText, $matches)) {
                $candidate = trim(self::normalizeWhitespace($matches[1]));
                if (!$isInstitutionHeader($candidate)) {
                    $data['nombre'] = $candidate;
                }
            }
        }

        if (empty($data['nombre']) && preg_match('/\b([A-ZÁÉÍÓÚÑ]{3,}\s+[A-ZÁÉÍÓÚÑ]{3,}\s+[A-ZÁÉÍÓÚÑ\s]{3,})\b/u', $flatText, $matches)) {
            $candidate = self::normalizeWhitespace($matches[1]);
            if (!$isInstitutionHeader($candidate)) {
                $data['nombre'] = $candidate;
            }
        }

        if (empty($data['nombre']) && $filePath) {
            $basename = pathinfo($filePath, PATHINFO_FILENAME);
            $basename = preg_replace('/(_|-|\.)+/', ' ', $basename);
            $basename = trim($basename);
            if ($basename !== '' && !$isInstitutionHeader($basename) && preg_match('/[A-Za-zÁÉÍÓÚáéíóúÑñ]{2,}\s+[A-Za-zÁÉÍÓÚáéíóúÑñ]{2,}/u', $basename)) {
                $data['nombre'] = mb_strtoupper($basename, 'UTF-8');
            }
        }

        if (empty($data['anio_egreso']) && preg_match('/\b(20\d{2})\b/u', $flatText, $matches)) {
            $data['anio_egreso'] = $matches[1];
            $data['fecha_egreso'] = $matches[1] . '-01-01';
        }

        if (empty($data['titulo']) && preg_match('/T[ÍI]TULO[:\s]*([A-ZÁÉÍÓÚÑ\s]+)/u', $upperText, $matches)) {
            $data['titulo'] = self::normalizeWhitespace($matches[1]);
        }

        foreach (['rut','nombre','anio_egreso','titulo','numero_certificado','fecha_entrega'] as $campo) {
            if (empty($data[$campo])) {
                error_log("PDFProcessor: No se pudo extraer el campo '{$campo}' del PDF.");
            }
        }

        return $data;
    }
    
    /**
     * Formatea el RUT al formato estándar chileno
     * @param string $rut RUT sin formatear
     * @return string RUT formateado
     */
    private static function formatRUT($rut) {
        // Limpiar RUT
        $rut = preg_replace('/[^0-9K]/', '', strtoupper($rut));
        
        if (strlen($rut) < 2) {
            return $rut;
        }
        
        // Separar dígito verificador
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);
        
        // Formatear con puntos
        $numeroFormateado = number_format($numero, 0, '', '.');
        
        return $numeroFormateado . '-' . $dv;
    }
    
    /**
     * Verifica si Tesseract está disponible
     * @return bool
     */
    public static function isTesseractAvailable() {
        exec('tesseract --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Verifica si ImageMagick está disponible
     * @return bool
     */
    public static function isImageMagickAvailable() {
        $commands = [
            'magick --version 2>&1',
            'convert --version 2>&1'
        ];

        foreach ($commands as $cmd) {
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0) {
                return true;
            }
        }

        return false;
    }
    
    /**
     */
    private static function getImageMagickConvertCommand() {
        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            return 'magick convert';
        }

        return 'convert';
    }

    private static function preprocessHandwritingImage(string $imagePath, string $debugTarget = null): void
    {
        $convertCmd = self::getImageMagickConvertCommand();
        $command = sprintf(
            '%s %s -resize 500%% -colorspace Gray -contrast -normalize -sharpen 0x1 -threshold 75%% %s 2>&1',
            $convertCmd,
            self::wrapShellArg($imagePath),
            self::wrapShellArg($imagePath)
        );
        exec($command);

        if ($debugTarget) {
            @copy($imagePath, $debugTarget);
        }
    }

    private static function decodeJsonResponse(string $output): ?array
    {
        $trimmed = trim($output);
        $decoded = json_decode($trimmed, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($trimmed, $start, $end - $start + 1);
            $decoded = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private static function extractFooterDateFromRawText(string $text): ?string
    {
        if (!$text) {
            return null;
        }

        $pattern = '/se\s+emite\s+la\s+presente\s+acta\s+con\s+fecha\s+(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})/iu';
        if (preg_match($pattern, $text, $matches)) {
            $day = (int)$matches[1];
            $monthName = mb_strtolower($matches[2], 'UTF-8');
            $year = (int)$matches[3];
            $month = self::spanishMonthToNumber($monthName);
            if ($month) {
                return sprintf('%04d-%02d-%02d', $year, $month, max(1, min($day, 31)));
            }
        }

        return null;
    }

    private static function spanishMonthToNumber(string $name): ?int
    {
        $map = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
        ];

        $name = trim($name);
        return $map[$name] ?? null;
    }

    private static function normalizeTitleValue(string $value): string
    {
        $value = self::normalizeWhitespace($value);
        $value = self::trimTitleTail($value);
        $value = preg_replace('/medio\s*en/iu', 'medio en', $value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/', ' ', $value);
        $titleCase = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');

        $catalog = [
            'tecnico de nivel medio en administracion' => 'Técnico De Nivel Medio En Administración',
            'operaciones portuarias' => 'Operaciones Portuarias',
            'tecnico en administracion' => 'Técnico En Administración',
            'programacion' => 'Programación',
            'tecnico financiero' => 'Técnico Financiero',
            'tecnico en computacion' => 'Técnico En Computación',
            'tecnico en importacion y exportacion' => 'Técnico En Importación Y Exportación',
            'contabilidad' => 'Contabilidad',
            'explotacion minera' => 'Explotación Minera'
        ];

        $normalizedKey = self::normalizeWhitespace($value);
        foreach ($catalog as $needle => $label) {
            if (strpos($normalizedKey, $needle) !== false) {
                return $label;
            }
        }

        return $titleCase;
    }

    private static function trimTitleTail(string $value): string
    {
        $patterns = [
            '/\s+(DIRECCI[ÓO]N|DIRECTOR|DIRECTO|JEFE|ARICA|AN|DORIS|AGUILAR|DIAZ)\b/iu',
            '/\s+SE\s+EMITE\b/iu',
        ];

        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $value);
            if (!empty($parts[0])) {
                $value = $parts[0];
            }
        }

        if (preg_match('/(T[ÉE]CNICO[\s\S]{0,120}?EN\s+[A-ZÁÉÍÓÚÑ\s]+?ADMINISTRACI[ÓO]N)/iu', $value, $matches)) {
            $value = trim($matches[1]);
        } elseif (preg_match('/^(.*?ADMINISTRACI[ÓO]N)\b/iu', $value, $matches)) {
            $value = trim($matches[1]);
        }

        return trim($value);
    }

    private static function extractCertificateNumberFromFilename(string $pdfPath): ?string
    {
        $filename = basename($pdfPath);
        if (preg_match('/(\d{2})[\-_](\d{3})/', $filename, $matches)) {
            return sprintf('%02d-%03d', (int)$matches[1], (int)$matches[2]);
        }

        if (preg_match('/(\d{5,6})/', $filename, $matches)) {
            $raw = str_pad($matches[1], 5, '0', STR_PAD_LEFT);
            if (strlen($raw) >= 5) {
                $raw = substr($raw, -5);
                return substr($raw, 0, 2) . '-' . substr($raw, 2);
            }
        }

        return null;
    }

    private static function extractCertificateNumberFromImage(string $pdfPath): ?string
    {
        try {
            $images = self::convertPDFToImages($pdfPath, 2);
        } catch (Exception $e) {
            return null;
        }

        if (empty($images)) {
            return null;
        }

        $regions = [
            'handwritten_top_right' => function ($width, $height) {
                return [
                    'x' => max(0, $width - (int) round($width * 0.42)),
                    'y' => max(0, (int) round($height * 0.02)),
                    'width' => max(120, (int) round($width * 0.4)),
                    'height' => max(80, (int) round($height * 0.22)),
                ];
            },
            'handwritten_top_left' => function ($width, $height) {
                return [
                    'x' => max(0, (int) round($width * 0.02)),
                    'y' => max(0, (int) round($height * 0.02)),
                    'width' => max(120, (int) round($width * 0.4)),
                    'height' => max(80, (int) round($height * 0.22)),
                ];
            },
            'second_page_top_left' => function ($width, $height) {
                return [
                    'x' => max(0, (int) round($width * 0.02)),
                    'y' => max(0, (int) round($height * 0.02)),
                    'width' => max(150, (int) round($width * 0.45)),
                    'height' => max(100, (int) round($height * 0.25)),
                ];
            },
        ];

        foreach ($images as $index => $imagePath) {
            foreach ($regions as $regionName => $regionFactory) {
                $number = self::extractNumberFromImageRegion($imagePath, $regionFactory);
                if ($number) {
                    self::cleanupTempImages($images);
                    return $number;
                }
            }
        }

        self::cleanupTempImages($images);
        return null;
    }

    private static function extractNumberFromImageRegion(string $imagePath, callable $regionFactory): ?string
    {
        $cropPath = sys_get_temp_dir() . '/ocr_cert_' . uniqid() . '.png';
        $cleanup = function () use ($cropPath) {
            if (file_exists($cropPath)) {
                @unlink($cropPath);
            }
        };

        try {
            $crop = self::cropImageRegion($imagePath, $regionFactory, $cropPath);
            if (!$crop) {
                return null;
            }

            self::preprocessHandwritingImage($cropPath);
            $text = self::extractTextFromImage($cropPath, '--psm 7 -c tessedit_char_whitelist=0123456789-');
            if (!$text) {
                return null;
            }

            if (preg_match('/(\d{2})\D{0,2}(\d{3})/', $text, $matches)) {
                return sprintf('%02d-%03d', (int)$matches[1], (int)$matches[2]);
            }
            if (preg_match('/(\d{5,6})/', preg_replace('/\D+/', '', $text), $matches)) {
                $raw = substr(str_pad($matches[1], 5, '0', STR_PAD_LEFT), -5);
                return substr($raw, 0, 2) . '-' . substr($raw, 2);
            }

            return null;
        } finally {
            $cleanup();
        }
    }

    private static function cropImageRegion(string $imagePath, callable $regionFactory, string $targetPath): bool
    {
        if (function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($imagePath);
            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                if ($width > 0 && $height > 0) {
                    $region = $regionFactory($width, $height);
                    $crop = imagecrop($image, $region);
                    imagedestroy($image);
                    if ($crop) {
                        imagepng($crop, $targetPath);
                        imagedestroy($crop);
                        return true;
                    }
                } else {
                    imagedestroy($image);
                }
            }
        }

        $convertCmd = self::getImageMagickConvertCommand();
        $region = $regionFactory(1000, 1000);
        $geometry = sprintf('%dx%d+%d+%d', $region['width'], $region['height'], $region['x'], $region['y']);
        $command = sprintf(
            '%s %s -crop %s +repage -colorspace Gray -contrast -normalize -quality 100 %s 2>&1',
            $convertCmd,
            self::wrapShellArg($imagePath),
            $geometry,
            self::wrapShellArg($targetPath)
        );
        exec($command, $magickOutput, $returnCode);
        return $returnCode === 0 && file_exists($targetPath);
    }

    private static function cleanupTempImages(array $images): void
    {
        foreach ($images as $image) {
            if (file_exists($image)) {
                @unlink($image);
            }
        }
    }

    private static function extractTitleLine(string $text): ?string
    {
        $candidates = [
            '/T[íi]tulo\s+de[:\s]*\R+([^\r\n]+)/iu',
            '/(T[íi]cnico[\s\S]{0,120}?Administraci[óo]n)/iu',
        ];

        foreach ($candidates as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = $matches[1];
                $candidate = preg_split('/\s+(DIRECTO|DIRECCION|JEFE)\b/i', $candidate)[0];
                return trim($candidate);
            }
        }

        $lines = preg_split('/\R+/', $text);
        foreach ($lines as $line) {
            if (preg_match('/T[íi]cnico/iu', $line)) {
                return trim($line);
            }
        }

        return null;
    }

    private static function sanitizeAccents(string $value): string
    {
        $replacements = [
            'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
            'Ã' => 'Á', 'Ã‰' => 'É', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú',
            'Ã±' => 'ñ', 'Ã‘' => 'Ñ', 'Â' => '',
        ];
        $clean = strtr($value, $replacements);
        $clean = str_replace(['T�cnico', 'Administraci�n'], ['Técnico', 'Administración'], $clean);
        return $clean;
    }

    private static function extractFooterDate(string $pdfPath): ?string
    {
        try {
            $images = self::convertPDFToImages($pdfPath);
        } catch (Exception $e) {
            return null;
        }

        if (empty($images)) {
            return null;
        }

        $firstImage = $images[0];
        $convertCmd = self::getImageMagickConvertCommand();
        $cropPath = sys_get_temp_dir() . '/ocr_footer_' . uniqid() . '.png';

        $command = sprintf(
            '%s %s -gravity South -crop 90%%x25%%+0+0 +repage -colorspace Gray -contrast -normalize -quality 100 %s 2>&1',
            $convertCmd,
            self::wrapShellArg($firstImage),
            self::wrapShellArg($cropPath)
        );
        exec($command);

        if (!file_exists($cropPath)) {
            foreach ($images as $img) {
                if (file_exists($img)) {
                    @unlink($img);
                }
            }
            return null;
        }

        $text = self::extractTextFromImage($cropPath);

        @unlink($cropPath);
        foreach ($images as $img) {
            if (file_exists($img)) {
                @unlink($img);
            }
        }

        if (!$text) {
            return null;
        }

        if (preg_match('/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/u', $text, $matches)) {
            $date = self::normalizeDate($matches[1]);
            if ($date) {
                return $date;
            }
        }

        if (preg_match('/(\d{1,2}\s+DE\s+[A-ZÁÉÍÓÚÑ]+\s+DE\s+\d{4})/u', mb_strtoupper($text, 'UTF-8'), $matches)) {
            $date = self::normalizeDate($matches[1]);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Extrae texto usando la librería Smalot\PdfParser, priorizando la página 1.
     */
    private static function extractTextWithPdfParser($pdfPath) {
        if (!class_exists(Parser::class)) {
            return '';
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $pages = $pdf->getPages();

            if (!empty($pages)) {
                // Solo necesitamos la primera página (resumen del expediente)
                return $pages[0]->getText();
            }

            return $pdf->getText();

        } catch (Exception $e) {
            error_log('PDFParser Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Extrae texto usando Spatie\PdfToText (pdftotext CLI)
     */
    private static function extractTextWithSpatie($pdfPath) {
        if (!class_exists(Pdf::class)) {
            return '';
        }

        $binary = self::resolvePdftotextBinary();

        try {
            $text = (new Pdf($binary))
                ->setPdf($pdfPath)
                ->text();

            return trim($text);

        } catch (\Throwable $e) {
            error_log('PDFProcessor Spatie Error: ' . $e->getMessage());
            return '';
        }
    }

    private static function resolvePdftotextBinary() {
        $config = self::loadPdfConfig();
        if (!empty($config['pdftotext_path'])) {
            return $config['pdftotext_path'];
        }

        if ($env = getenv('PDFTOTEXT_PATH')) {
            return $env;
        }

        return 'pdftotext';
    }

    private static function loadPdfConfig() {
        static $config;

        if ($config !== null) {
            return $config;
        }

        $path = __DIR__ . '/../config/pdf.php';
        $config = file_exists($path) ? require $path : [];

        return is_array($config) ? $config : [];
    }

    private static function isPdfToTextAvailable() {
        $binary = self::resolvePdftotextBinary();

        if ($binary !== 'pdftotext' && is_file($binary)) {
            return is_executable($binary);
        }

        $command = stripos(PHP_OS_FAMILY, 'Windows') !== false ? 'where pdftotext' : 'which pdftotext';
        exec($command . ' 2>&1', $output, $code);
        return $code === 0;
    }

    private static function shouldOverrideNombre(?string $current, ?string $candidate): bool {
        if (empty($candidate)) {
            return false;
        }

        if (empty($current)) {
            return true;
        }

        $currentNorm = self::normalizeWhitespace($current);
        $candidateNorm = self::normalizeWhitespace($candidate);

        if ($currentNorm === $candidateNorm) {
            return false;
        }

        $currentWords = array_filter(preg_split('/\s+/u', $currentNorm));
        $candidateWords = array_filter(preg_split('/\s+/u', $candidateNorm));

        $currentWordCount = count($currentWords);
        $candidateWordCount = count($candidateWords);

        if ($candidateWordCount >= 3 && $currentWordCount < $candidateWordCount) {
            return true;
        }

        if ($currentWordCount <= 1 && $candidateWordCount >= 3) {
            return true;
        }

        if (strpos($currentNorm, ' ') === false && strpos($candidateNorm, ' ') !== false) {
            return true;
        }

        return false;
    }

    private static function formatCertificateNumber($value) {
        if (empty($value)) {
            return $value;
        }

        if (preg_match('/(\d{1,2})[^0-9]*(\d{3})/u', $value, $matches)) {
            return sprintf('%02d-%03d', (int) $matches[1], (int) $matches[2]);
        }

        if (preg_match('/(\d{3,5})/u', $value, $matches)) {
            $sequence = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
            $left = substr($sequence, 0, max(strlen($sequence) - 3, 1));
            $right = substr($sequence, -3);
            return str_pad($left, 2, '0', STR_PAD_LEFT) . '-' . $right;
        }

        return $value;
    }

    private static function normalizeWhitespace($value) {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private static function normalizeRut($rut) {
        $clean = preg_replace('/[^0-9Kk]/', '', $rut);
        if (strlen($clean) < 2) {
            return null;
        }

        $dv = strtoupper(substr($clean, -1));
        $body = substr($clean, 0, -1);
        $formatted = number_format((int)$body, 0, '', '.') . '-' . $dv;

        return $formatted;
    }

    private static function normalizeDate($value) {
        if (empty($value)) {
            return null;
        }

        $value = str_replace(['.', '-'], '/', $value);
        $parts = array_map('trim', explode('/', $value));
        if (count($parts) !== 3) {
            return null;
        }

        [$day, $month, $year] = $parts;
        if (strlen($year) === 2) {
            $year = ((int)$year >= 50 ? '19' : '20') . $year;
        }

        return sprintf('%04d-%02d-%02d', (int)$year, (int)$month, (int)$day);
    }

    /**
     * Verifica todas las dependencias requeridas por el flujo.
     */
    public static function checkDependencies() {
        return [
            'pdf_to_text' => self::isPdfToTextAvailable(),
            'pdf_parser' => class_exists(Parser::class),
            'tesseract' => self::isTesseractAvailable(),
            'imagemagick' => self::isImageMagickAvailable(),
            'php_fileinfo' => extension_loaded('fileinfo'),
            'php_gd' => extension_loaded('gd'),
            'python' => self::isPythonConfigured(),
            'paddle_script' => self::isPaddleScriptAvailable()
        ];
    }

    private static function isPythonConfigured() {
        $config = self::loadPdfConfig();
        $python = $config['python_path'] ?? null;
        return !empty($python) && is_file($python);
    }

    private static function isPaddleScriptAvailable() {
        $config = self::loadPdfConfig();
        $script = $config['paddle_script_path'] ?? (__DIR__ . '/../scripts/ocr_paddle.py');
        return is_file($script);
    }
}
