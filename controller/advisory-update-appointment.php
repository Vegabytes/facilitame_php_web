<?php
// api/advisory-update-appointment.php
if (!asesoria()) {
    json_response("ko", "No autorizado", 4031301);
}

global $pdo;

try {
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    
    if (!$appointment_id) {
        json_response("ko", "ID de cita requerido", 4001302);
    }
    
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 4041303);
    }
    
    // Verificar que la cita pertenece a esta asesoría
    $stmt = $pdo->prepare("SELECT * FROM advisory_appointments WHERE id = ? AND advisory_id = ?");
    $stmt->execute([$appointment_id, $advisory['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        json_response("ko", "Cita no encontrada o sin permisos", 4041304);
    }
    
    // Determinar qué actualizar
    $updates = [];
    $params = [];
    
    // Cambio de estado
    if (isset($_POST['status'])) {
        $new_status = trim($_POST['status']);
        
        // Validar transiciones de estado
        $allowed_transitions = [
            'solicitado' => ['agendado', 'cancelado'],
            'agendado' => ['finalizado', 'cancelado'],
            'finalizado' => [],
            'cancelado' => []
        ];
        
        $current_status = $appointment['status'];
        
        if (!isset($allowed_transitions[$current_status]) || !in_array($new_status, $allowed_transitions[$current_status])) {
            json_response("ko", "Transición de estado no permitida de '{$current_status}' a '{$new_status}'", 4001305);
        }
        
        $updates[] = "status = ?";
        $params[] = $new_status;
        
        // Si se agenda, requiere fecha
        if ($new_status === 'agendado' && $current_status === 'solicitado') {
            if (empty($_POST['scheduled_date'])) {
                json_response("ko", "Fecha requerida para agendar", 4001306);
            }
            $updates[] = "scheduled_date = ?";
            $params[] = trim($_POST['scheduled_date']);
        }
    }
    
    // Notas de asesoría
    if (isset($_POST['notes'])) {
        $updates[] = "notes_advisory = ?";
        $params[] = trim($_POST['notes']);
    }
    
    // Fecha programada (sin cambiar estado)
    if (!empty($_POST['scheduled_date']) && !isset($_POST['status'])) {
        $updates[] = "scheduled_date = ?";
        $params[] = trim($_POST['scheduled_date']);
    }
    
    if (empty($updates)) {
        json_response("ko", "No hay cambios para aplicar", 4001307);
    }
    
    // Actualizar
    $updates[] = "updated_at = NOW()";
    $params[] = $appointment_id;
    
    $sql = "UPDATE advisory_appointments SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // TODO: Enviar email al cliente si cambió el estado
    
    json_response("ok", "Cita actualizada correctamente", 2001301);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-update-appointment: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", "Error interno del servidor", 5001301);
}