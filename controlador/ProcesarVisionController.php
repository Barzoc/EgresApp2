<?php
// Controlador sencillo para recibir una imagen en base64 y consultar Google Cloud Vision
header('Content-Type: application/json; charset=utf-8');
// Opcional: permitir peticiones desde mismo origen/local (ajustar si es necesario)
// header('Access-Control-Allow-Origin: http://localhost');
// header('Access-Control-Allow-Methods: POST');
// header('Access-Control-Allow-Headers: Content-Type');

session_start();
// Verificar sesión: permitir sólo usuarios autenticados
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Sesión no iniciada']);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No image provided']);
    exit;
}

$image_b64 = $data['image'];

// Limpiar el prefijo si existe
if (strpos($image_b64, 'data:') === 0) {
    $parts = explode(',', $image_b64, 2);
    if (count($parts) === 2) $image_b64 = $parts[1];
}

$image_bin = base64_decode($image_b64);
if ($image_bin === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 image']);
    exit;
}

// Security: limit size to ~3MB
if (strlen($image_bin) > 3 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'Image too large']);
    exit;
}

$cfgPath = __DIR__ . '/../config/google_vision.php';
if (!file_exists($cfgPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config not found']);
    exit;
}

$cfg = include $cfgPath;
if (empty($cfg['api_key']) || $cfg['api_key'] === 'REEMPLAZA_POR_TU_API_KEY') {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured. Edit config/google_vision.php']);
    exit;
}

$apiKey = $cfg['api_key'];

$endpoint = 'https://vision.googleapis.com/v1/images:annotate?key=' . urlencode($apiKey);

$body = [
    'requests' => [
        [
            'image' => [
                'content' => base64_encode($image_bin)
            ],
            'features' => [
                ['type' => 'LABEL_DETECTION', 'maxResults' => 10],
                ['type' => 'TEXT_DETECTION', 'maxResults' => 10],
                ['type' => 'WEB_DETECTION', 'maxResults' => 5]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

$response = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    http_response_code(502);
    echo json_encode(['error' => 'Request error', 'details' => $error, 'curl_errno' => $errno]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    // Intentar decodificar el body para dar info legible
    $bodyDecoded = json_decode($response, true);
    echo json_encode(['error' => 'API error', 'http_code' => $httpCode, 'body' => $bodyDecoded ? $bodyDecoded : $response]);
    exit;
}

// Pasar la respuesta directamente (ya es JSON)
echo $response;

exit;

?>
