<?php
require_once __DIR__ . '/../modelo/Egresado.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$idExpediente = $_POST['id_expediente'] ?? null;
if (empty($idExpediente)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Falta el identificador del expediente']);
    exit;
}

function normalizeDateInput(?string $value): ?string
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

$fields = [
    'nombre' => trim($_POST['nombre'] ?? ''),
    'rut' => trim($_POST['rut'] ?? ''),
    'titulo' => trim($_POST['titulo'] ?? ''),
    'numero_certificado' => trim($_POST['numero_certificado'] ?? ''),
    'correo' => trim($_POST['correo'] ?? ''),
    'sexo' => trim($_POST['sexo'] ?? ''),
    'gestion' => trim($_POST['gestion'] ?? ''),
];

$fechaEgreso = trim($_POST['fecha_egreso'] ?? '');
if (!empty($fechaEgreso)) {
    if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', $fechaEgreso, $m)) {
        $fields['anio_egreso'] = $m[1];
    } elseif (preg_match('/^\d{4}$/', $fechaEgreso)) {
        $fields['anio_egreso'] = $fechaEgreso;
    }
}

$fields['fecha_entrega'] = normalizeDateInput($_POST['fecha_entrega'] ?? '') ?? normalizeDateInput($fechaEgreso) ?? null;

$fields = array_filter($fields, function ($value) {
    return $value !== null && $value !== '';
});

if (empty($fields)) {
    echo json_encode(['success' => true, 'mensaje' => 'No hubo cambios que guardar']);
    exit;
}

try {
    $egresado = new Egresado();
    $egresado->ActualizarDatosCertificado($idExpediente, $fields);

    if (isset($fields['fecha_entrega'])) {
        $egresado->ActualizarFechaManual($idExpediente, $fields['fecha_entrega']);
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Datos actualizados correctamente',
        'fields' => $fields,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron guardar los cambios: ' . $e->getMessage(),
    ]);
}
