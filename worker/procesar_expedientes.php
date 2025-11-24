<?php

require_once __DIR__ . '/../modelo/ExpedienteQueue.php';
require_once __DIR__ . '/../lib/PDFProcessor.php';
require_once __DIR__ . '/../modelo/Egresado.php';

function logMessage(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message" . PHP_EOL;
}

$queue = new ExpedienteQueue();
$egresadoModel = new Egresado();

function convertToIsoDate(?string $value): ?string
{
    if (empty($value)) {
        return null;
    }

    $normalized = str_replace(['/', '.'], '-', trim($value));

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
        return $normalized;
    }

    if (!preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})$/', $normalized, $matches)) {
        return null;
    }

    $day = (int) $matches[1];
    $month = (int) $matches[2];
    $year = $matches[3];

    if (strlen($year) === 2) {
        $year = (intval($year) >= 50 ? '19' : '20') . $year;
    }

    $year = (int) $year;

    if (!checkdate($month, $day, $year)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

while (true) {
    $job = $queue->claimNextPending();
    if (!$job) {
        logMessage('No hay expedientes pendientes. Finalizando.');
        break;
    }

    $jobId = (int) $job['id'];
    logMessage("Procesando expediente #$jobId ({$job['filename']})");

    $resultPayload = [
        'filename' => $job['filename'],
        'filepath' => $job['filepath'],
        'id_expediente' => $job['id_expediente'],
    ];

    try {
        if (!file_exists($job['filepath'])) {
            throw new RuntimeException('El archivo PDF ya no existe en la ruta especificada.');
        }

        $resultadoOCR = PDFProcessor::extractStructuredData($job['filepath']);
        $text = $resultadoOCR['text'] ?? '';
        $fields = $resultadoOCR['fields'] ?? [];
        $fechaEntregaIso = convertToIsoDate($fields['fecha_entrega'] ?? null);
        if ($fechaEntregaIso) {
            $fields['fecha_entrega'] = $fechaEntregaIso;
        }
        $rutExtraido = $fields['rut'] ?? null;
        $numeroCertificado = $fields['numero_certificado'] ?? null;

        $resultPayload['ocr'] = [
            'source' => $resultadoOCR['source'] ?? null,
            'command' => $resultadoOCR['command'] ?? null,
            'command_output' => $resultadoOCR['command_output'] ?? null,
            'texto_muestra' => mb_substr($text, 0, 500),
            'datos_crudos' => $fields,
        ];

        if (empty($fields) || (empty($rutExtraido) && empty($fields['nombre']))) {
            throw new RuntimeException('OCR incompleto: no se extrajeron campos mínimos.');
        }

        if (empty($job['id_expediente'])) {
            if ($rutExtraido) {
                $duplicadoRut = $egresadoModel->BuscarPorRutNormalizado($rutExtraido);
                if ($duplicadoRut) {
                    throw new RuntimeException('Ya existe un egresado con este RUT (' . ($duplicadoRut['identificacion'] ?? 'desconocido') . ').');
                }
            }

            $nuevoId = $egresadoModel->CrearDesdeExpediente($fields, $job['filename']);
            if (!$nuevoId) {
                throw new RuntimeException('No se pudo crear el egresado a partir del expediente.');
            }
            $job['id_expediente'] = $nuevoId;
            $resultPayload['id_expediente'] = $nuevoId;
        } else {
            $egresadoModel->CambiarExpediente($job['id_expediente'], $job['filename']);
        }

        $egresadoModel->ActualizarDatosCertificado($job['id_expediente'], [
            'nombre' => $fields['nombre'] ?? null,
            'rut' => $rutExtraido,
            'anio_egreso' => $fields['anio_egreso'] ?? null,
            'titulo' => $fields['titulo'] ?? null,
            'numero_certificado' => $fields['numero_certificado'] ?? null,
            'fecha_entrega' => $fechaEntregaIso,
        ]);

        $egresadoModel->ActualizarTituloEgresadoDatos($job['id_expediente'], [
            'numero_documento' => $numeroCertificado,
            'fecha_grado' => $fechaEntregaIso,
            'titulo_nombre' => $fields['titulo'] ?? null,
        ]);

        $debugDir = dirname($job['filepath']);
        file_put_contents(
            $debugDir . '/debug_texto.txt',
            "TEXTO EXTRAÍDO:\n" . $text . "\n\nMETADATA:\n" . json_encode([
                'command' => $resultadoOCR['command'] ?? null,
                'command_output' => $resultadoOCR['command_output'] ?? null,
                'source' => $resultadoOCR['source'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $debugDir . '/debug_datos.txt',
            "DATOS EXTRAÍDOS:\n" . json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $queue->markCompleted($jobId, $resultPayload);
        logMessage("Expediente #$jobId procesado correctamente. ID egresado: {$job['id_expediente']}");
    } catch (Throwable $e) {
        $queue->markFailed($jobId, $e->getMessage(), $resultPayload);
        logMessage("Error al procesar expediente #$jobId: {$e->getMessage()}");
    }
}
