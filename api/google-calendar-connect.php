<?php
/**
 * API: Iniciar conexión con Google Calendar
 * GET /api/google-calendar-connect
 *
 * Redirige al usuario a Google para autorizar la conexión
 */

if (!isset(USER['id'])) {
    header('Location: /login');
    exit;
}

require_once ROOT_DIR . '/bold/classes/GoogleCalendarClient.php';

$client = new GoogleCalendarClient();

// Guardar el user_id en el state para recuperarlo después
$state = base64_encode(json_encode([
    'user_id' => USER['id'],
    'return_url' => $_GET['return_url'] ?? '/settings'
]));

$authUrl = $client->getAuthUrl($state);

header('Location: ' . $authUrl);
exit;
