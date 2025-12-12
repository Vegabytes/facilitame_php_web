<?php
/**
 * API: Desconectar Google Calendar
 * POST /api/google-calendar-disconnect
 */

if (!isset(USER['id'])) {
    json_response('ko', 'No autorizado', 401);
}

require_once ROOT_DIR . '/bold/classes/GoogleCalendarClient.php';

try {
    $client = new GoogleCalendarClient(USER['id']);
    $client->disconnect();

    app_log('google_calendar', USER['id'], 'disconnect', 'user', USER['id']);

    json_response('ok', 'Google Calendar desconectado');

} catch (Exception $e) {
    json_response('ko', 'Error al desconectar: ' . $e->getMessage());
}
