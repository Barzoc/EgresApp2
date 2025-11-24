<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/Utils.php';
require_once __DIR__ . '/../modelo/Egresado.php';
require_once __DIR__ . '/../modelo/Conexion.php';
require_once __DIR__ . '/../lib/CertificatePdfBuilder.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$rut = trim($_POST['rut'] ?? '');
if (empty($rut) || !Utils::validarRut($rut)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'RUT inválido']);
    exit;
}

try {
    $egresadoModel = new Egresado();
    $registro = $egresadoModel->ObtenerDatosCertificadoPorRut($rut);

    if (!$registro) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el RUT proporcionado.']);
        exit;
    }

    $titulo = $registro['tituloObtenido']
        ?? $registro['titulo_nombre']
        ?? $registro['titulo_catalogo']
        ?? '';

    $fechaTitulo = $registro['fechaGrado']
        ?? ($registro['fecha_grado'] ?? $registro['fechaEntregaCertificado'] ?? null);

    $numeroRegistro = $registro['numeroCertificado']
        ?? ($registro['numero_documento'] ?? '');

    $config = is_file(__DIR__ . '/../config/certificado.php')
        ? require __DIR__ . '/../config/certificado.php'
        : [];

    $builder = new CertificatePdfBuilder();
    $pdfContent = $builder->build([
        'nombre' => $registro['nombreCompleto'] ?? '',
        'rut' => $registro['carnet'] ?? $rut,
        'titulo' => $titulo,
        'fecha_titulo' => $fechaTitulo,
        'numero_registro' => $numeroRegistro,
        'fecha_emision' => date('Y-m-d'),
    ], [
        'rector' => $config['rector'] ?? 'RECTOR(A)',
    ]);

    $certDir = realpath(__DIR__ . '/../certificados');
    if ($certDir === false) {
        $certDir = __DIR__ . '/../certificados';
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }
    }

    $cleanRut = preg_replace('/[^0-9kK]/', '', $registro['carnet'] ?? $rut);
    $filename = sprintf('cert_%s_%s.pdf', $cleanRut ?: 'egresado', date('YmdHis'));
    $filePath = rtrim($certDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($filePath, $pdfContent) === false) {
        throw new RuntimeException('No se pudo guardar el PDF en el servidor.');
    }

    try {
        $conexion = new Conexion();
        $pdo = $conexion->pdo;
        $stmt = $pdo->prepare('UPDATE tituloegresado SET rutaCertificado = :ruta WHERE identificacion = :id LIMIT 1');
        $stmt->execute([
            ':ruta' => $filename,
            ':id' => $registro['identificacion'] ?? 0,
        ]);
    } catch (Throwable $e) {
        // No bloquear por errores en la actualización de la ruta
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $basePath = rtrim(preg_replace('#/controlador/[^/]+$#', '', $scriptName), '/');
    if ($basePath === '') {
        $relativeUrl = '/certificados/' . $filename;
    } else {
        $relativeUrl = $basePath . '/certificados/' . $filename;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $absoluteUrl = $scheme . '://' . $host . $relativeUrl;

    echo json_encode([
        'success' => true,
        'message' => 'Certificado generado correctamente.',
        'url' => $absoluteUrl,
        'path' => $relativeUrl,
        'filename' => $filename,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el certificado: ' . $e->getMessage(),
    ]);
}
