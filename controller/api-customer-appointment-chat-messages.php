<?php
/**
 * API: Obtener mensajes del chat de una cita (Cliente)
 * GET /api-customer-appointment-chat-messages?appointment_id=X
 */

global $pdo;

if (!cliente()) {
    json_response("ko", "Acceso denegado", 403);
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if (!$appointment_id) {
    json_response("ko", "ID de cita requerido", 400);
}

try {
    // Verificar que la cita pertenece a este cliente
    $stmt = $pdo->prepare("
        SELECT advisory_id 
        FROM advisory_appointments 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$appointment_id, USER['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada", 404);
    }
    
    // Obtener mensajes
    $messages = get_appointment_chat_messages($appointment_id);
    
    // Marcar como leídos los mensajes de la asesoría
    $marked = mark_appointment_messages_read($appointment_id, 'customer');
    
    json_response("ok", "Mensajes obtenidos", 200, [
        'messages' => $messages,
        'marked_as_read' => $marked
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-appointment-chat-messages: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
