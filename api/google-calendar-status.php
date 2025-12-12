<?php
/**
 * API: Estado de conexión de Google Calendar
 * GET /api/google-calendar-status
 */

if (!isset(USER['id'])) {
    json_response('ko', 'No autorizado', 401);
}

require_once ROOT_DIR . '/bold/classes/GoogleCalendarClient.php';

try {
    $client = new GoogleCalendarClient(USER['id']);

    json_response('ok', '', 200, [
        'connected' => $client->isConnected()
    ]);

} catch (Exception $e) {
    json_response('ok', '', 200, [
        'connected' => false
    ]);
}
