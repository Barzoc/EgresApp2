<?php

require_once __DIR__ . '/../modelo/ExpedienteQueue.php';
require_once __DIR__ . '/../services/ExpedienteProcessor.php';

header('Content-Type: application/json; charset=utf-8');

try {
    session_start();
} catch (Exception $e) {
    // Ignorar si las cabeceras ya fueron enviadas (por ejemplo en CLI)
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$idExpediente = $_POST['id_expediente'] ?? null;

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'No se recibió el archivo PDF']);
    exit;
}

$fileTmp = $_FILES['file']['tmp_name'];
$mime = function_exists('mime_content_type') ? mime_content_type($fileTmp) : $_FILES['file']['type'];
$extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
$allowedMimes = ['application/pdf', 'application/x-pdf', 'application/octet-stream'];

if (!in_array($mime, $allowedMimes, true) && $extension !== 'pdf') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'El archivo debe ser un PDF válido',
        'detalles' => ['mime_detectado' => $mime, 'extension' => $extension]
    ]);
    exit;
}

$uploadsDir = realpath(__DIR__ . '/../assets/expedientes/expedientes_subidos');
if ($uploadsDir === false) {
    $uploadsDir = __DIR__ . '/../assets/expedientes/expedientes_subidos';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
}

$originalName = $_FILES['file']['name'] ?? 'expediente.pdf';
$extension = pathinfo($originalName, PATHINFO_EXTENSION);
$baseName = pathinfo($originalName, PATHINFO_FILENAME);

if (empty($extension)) {
    $extension = 'pdf';
}

$safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName);
if ($safeBase === '') {
    $safeBase = 'expediente';
}

$filename = $safeBase . '.' . strtolower($extension);
$counter = 1;
while (file_exists(rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename)) {
    $filename = $safeBase . '_' . $counter . '.' . strtolower($extension);
    $counter++;
}
$destino = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'No se pudo guardar el archivo en el servidor']);
    exit;
}

$queue = new ExpedienteQueue();
$queueId = $queue->enqueue([
    'filename' => $filename,
    'filepath' => $destino,
    'id_expediente' => $idExpediente
]);

$processor = new ExpedienteProcessor($queue);
$processResult = $processor->processJobById($queueId);

if (!$processResult['success']) {
    $mensaje = $processResult['mensaje'] ?? 'Error al procesar el expediente.';
    $statusCode = 500;
    $esDuplicado = false;
    if (stripos($mensaje, 'Ya existe un egresado') !== false || stripos($mensaje, 'ya fue procesado') !== false) {
        $statusCode = 409;
        $esDuplicado = true;
    } elseif (stripos($mensaje, 'Expediente ya se encuentra ingresado') !== false || stripos($mensaje, 'Estos datos ya han sido ingresados') !== false) {
        $statusCode = 409;
        $esDuplicado = true;
    } elseif (stripos($mensaje, 'OCR incompleto') !== false) {
        $statusCode = 422;
    }

    if ($esDuplicado && is_file($destino)) {
        @unlink($destino);
    }

    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'mensaje' => $mensaje,
        'queue_id' => $queueId,
        'estado' => 'failed',
        'debug' => $processResult['payload']['ocr'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fields = $processResult['fields'] ?? [];

echo json_encode([
    'success' => true,
    'mensaje' => 'Expediente procesado correctamente',
    'queue_id' => $queueId,
    'estado' => 'done',
    'archivo' => $filename,
    'egresado_id' => $processResult['id_expediente'] ?? null,
    'datos' => [
        'rut' => $fields['rut'] ?? '',
        'nombre' => $fields['nombre'] ?? $fields['nombre_completo'] ?? '',
        'fecha_egreso' => $fields['fecha_egreso'] ?? $fields['anio_egreso'] ?? '',
        'fecha_entrega' => $fields['fecha_entrega'] ?? '',
        'numero_certificado' => $fields['numero_certificado'] ?? '',
        'titulo' => $fields['titulo'] ?? $fields['especialidad'] ?? ''
    ],
    'debug' => $processResult['payload']['ocr'] ?? null
], JSON_UNESCAPED_UNICODE);
