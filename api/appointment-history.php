<?php
/**
 * API: Obtener historial de una cita
 * GET /api-appointment-history?appointment_id=X
 * 
 * Accesible por asesoría (dueña de la cita) o cliente (dueño de la cita)
 */

global $pdo;

if (!asesoria() && !cliente()) {
    json_response("ko", "No autorizado", 403);
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

if (!$appointment_id) {
    json_response("ko", "ID de cita requerido", 400);
}

try {
    // Verificar acceso según rol
    if (asesoria()) {
        $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
        $stmt->execute([USER['id']]);
        $advisory = $stmt->fetch();
        
        if (!$advisory) {
            json_response("ko", "Asesoría no encontrada", 404);
        }
        
        $stmt = $pdo->prepare("SELECT id FROM advisory_appointments WHERE id = ? AND advisory_id = ?");
        $stmt->execute([$appointment_id, $advisory['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM advisory_appointments WHERE id = ? AND customer_id = ?");
        $stmt->execute([$appointment_id, USER['id']]);
    }
    
    if (!$stmt->fetch()) {
        json_response("ko", "Cita no encontrada o sin permisos", 404);
    }
    
    // Obtener historial
    $history = get_appointment_history($appointment_id);
    
    // Formatear para el frontend
    $formatted = [];
    foreach ($history as $entry) {
        $formatted[] = [
            'id' => $entry['id'],
            'action' => $entry['action'],
            'action_label' => translate_history_action(
                $entry['action'], 
                $entry['field_changed'], 
                $entry['old_value'], 
                $entry['new_value']
            ),
            'user_type' => $entry['user_type'],
            'user_name' => trim(($entry['user_name'] ?? '') . ' ' . ($entry['user_lastname'] ?? '')),
            'field' => $entry['field_changed'],
            'old_value' => $entry['old_value'],
            'new_value' => $entry['new_value'],
            'notes' => $entry['notes'],
            'created_at' => $entry['created_at'],
            'created_at_formatted' => date('d/m/Y H:i', strtotime($entry['created_at']))
        ];
    }
    
    json_response("ok", "Historial obtenido", 200, [
        'history' => $formatted
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-appointment-history: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
