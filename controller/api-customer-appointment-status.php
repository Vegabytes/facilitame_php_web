<?php
/**
 * API: Estado de una cita específica (Cliente)
 * GET /api-customer-appointment-status?id=X
 * 
 * Obtiene el estado actual de una cita para detectar cambios
 * en tiempo real (confirmaciones, reprogramaciones, etc.)
 */
global $pdo;

if (!cliente()) {
    json_response("ko", "Acceso denegado", 403);
}

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    json_response("ko", "ID de cita requerido", 400);
}

try {
    $customer_id = USER['id'];
    
    // Obtener estado actual de la cita
    $stmt = $pdo->prepare("
        SELECT 
            id,
            status,
            scheduled_date,
            proposed_date,
            needs_confirmation_from,
            proposed_by,
            updated_at
        FROM advisory_appointments
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$appointment_id, $customer_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada", 404);
    }
    
    // Contar mensajes no leídos de asesoría
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM advisory_messages
        WHERE appointment_id = ?
        AND sender_type = 'advisory'
        AND (is_read = 0 OR is_read IS NULL)
    ");
    $stmt->execute([$appointment_id]);
    $unread = (int)$stmt->fetch()['cnt'];
    
    json_response("ok", "Estado obtenido", 200, [
        'appointment' => [
            'id' => (int)$appointment['id'],
            'status' => $appointment['status'],
            'scheduled_date' => $appointment['scheduled_date'],
            'proposed_date' => $appointment['proposed_date'],
            'needs_confirmation_from' => $appointment['needs_confirmation_from'],
            'proposed_by' => $appointment['proposed_by'],
            'updated_at' => $appointment['updated_at'],
            'unread_messages' => $unread
        ],
        'timestamp' => date('c')
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-appointment-status: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}