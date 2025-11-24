<?php
require_once 'modelo/ExpedienteQueue.php';
require_once 'services/ExpedienteProcessor.php';

$pdfPath = getcwd() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'expedientes' . DIRECTORY_SEPARATOR . 'YUDITH_CARMEN_QUISPE_MORALES.pdf';
if (!file_exists($pdfPath)) {
    fwrite(STDERR, 'Archivo no encontrado: ' . $pdfPath . PHP_EOL);
    exit(1);
}

$queue = new ExpedienteQueue();
$jobId = $queue->enqueue([
    'filename' => basename($pdfPath),
    'filepath' => $pdfPath,
]);

$processor = new ExpedienteProcessor($queue);
$result = $processor->processJobById($jobId);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
?>
