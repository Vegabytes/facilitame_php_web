<?php
/**
 * API: Confirmar cita (Cliente) - v2
 * POST /api-customer-confirm-appointment
 * 
 * El cliente confirma la fecha propuesta por la asesoría.
 * La cita pasa a estado 'agendado' con scheduled_date = proposed_date.
 */
global $pdo;

if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

try {
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    
    if (!$appointment_id) {
        json_response("ko", "ID de cita requerido", 400);
    }
    
    // Obtener la cita verificando que pertenece al cliente
    $stmt = $pdo->prepare("
        SELECT * FROM advisory_appointments 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$appointment_id, USER['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada o sin permisos", 404);
    }
    
    // Verificar que necesita confirmación del cliente
    if ($appointment['needs_confirmation_from'] !== 'customer') {
        json_response("ko", "Esta cita no requiere tu confirmación", 400);
    }
    
    // Verificar que tiene fecha propuesta
    if (empty($appointment['proposed_date'])) {
        json_response("ko", "La cita no tiene fecha propuesta para confirmar", 400);
    }
    
    // Verificar estado válido para confirmar
    if (!in_array($appointment['status'], ['solicitado', 'agendado'])) {
        json_response("ko", "No se puede confirmar una cita en estado: " . $appointment['status'], 400);
    }
    
    // Confirmar: mover proposed_date a scheduled_date, cambiar estado a agendado
    $stmt = $pdo->prepare("
        UPDATE advisory_appointments 
        SET scheduled_date = proposed_date,
            status = 'agendado',
            needs_confirmation_from = NULL,
            confirmed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$appointment_id]);
    
    // Registrar en historial
    if (function_exists('log_appointment_change')) {
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'customer',
            'confirmed',
            'status',
            $appointment['status'],
            'agendado',
            'Cliente confirmó la fecha propuesta por la asesoría'
        );
    }
    
    // Enviar email a la asesoría notificando la confirmación
    if (function_exists('send_appointment_email')) {
        send_appointment_email($appointment_id, 'confirmed', 'advisory');
    }

    // Generar notificación para la asesoría
    $stmt = $pdo->prepare("SELECT user_id FROM advisories WHERE id = ?");
    $stmt->execute([$appointment['advisory_id']]);
    $advisory_user = $stmt->fetch();

    if ($advisory_user) {
        notification(
            USER['id'],                      // sender_id (cliente)
            $advisory_user['user_id'],       // receiver_id (usuario de la asesoría)
            null,                            // request_id (no aplica para citas)
            'Cita confirmada',
            USER['name'] . ' ' . USER['lastname'] . ' ha confirmado la cita del ' . date('d/m/Y H:i', strtotime($appointment['proposed_date'])) . '.'
        );
    }

    json_response("ok", "¡Cita confirmada! La fecha ha sido agendada.", 200, [
        'scheduled_date' => $appointment['proposed_date']
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-confirm-appointment: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", "Error interno del servidor", 500);
}