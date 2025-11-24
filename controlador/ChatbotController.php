<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Simple helper for JSON responses
function respond($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    respond([
        'history' => $_SESSION['chat_history'],
    ]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$message = trim($input['message'] ?? '');
$isBoot = !empty($input['boot']);

if ($message === '' && !$isBoot) {
    respond(['error' => 'Mensaje vacío'], 400);
}

$userName = $_SESSION['nombre'] ?? ($_SESSION['email'] ?? 'usuario');
$userEmail = $_SESSION['email'] ?? 'correo@no-disponible';

$systemPrompt = <<<PROMPT
Eres "EgresApp Assistant", un asistente local que guía paso a paso a los administradores que usan el sistema "EgresApp2" para gestionar egresados.

Tareas que conoces:
- Iniciar sesión solicitando correo y contraseña o usando el RUT desde la pantalla principal.
- Agregar egresados desde el menú "Añadir Egresados" (> botón "Crear egresado").
- Cargar títulos desde "Añadir Títulos".
- Subir expedientes PDF y generar certificados en "Añadir Egresados" (botones "Subir expediente" y "Generar certificado").
- Consultar estadísticas en el módulo "Estadísticas" (gráficos de género, títulos, etc.).
- Exportar reportes (Excel/PDF) desde las tablas de egresados.

Instrucciones de estilo:
1. Siempre ofrece respuestas claras, ordenadas y breves, usando pasos numerados cuando corresponda.
2. Si el usuario está recién ingresando, ofrécele ayuda para consultar certificados o recuperar su clave.
3. Cuando puedas llevarlo a una sección, agrega enlaces en formato Markdown con la ruta interna, por ejemplo: [Ir a Añadir Egresados](internal://adm_egresado.php).
4. Si no conoces la respuesta, dilo con honestidad y sugiere contactarse con soporte.
5. Responde en español y mantén un tono cordial y profesional.
PROMPT;

$history = $_SESSION['chat_history'];
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
];

foreach ($history as $entry) {
    $messages[] = $entry;
}

if ($isBoot) {
    $introContent = "Saluda al usuario {$userName} (correo {$userEmail}).\n" .
        "Ofrece ayuda inicial para consultar certificados o cualquier tarea dentro de la plataforma.";
    $messages[] = ['role' => 'user', 'content' => $introContent];
} else {
    $userEntry = [
        'role' => 'user',
        'content' => $message,
    ];
    $messages[] = $userEntry;
}

$model = getenv('OLLAMA_MODEL') ?: 'phi3:mini';
$ollamaUrl = rtrim(getenv('OLLAMA_URL') ?: 'http://127.0.0.1:11434', '/');

$payload = [
    'model' => $model,
    'stream' => false,
    'messages' => $messages,
];

$ch = curl_init($ollamaUrl . '/api/chat');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    respond(['error' => 'No se pudo conectar al modelo local: ' . $error], 500);
}

$data = json_decode($response, true);
if ($status >= 400 || !is_array($data)) {
    respond([
        'error' => 'Respuesta inválida del modelo local',
        'raw' => $response,
    ], 500);
}

$assistantMessage = $data['message']['content'] ?? '';
$assistantMessage = trim($assistantMessage);
if ($assistantMessage === '') {
    respond(['error' => 'El modelo no devolvió contenido'], 500);
}

if (!$isBoot) {
    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'content' => $message,
    ];
}

$_SESSION['chat_history'][] = [
    'role' => 'assistant',
    'content' => $assistantMessage,
];

respond([
    'reply' => $assistantMessage,
    'history' => $_SESSION['chat_history'],
]);
