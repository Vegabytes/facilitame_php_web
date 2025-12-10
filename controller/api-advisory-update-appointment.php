<?php
/**
 * API: Actualizar cita (Asesoria) v2
 * POST /api-advisory-update-appointment
 * 
 * Acciones v2:
 * - action=accept_proposal: Acepta propuesta del cliente (proposed_date -> scheduled_date)
 * - action=reschedule: Propone nueva fecha (cliente debe confirmar)
 * - action=finalize: Finaliza cita confirmada
 * - action=cancel: Cancela cita
 * 
 * Tambien soporta edicion de campos (type, department, notes, etc.)
 */

global $pdo;

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

try {
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : null;
    
    if (!$appointment_id) {
        json_response("ko", "ID de cita requerido", 400);
    }
    
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoria no encontrada", 404);
    }
    
    // Verificar que la cita pertenece a esta asesoria
    $stmt = $pdo->prepare("SELECT * FROM advisory_appointments WHERE id = ? AND advisory_id = ?");
    $stmt->execute([$appointment_id, $advisory['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada o sin permisos", 404);
    }
    
    $updates = [];
    $params = [];
    $changes = [];
    $email_type = null;
    
    // =========================================
    // ACCIONES v2
    // =========================================
    
    if ($action === 'accept_proposal') {
        // Aceptar propuesta del cliente: mover proposed_date a scheduled_date
        if ($appointment['needs_confirmation_from'] !== 'advisory') {
            json_response("ko", "Esta cita no tiene una propuesta pendiente de tu confirmacion", 400);
        }
        
        if (empty($appointment['proposed_date'])) {
            json_response("ko", "No hay fecha propuesta para aceptar", 400);
        }
        
        $updates[] = "scheduled_date = proposed_date";
        $updates[] = "status = 'agendado'";
        $updates[] = "needs_confirmation_from = NULL";
        $updates[] = "confirmed_at = NOW()";
        
        $changes[] = [
            'action' => 'proposal_accepted',
            'field' => 'scheduled_date',
            'old' => $appointment['scheduled_date'],
            'new' => $appointment['proposed_date'],
            'notes' => 'Asesoria acepto propuesta del cliente'
        ];
        
        $email_type = 'confirmed';
        
    } elseif ($action === 'reschedule') {
        // Proponer nueva fecha (cliente debe confirmar)
        $proposed_date = isset($_POST['proposed_date']) ? trim($_POST['proposed_date']) : '';
        
        if (empty($proposed_date)) {
            json_response("ko", "Debes indicar la nueva fecha propuesta", 400);
        }
        
        // Validar fecha futura
        $proposed_timestamp = strtotime($proposed_date);
        if ($proposed_timestamp === false || $proposed_timestamp <= time()) {
            json_response("ko", "La fecha propuesta debe ser en el futuro", 400);
        }
        
        $proposed_date_mysql = date('Y-m-d H:i:s', $proposed_timestamp);
        
        $updates[] = "proposed_date = ?";
        $params[] = $proposed_date_mysql;
        $updates[] = "proposed_by = 'advisory'";
        $updates[] = "needs_confirmation_from = 'customer'";
        // Si estaba agendada, volver a solicitado
        if ($appointment['status'] === 'agendado') {
            $updates[] = "status = 'solicitado'";
            $updates[] = "scheduled_date = NULL";
        }
        
        $changes[] = [
            'action' => 'rescheduled',
            'field' => 'proposed_date',
            'old' => $appointment['proposed_date'] ?? $appointment['scheduled_date'],
            'new' => $proposed_date_mysql,
            'notes' => 'Asesoria propuso nueva fecha'
        ];
        
        $email_type = 'proposal';
        
    } elseif ($action === 'finalize') {
        // Finalizar cita
        if ($appointment['status'] !== 'agendado') {
            json_response("ko", "Solo se pueden finalizar citas confirmadas", 400);
        }
        
        $updates[] = "status = 'finalizado'";
        
        $changes[] = [
            'action' => 'finalized',
            'field' => 'status',
            'old' => 'agendado',
            'new' => 'finalizado'
        ];
        
        $email_type = 'finalized';
        
    } elseif ($action === 'cancel') {
        // Cancelar cita
        if (!in_array($appointment['status'], ['solicitado', 'agendado'])) {
            json_response("ko", "Esta cita no se puede cancelar", 400);
        }
        
        $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : null;
        
        $updates[] = "status = 'cancelado'";
        $updates[] = "cancelled_by = 'advisory'";
        $updates[] = "cancelled_at = NOW()";
        $updates[] = "needs_confirmation_from = NULL";
        
        if ($cancellation_reason) {
            $updates[] = "cancellation_reason = ?";
            $params[] = $cancellation_reason;
        }
        
        $changes[] = [
            'action' => 'cancelled',
            'field' => 'status',
            'old' => $appointment['status'],
            'new' => 'cancelado',
            'notes' => $cancellation_reason
        ];
        
        $email_type = 'cancelled';
        
    } elseif (isset($_POST['status'])) {
        // =========================================
        // CAMBIO DE ESTADO LEGACY (compatibilidad)
        // =========================================
        $new_status = trim($_POST['status']);
        $current_status = $appointment['status'];
        
        // Validar transiciones permitidas
        $allowed = get_allowed_status_transitions($current_status);
        
        if (!in_array($new_status, $allowed)) {
            json_response("ko", "Transicion de estado no permitida de '{$current_status}' a '{$new_status}'", 400);
        }
        
        $updates[] = "status = ?";
        $params[] = $new_status;
        
        $changes[] = [
            'action' => 'status_changed',
            'field' => 'status',
            'old' => $current_status,
            'new' => $new_status
        ];
        
        // Logica especifica por estado (legacy)
        if ($new_status === 'agendado' && $current_status === 'solicitado') {
            if (empty($_POST['scheduled_date'])) {
                json_response("ko", "Fecha requerida para agendar", 400);
            }
            $scheduled_date = trim($_POST['scheduled_date']);
            $updates[] = "scheduled_date = ?";
            $params[] = $scheduled_date;
            $updates[] = "needs_confirmation_from = NULL";
            
            $changes[] = [
                'action' => 'scheduled',
                'field' => 'scheduled_date',
                'old' => $appointment['scheduled_date'],
                'new' => $scheduled_date
            ];
            
            $email_type = 'scheduled';
            
        } elseif ($new_status === 'cancelado') {
            $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : null;
            
            $updates[] = "cancelled_by = 'advisory'";
            $updates[] = "cancelled_at = NOW()";
            $updates[] = "needs_confirmation_from = NULL";
            
            if ($cancellation_reason) {
                $updates[] = "cancellation_reason = ?";
                $params[] = $cancellation_reason;
            }
            
            $changes[] = [
                'action' => 'cancelled',
                'field' => null,
                'old' => null,
                'new' => null,
                'notes' => $cancellation_reason
            ];
            
            $email_type = 'cancelled';
            
        } elseif ($new_status === 'finalizado') {
            $email_type = 'finalized';
            
        } elseif ($new_status === 'solicitado' && $current_status === 'cancelado') {
            $updates[] = "cancelled_by = NULL";
            $updates[] = "cancelled_at = NULL";
            $updates[] = "cancellation_reason = NULL";
            
            $changes[] = [
                'action' => 'reactivated',
                'field' => null,
                'old' => 'cancelado',
                'new' => 'solicitado'
            ];
        }
    }
    
    // =========================================
    // EDICION DE CAMPOS
    // =========================================
    
    // Tipo de cita
    if (isset($_POST['type']) && !empty($_POST['type'])) {
        $new_type = trim($_POST['type']);
        $valid_types = ['llamada', 'reunion_presencial', 'reunion_virtual'];
        
        if (!in_array($new_type, $valid_types)) {
            json_response("ko", "Tipo de cita no valido", 400);
        }
        
        if ($new_type !== $appointment['type']) {
            $updates[] = "type = ?";
            $params[] = $new_type;
            
            $changes[] = [
                'action' => 'edited',
                'field' => 'type',
                'old' => $appointment['type'],
                'new' => $new_type
            ];
        }
    }
    
    // Departamento
    if (isset($_POST['department']) && !empty($_POST['department'])) {
        $new_dept = trim($_POST['department']);
        $valid_depts = ['contabilidad', 'fiscalidad', 'laboral', 'gestion'];
        
        if (!in_array($new_dept, $valid_depts)) {
            json_response("ko", "Departamento no valido", 400);
        }
        
        if ($new_dept !== $appointment['department']) {
            $updates[] = "department = ?";
            $params[] = $new_dept;
            
            $changes[] = [
                'action' => 'edited',
                'field' => 'department',
                'old' => $appointment['department'],
                'new' => $new_dept
            ];
        }
    }
    
    // Notas de asesoria
    if (isset($_POST['notes_advisory'])) {
        $new_notes = trim($_POST['notes_advisory']);
        
        if ($new_notes !== ($appointment['notes_advisory'] ?? '')) {
            $updates[] = "notes_advisory = ?";
            $params[] = $new_notes;
            
            $changes[] = [
                'action' => 'notes_updated',
                'field' => 'notes_advisory',
                'old' => $appointment['notes_advisory'],
                'new' => $new_notes
            ];
        }
    }
    
    // Compatibilidad con 'notes' (alias)
    if (isset($_POST['notes']) && !isset($_POST['notes_advisory'])) {
        $new_notes = trim($_POST['notes']);
        
        if ($new_notes !== ($appointment['notes_advisory'] ?? '')) {
            $updates[] = "notes_advisory = ?";
            $params[] = $new_notes;
            
            $changes[] = [
                'action' => 'notes_updated',
                'field' => 'notes_advisory',
                'old' => $appointment['notes_advisory'],
                'new' => $new_notes
            ];
        }
    }
    
    // Motivo
    if (isset($_POST['reason'])) {
        $new_reason = trim($_POST['reason']);
        
        if ($new_reason !== ($appointment['reason'] ?? '')) {
            $updates[] = "reason = ?";
            $params[] = $new_reason;
            
            $changes[] = [
                'action' => 'edited',
                'field' => 'reason',
                'old' => $appointment['reason'],
                'new' => $new_reason
            ];
        }
    }
    
    // =========================================
    // EJECUTAR ACTUALIZACION
    // =========================================
    
    if (empty($updates)) {
        json_response("ko", "No hay cambios para aplicar", 400);
    }
    
    // Anadir updated_at
    $updates[] = "updated_at = NOW()";
    $params[] = $appointment_id;
    
    $sql = "UPDATE advisory_appointments SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // =========================================
    // REGISTRAR EN HISTORIAL
    // =========================================
    
    foreach ($changes as $change) {
        log_appointment_change(
            $appointment_id,
            USER['id'],
            'advisory',
            $change['action'],
            $change['field'] ?? null,
            $change['old'] ?? null,
            $change['new'] ?? null,
            $change['notes'] ?? null
        );
    }
    
    // =========================================
    // ENVIAR EMAIL
    // =========================================
    
    if ($email_type) {
        send_appointment_email($appointment_id, $email_type, 'customer');
    }
    
    json_response("ok", "Cita actualizada correctamente", 200, [
        'changes' => count($changes),
        'email_sent' => $email_type !== null
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-update-appointment: " . $e->getMessage() . " linea " . $e->getLine());
    json_response("ko", "Error interno del servidor", 500);
}