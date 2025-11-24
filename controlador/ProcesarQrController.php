<?php
header('Content-Type: application/json');

// Espera un JSON con { qr: 'codigo' }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['qr'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibio el codigo']);
    exit();
}
$qr = trim($data['qr']);

include_once __DIR__ . '/../modelo/Egresado.php';
$eg = new Egresado();

// Primero intentamos por identificacion
// Buscar por identificacion
$result = $eg->Buscar($qr);
if (empty($result)) {
    // Intentar por carnet
    $result = $eg->BuscarPorCarnet($qr);
}

if (!empty($result)) {
    // Tomar el primer egresado encontrado
    $egresado = $result[0];
    // Obtener títulos asociados
    $titulos = $eg->ObtenerTitulosPorIdentificacion($egresado['identificacion']);
    echo json_encode(['status' => 'ok', 'data' => ['egresado' => $egresado, 'titulos' => $titulos]]);
} else {
    echo json_encode(['status' => 'notfound']);
}

?>