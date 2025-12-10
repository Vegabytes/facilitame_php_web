<?php
/**
 * API: Obtener mensajes del chat de una cita (Asesoría)
 * GET /api-advisory-appointment-chat-messages?appointment_id=X
 */

global $pdo;

if (!asesoria()) {
    json_response("ko", "Acceso denegado", 403);
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if (!$appointment_id) {
    json_response("ko", "ID de cita requerido", 400);
}

try {
    // Obtener advisory_id del usuario actual
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 404);
    }
    
    // Verificar que la cita pertenece a esta asesoría (query simplificada)
    $stmt = $pdo->prepare("
        SELECT customer_id 
        FROM advisory_appointments 
        WHERE id = ? AND advisory_id = ?
    ");
    $stmt->execute([$appointment_id, $advisory['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada", 404);
    }
    
    // Obtener mensajes
    $messages = get_appointment_chat_messages($appointment_id);
    
    // Marcar como leídos los mensajes del cliente
    $marked = mark_appointment_messages_read($appointment_id, 'advisory');
    
    json_response("ok", "Mensajes obtenidos", 200, [
        'messages' => $messages,
        'marked_as_read' => $marked
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-appointment-chat-messages: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
