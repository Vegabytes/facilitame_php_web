<?php
/**
 * API: Enviar mensaje en el chat de una cita (Cliente)
 * POST /api-customer-appointment-chat-send
 */

global $pdo;

if (!cliente()) {
    json_response("ko", "Acceso denegado", 403);
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$appointment_id || empty($message)) {
    json_response("ko", "Datos incompletos", 400);
}

if (mb_strlen($message) > 5000) {
    json_response("ko", "El mensaje es demasiado largo (máximo 5000 caracteres)", 400);
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
    
    // Enviar mensaje
    $result = send_appointment_message_from_customer(
        $appointment_id, 
        $appointment['advisory_id'], 
        USER['id'], 
        $message
    );
    
    if ($result['status'] === 'ok') {
        // Registrar en historial
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'customer',
            'message_sent',
            null,
            null,
            null,
            'Mensaje enviado en el chat'
        );

        // Generar notificación para la asesoría
        $stmt = $pdo->prepare("SELECT user_id FROM advisories WHERE id = ?");
        $stmt->execute([$appointment['advisory_id']]);
        $advisory_user = $stmt->fetch();

        if ($advisory_user) {
            notification(
                USER['id'],                      // sender_id (cliente)
                $advisory_user['user_id'],       // receiver_id (usuario de la asesoría)
                null,                            // request_id (no aplica para citas)
                'Nuevo mensaje de cita',
                USER['name'] . ' ' . USER['lastname'] . ' te ha enviado un mensaje.'
            );
        }

        json_response("ok", "Mensaje enviado", 200, [
            'message_id' => $result['message_id']
        ]);
    } else {
        json_response("ko", "Error al enviar mensaje", 500);
    }
    
} catch (Throwable $e) {
    error_log("Error en api-customer-appointment-chat-send: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
