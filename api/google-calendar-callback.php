<?php
/**
 * API: Callback de Google Calendar OAuth
 * GET /api/google-calendar-callback
 *
 * Google redirige aquí después de que el usuario autorice
 */

require_once ROOT_DIR . '/bold/classes/GoogleCalendarClient.php';

// Verificar que no hay error
if (isset($_GET['error'])) {
    $errorMsg = urlencode('No se pudo conectar con Google Calendar: ' . $_GET['error']);
    header('Location: /settings?error=' . $errorMsg);
    exit;
}

// Verificar que hay código
if (!isset($_GET['code'])) {
    header('Location: /settings?error=' . urlencode('No se recibió autorización de Google'));
    exit;
}

// Recuperar state
$state = [];
if (isset($_GET['state'])) {
    $state = json_decode(base64_decode($_GET['state']), true) ?: [];
}

$userId = $state['user_id'] ?? null;
$returnUrl = $state['return_url'] ?? '/settings';

if (!$userId) {
    header('Location: /login?error=' . urlencode('Sesión inválida'));
    exit;
}

try {
    $client = new GoogleCalendarClient();

    // Intercambiar código por tokens
    $tokens = $client->exchangeCode($_GET['code']);

    // Guardar tokens
    $client->saveTokens($userId, $tokens);

    // Registrar en log
    app_log('google_calendar', $userId, 'connect', 'user', $userId);

    // Redirigir con éxito
    $successMsg = urlencode('Google Calendar conectado correctamente');
    header('Location: ' . $returnUrl . '?success=' . $successMsg);
    exit;

} catch (Exception $e) {
    $errorMsg = urlencode('Error al conectar: ' . $e->getMessage());
    header('Location: ' . $returnUrl . '?error=' . $errorMsg);
    exit;
}
