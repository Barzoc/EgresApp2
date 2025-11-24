<?php

require_once __DIR__ . '/../modelo/ExpedienteQueue.php';
require_once __DIR__ . '/../lib/PDFProcessor.php';
require_once __DIR__ . '/../modelo/Egresado.php';

class ExpedienteProcessor
{
    private ExpedienteQueue $queue;
    private Egresado $egresado;

    public function __construct(?ExpedienteQueue $queue = null, ?Egresado $egresado = null)
    {
        $this->queue = $queue ?? new ExpedienteQueue();
        $this->egresado = $egresado ?? new Egresado();
    }

    public function processJobById(int $jobId): array
    {
        $job = $this->queue->acquireJobForProcessing($jobId);
        if (!$job) {
            return [
                'success' => false,
                'mensaje' => 'El expediente ya fue procesado o no existe en la cola.',
                'job_id' => $jobId,
            ];
        }

        return $this->processJob($job);
    }

    public function processJob(array $job): array
    {
        $jobId = (int) $job['id'];
        $overallStart = microtime(true);
        $lastCheckpoint = $overallStart;
        $timings = [
            'started_at' => date('c'),
        ];
        $recordTiming = function (string $label) use (&$timings, &$lastCheckpoint) {
            $now = microtime(true);
            $timings[$label] = round(($now - $lastCheckpoint) * 1000, 2);
            $lastCheckpoint = $now;
        };

        $resultPayload = [
            'filename' => $job['filename'],
            'filepath' => $job['filepath'],
            'id_expediente' => $job['id_expediente'],
            'timings' => $timings,
        ];

        $fields = [];
        $text = '';

        try {
            if (!file_exists($job['filepath'])) {
                throw new RuntimeException('El archivo PDF ya no existe en la ruta especificada.');
            }

            $resultadoOCR = PDFProcessor::extractStructuredData($job['filepath']);
            $recordTiming('ocr_ms');
            $text = $resultadoOCR['text'] ?? '';
            $fields = $resultadoOCR['fields'] ?? [];
            $rutExtraido = $fields['rut'] ?? null;
            $numeroCertificado = $fields['numero_certificado'] ?? null;
            $fechaEntregaIso = $this->convertToIsoDate($fields['fecha_entrega'] ?? null);
            $persistableFields = $fields;
            $persistableFields['fecha_entrega'] = $fechaEntregaIso;

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
                    $duplicadoRut = $this->egresado->BuscarPorRutNormalizado($rutExtraido);
                    if ($duplicadoRut) {
                        throw new RuntimeException('Expediente ya se encuentra ingresado (RUT ' . ($duplicadoRut['identificacion'] ?? 'desconocido') . ').');
                    }
                }

                if (!empty($numeroCertificado)) {
                    $duplicadoCert = $this->egresado->BuscarPorNumeroCertificado($numeroCertificado);
                    if ($duplicadoCert) {
                        throw new RuntimeException('Estos datos ya han sido ingresados (número de certificado ' . $numeroCertificado . ').');
                    }
                }

                $nuevoId = $this->egresado->CrearDesdeExpediente($persistableFields, $job['filename']);
                if (!$nuevoId) {
                    throw new RuntimeException('No se pudo crear el egresado a partir del expediente.');
                }
                $job['id_expediente'] = $nuevoId;
                $resultPayload['id_expediente'] = $nuevoId;
            } else {
                $this->egresado->CambiarExpediente($job['id_expediente'], $job['filename']);
            }

            $this->egresado->ActualizarDatosCertificado($job['id_expediente'], [
                'nombre' => $fields['nombre'] ?? null,
                'rut' => $rutExtraido,
                'anio_egreso' => $fields['anio_egreso'] ?? null,
                'titulo' => $fields['titulo'] ?? null,
                'numero_certificado' => $fields['numero_certificado'] ?? null,
                'fecha_entrega' => $fechaEntregaIso,
            ]);

            $this->egresado->ActualizarTituloEgresadoDatos($job['id_expediente'], [
                'numero_documento' => $numeroCertificado,
                'fecha_grado' => $fechaEntregaIso,
                'titulo_nombre' => $fields['titulo'] ?? null,
            ]);
            $recordTiming('db_updates_ms');

            $this->writeDebugFiles($job['filepath'], $text, $resultadoOCR);

            $resultPayload['fields'] = $fields;
            $timings['total_ms'] = round((microtime(true) - $overallStart) * 1000, 2);
            $resultPayload['timings'] = $timings;
            $this->queue->markCompleted($jobId, $resultPayload);
            error_log(sprintf('ExpedienteProcessor job #%d timings: %s', $jobId, json_encode($timings)));

            return [
                'success' => true,
                'mensaje' => 'Expediente procesado correctamente',
                'job_id' => $jobId,
                'id_expediente' => $job['id_expediente'],
                'fields' => $fields,
                'payload' => $resultPayload,
                'archivo' => $job['filename'],
                'timings' => $timings,
            ];
        } catch (Throwable $e) {
            $timings['total_ms'] = round((microtime(true) - $overallStart) * 1000, 2);
            $timings['error'] = $e->getMessage();
            $resultPayload['timings'] = $timings;
            $this->queue->markFailed($jobId, $e->getMessage(), $resultPayload);
            error_log(sprintf('ExpedienteProcessor job #%d failed after %sms: %s', $jobId, $timings['total_ms'], $e->getMessage()));
            return [
                'success' => false,
                'mensaje' => $e->getMessage(),
                'job_id' => $jobId,
                'id_expediente' => $job['id_expediente'],
                'fields' => $fields,
                'payload' => $resultPayload,
                'timings' => $timings,
            ];
        }
    }

    private function writeDebugFiles(string $filePath, string $text, array $resultadoOCR): void
    {
        $debugDir = dirname($filePath);
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
            "DATOS EXTRAÍDOS:\n" . json_encode($resultadoOCR['fields'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function convertToIsoDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $normalized = str_replace(['/', '.'], '-', trim($value));

        // Ya viene en formato ISO completo
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
}
