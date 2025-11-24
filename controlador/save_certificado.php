<?php
// save_certificado.php
// Recibe un PDF en base64 desde el cliente y lo guarda en /certificados/
require_once __DIR__ . '/../modelo/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$rut = $_POST['rut'] ?? null;
$pdf_base64 = $_POST['pdf_base64'] ?? null;
$filename = $_POST['filename'] ?? null; // opcional

if (!$rut || !$pdf_base64) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros (rut / pdf_base64)']);
    exit;
}

// Extraer base64 puro si viene con prefix data:application/pdf;base64,
if (strpos($pdf_base64, 'base64,') !== false) {
    $parts = explode('base64,', $pdf_base64);
    $pdf_base64 = $parts[1];
}

$pdf_bin = base64_decode($pdf_base64);
if ($pdf_bin === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Base64 inválido']);
    exit;
}

// Sanear rut para usar en filename
$clean = preg_replace('/[^0-9kK]/', '', $rut);
if (!$filename) {
    $filename = $clean . '_' . time() . '.pdf';
} else {
    // asegurar extensión .pdf
    if (substr(strtolower($filename), -4) !== '.pdf') $filename .= '.pdf';
}

$dir = __DIR__ . '/../certificados/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$path = $dir . $filename;
$written = file_put_contents($path, $pdf_bin);
if ($written === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo escribir el archivo en servidor']);
    exit;
}

// Intentar actualizar tabla tituloegresado para guardar la ruta (opcional)
try {
    $db = new Conexion();
    $pdo = $db->pdo;
    // obtener identificacion del egresado
    $sql = "SELECT identificacion FROM egresado WHERE REPLACE(REPLACE(UPPER(carnet),'.',''),'-','') = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtoupper($clean)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['identificacion'])) {
        $ident = $row['identificacion'];
        // intentar seleccionar un registro de tituloegresado
        $sql2 = "SELECT id FROM tituloegresado WHERE identificacion = ? LIMIT 1";
        $s2 = $pdo->prepare($sql2);
        $s2->execute([$ident]);
        $r2 = $s2->fetch(PDO::FETCH_ASSOC);
        if ($r2 && isset($r2['id'])) {
            $id_te = $r2['id'];
            $update = $pdo->prepare("UPDATE tituloegresado SET rutaCertificado = ? WHERE id = ?");
            $update->execute([$filename, $id_te]);
        }
    }
} catch (Exception $e) {
    // No bloquear por error en la actualización, sólo devolver el link
}

$public_url = './certificados/' . $filename;

echo json_encode(['success' => true, 'url' => $public_url, 'filename' => $filename]);
exit;
