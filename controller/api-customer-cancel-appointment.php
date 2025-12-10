<?php
/**
 * API: Cancelar cita (Cliente)
 * POST /api-customer-cancel-appointment
 */

global $pdo;

if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

try {
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : null;
    
    if (!$appointment_id) {
        json_response("ko", "ID de cita requerido", 400);
    }
    
    // Verificar que la cita pertenece al cliente
    $stmt = $pdo->prepare("SELECT * FROM advisory_appointments WHERE id = ? AND customer_id = ?");
    $stmt->execute([$appointment_id, USER['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada", 404);
    }
    
    // Verificar que se puede cancelar
    if (!can_cancel_appointment($appointment)) {
        json_response("ko", "Esta cita no puede ser cancelada", 400);
    }
    
    // Actualizar
    $stmt = $pdo->prepare("
        UPDATE advisory_appointments 
        SET status = 'cancelado',
            cancelled_by = 'customer',
            cancelled_at = NOW(),
            cancellation_reason = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$cancellation_reason, $appointment_id]);
    
    // Registrar en historial
    log_appointment_change(
        $appointment_id,
        USER['id'],
        'customer',
        'cancelled',
        null,
        $appointment['status'],
        'cancelado',
        $cancellation_reason
    );
    
    // Enviar email a la asesoría
    send_appointment_email($appointment_id, 'cancelled', 'advisory');

    // Generar notificación para la asesoría
    $stmt = $pdo->prepare("SELECT user_id FROM advisories WHERE id = ?");
    $stmt->execute([$appointment['advisory_id']]);
    $advisory_user = $stmt->fetch();

    if ($advisory_user) {
        notification(
            USER['id'],                      // sender_id (cliente)
            $advisory_user['user_id'],       // receiver_id (usuario de la asesoría)
            null,                            // request_id (no aplica para citas)
            'Cita cancelada',
            USER['name'] . ' ' . USER['lastname'] . ' ha cancelado su cita.'
        );
    }

    json_response("ok", "Cita cancelada correctamente", 200);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-cancel-appointment: " . $e->getMessage());
    json_response("ko", "Error interno del servidor", 500);
}
