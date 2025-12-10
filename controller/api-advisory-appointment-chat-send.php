<?php
/**
 * API: Enviar mensaje en el chat de una cita (Asesoría)
 * POST /api-advisory-appointment-chat-send
 */

global $pdo;

if (!asesoria()) {
    json_response("ko", "Acceso denegado", 403);
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$appointment_id || empty($message)) {
    json_response("ko", "Datos incompletos", 400);
}

// Validar longitud del mensaje
if (mb_strlen($message) > 5000) {
    json_response("ko", "El mensaje es demasiado largo (máximo 5000 caracteres)", 400);
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
    
    // Enviar mensaje
    $result = send_appointment_message_from_advisory(
        $appointment_id, 
        $advisory['id'], 
        $appointment['customer_id'], 
        $message
    );
    
    if ($result['status'] === 'ok') {
        // Registrar en historial
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'advisory',
            'message_sent',
            null,
            null,
            null,
            'Mensaje enviado en el chat'
        );
        
        json_response("ok", "Mensaje enviado", 200, [
            'message_id' => $result['message_id']
        ]);
    } else {
        json_response("ko", "Error al enviar mensaje", 500);
    }
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-appointment-chat-send: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
