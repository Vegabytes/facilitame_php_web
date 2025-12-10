<?php
/**
 * API: Solicitar cita (Cliente) - v2
 * POST /api-customer-request-appointment
 * 
 * Cambios v2:
 * - Cliente propone fecha/hora concreta (proposed_date)
 * - La asesoría debe confirmar (needs_confirmation_from = 'advisory')
 */
global $pdo;

if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

try {
    $advisory_id = isset($_POST['advisory_id']) ? intval($_POST['advisory_id']) : 0;
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $proposed_date = isset($_POST['proposed_date']) ? trim($_POST['proposed_date']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    // Validaciones básicas
    if (!$advisory_id || !$type || !$department || !$proposed_date || !$reason) {
        json_response("ko", "Faltan campos obligatorios", 400);
    }
    
    // Verificar que el cliente pertenece a esa asesoría
    $stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
    $stmt->execute([USER['id'], $advisory_id]);
    if (!$stmt->fetch()) {
        json_response("ko", "No perteneces a esta asesoría", 403);
    }
    
    // Validar valores permitidos
    $valid_types = ['llamada', 'reunion_presencial', 'reunion_virtual'];
    $valid_departments = ['contabilidad', 'fiscalidad', 'laboral', 'gestion'];
    
    if (!in_array($type, $valid_types)) {
        json_response("ko", "Tipo de cita no válido", 400);
    }
    
    if (!in_array($department, $valid_departments)) {
        json_response("ko", "Departamento no válido", 400);
    }
    
    // Validar fecha propuesta
    $proposed_datetime = strtotime($proposed_date);
    if (!$proposed_datetime) {
        json_response("ko", "Fecha propuesta no válida", 400);
    }
    
    // Verificar que la fecha es futura
    if ($proposed_datetime < time()) {
        json_response("ko", "La fecha propuesta debe ser futura", 400);
    }
    
    // Formatear fecha para MySQL
    $proposed_date_mysql = date('Y-m-d H:i:s', $proposed_datetime);
    
    // Validar longitud del motivo
    if (mb_strlen($reason) > 2000) {
        json_response("ko", "El motivo es demasiado largo (máximo 2000 caracteres)", 400);
    }
    
    // Insertar solicitud con nuevos campos v2
    $stmt = $pdo->prepare("
        INSERT INTO advisory_appointments 
        (advisory_id, customer_id, type, department, reason, 
         proposed_date, proposed_by, needs_confirmation_from, 
         status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'customer', 'advisory', 'solicitado', NOW(), NOW())
    ");
    
    $stmt->execute([
        $advisory_id,
        USER['id'],
        $type,
        $department,
        $reason,
        $proposed_date_mysql
    ]);
    
    $appointment_id = $pdo->lastInsertId();
    
    // Registrar en historial
    if (function_exists('log_appointment_change')) {
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'customer',
            'created',
            null,
            null,
            null,
            'Solicitud de cita creada por el cliente'
        );
        
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'customer',
            'date_proposed',
            'proposed_date',
            null,
            $proposed_date_mysql,
            'Cliente propone fecha/hora'
        );
    }
    
    // Enviar email a la asesoría
    if (function_exists('send_appointment_email')) {
        send_appointment_email($appointment_id, 'created', 'advisory');
    }

    // Generar notificación para la asesoría
    $stmt = $pdo->prepare("SELECT user_id FROM advisories WHERE id = ?");
    $stmt->execute([$advisory_id]);
    $advisory_user = $stmt->fetch();

    if ($advisory_user) {
        notification(
            USER['id'],                      // sender_id (cliente)
            $advisory_user['user_id'],       // receiver_id (usuario de la asesoría)
            null,                            // request_id (no aplica para citas)
            'Nueva solicitud de cita',
            USER['name'] . ' ' . USER['lastname'] . ' ha solicitado una cita para ' . date('d/m/Y H:i', $proposed_datetime) . '.'
        );
    }

    json_response("ok", "Solicitud de cita creada correctamente. La asesoría revisará tu propuesta de fecha.", 200, [
        'appointment_id' => $appointment_id
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-request-appointment: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", "Error interno del servidor", 500);
}