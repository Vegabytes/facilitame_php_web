<?php
/**
 * API: Crear cita manualmente (Asesoria) v2
 * POST /api-advisory-create-appointment
 * 
 * Cambios v2:
 * - Usa proposed_date en lugar de scheduled_date
 * - Establece needs_confirmation_from='customer' (cliente debe confirmar)
 * - proposed_by='advisory'
 */
global $pdo;

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

try {
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $proposed_date = isset($_POST['proposed_date']) ? trim($_POST['proposed_date']) : null;
    $notes_advisory = isset($_POST['notes_advisory']) ? trim($_POST['notes_advisory']) : null;
    
    // Validaciones
    if (!$customer_id || !$type || !$department || !$reason) {
        json_response("ko", "Faltan campos obligatorios", 400);
    }
    
    // La fecha propuesta es obligatoria en v2
    if (empty($proposed_date)) {
        json_response("ko", "Debes proponer una fecha para la cita", 400);
    }
    
    // Validar fecha futura
    $proposed_timestamp = strtotime($proposed_date);
    if ($proposed_timestamp === false || $proposed_timestamp <= time()) {
        json_response("ko", "La fecha propuesta debe ser en el futuro", 400);
    }
    
    // Convertir a formato MySQL
    $proposed_date_mysql = date('Y-m-d H:i:s', $proposed_timestamp);
    
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoria no encontrada", 404);
    }
    
    // Verificar que el cliente pertenece a esta asesoria
    $stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
    $stmt->execute([$customer_id, $advisory['id']]);
    if (!$stmt->fetch()) {
        json_response("ko", "El cliente no pertenece a esta asesoria", 403);
    }
    
    // Validar valores
    $errors = validate_appointment_data([
        'type' => $type,
        'department' => $department
    ]);
    
    if (!empty($errors)) {
        json_response("ko", implode(', ', $errors), 400);
    }
    
    // Insertar con nuevos campos v2
    $stmt = $pdo->prepare("
        INSERT INTO advisory_appointments 
        (advisory_id, customer_id, type, department, reason, 
         proposed_date, proposed_by, needs_confirmation_from, 
         notes_advisory, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'advisory', 'customer', ?, 'solicitado', NOW())
    ");
    
    $stmt->execute([
        $advisory['id'],
        $customer_id,
        $type,
        $department,
        htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
        $proposed_date_mysql,
        $notes_advisory ? htmlspecialchars($notes_advisory, ENT_QUOTES, 'UTF-8') : null
    ]);
    
    $appointment_id = $pdo->lastInsertId();
    
    // Registrar en historial
    log_appointment_change(
        $appointment_id,
        USER['id'],
        'advisory',
        'created',
        null,
        null,
        null,
        'Cita creada por la asesoria'
    );
    
    log_appointment_change(
        $appointment_id,
        USER['id'],
        'advisory',
        'date_proposed',
        'proposed_date',
        null,
        $proposed_date_mysql,
        'Fecha propuesta al cliente'
    );
    
    // Enviar email al cliente (propuesta de fecha)
    send_appointment_email($appointment_id, 'proposal', 'customer');

    // Generar notificación para el cliente
    notification(
        USER['id'],                    // sender_id (asesoría)
        $customer_id,                  // receiver_id (cliente)
        null,                          // request_id (no aplica para citas)
        'Nueva propuesta de cita',
        'Tu asesoría te ha propuesto una cita para ' . date('d/m/Y H:i', $proposed_timestamp) . '. Accede para confirmar o proponer otra fecha.'
    );

    // Sincronizar con Google Calendar (si está conectado)
    syncAppointmentToGoogleCalendar($appointment_id, USER['id'], 'create');

    json_response("ok", "Cita creada. El cliente recibira la propuesta de fecha.", 200, [
        'appointment_id' => $appointment_id,
        'status' => 'solicitado',
        'needs_confirmation_from' => 'customer'
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-create-appointment: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}