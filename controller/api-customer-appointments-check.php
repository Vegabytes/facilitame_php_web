<?php
/**
 * API: Verificar cambios en citas del cliente
 * GET /api-customer-appointments-check?since=YYYY-MM-DD HH:MM:SS
 * 
 * Detecta cambios en las citas del cliente desde un timestamp dado
 * para notificaciones en tiempo real
 */
global $pdo;

if (!cliente()) {
    json_response("ko", "Acceso denegado", 403);
}

$since = isset($_GET['since']) ? trim($_GET['since']) : '';

// Validar timestamp
if (empty($since)) {
    // Si no hay since, usar hace 1 minuto
    $since = date('Y-m-d H:i:s', strtotime('-1 minute'));
} else {
    // Convertir ISO format a MySQL format
    $since = date('Y-m-d H:i:s', strtotime($since));
}

try {
    $customer_id = USER['id'];
    
    // Buscar citas actualizadas desde el timestamp
    $sql = "
        SELECT 
            aa.id,
            aa.status,
            aa.scheduled_date,
            aa.proposed_date,
            aa.needs_confirmation_from,
            aa.updated_at,
            (
                SELECT COUNT(*) 
                FROM advisory_messages am 
                WHERE am.appointment_id = aa.id 
                AND am.sender_type = 'advisory' 
                AND (am.is_read = 0 OR am.is_read IS NULL)
                AND am.created_at > ?
            ) as new_messages
        FROM advisory_appointments aa
        WHERE aa.customer_id = ?
        AND (
            aa.updated_at > ?
            OR EXISTS (
                SELECT 1 FROM advisory_messages am 
                WHERE am.appointment_id = aa.id 
                AND am.sender_type = 'advisory'
                AND am.created_at > ?
            )
        )
        ORDER BY aa.updated_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$since, $customer_id, $since, $since]);
    $updated_appointments = $stmt->fetchAll();
    
    $changes = [];
    
    foreach ($updated_appointments as $apt) {
        $change = [
            'id' => $apt['id'],
            'updated_at' => $apt['updated_at']
        ];
        
        // Determinar tipo de cambio
        if ($apt['status'] === 'agendado' && !empty($apt['scheduled_date'])) {
            $change['type'] = 'confirmed';
        } elseif ($apt['status'] === 'cancelado') {
            $change['type'] = 'cancelled';
        } elseif ($apt['needs_confirmation_from'] === 'customer' && !empty($apt['proposed_date'])) {
            $change['type'] = 'rescheduled';
        } elseif ($apt['new_messages'] > 0) {
            $change['type'] = 'message';
            $change['count'] = (int)$apt['new_messages'];
        } else {
            $change['type'] = 'updated';
        }
        
        $changes[] = $change;
    }
    
    json_response("ok", "Cambios verificados", 200, [
        'changes' => $changes,
        'timestamp' => date('c') // ISO 8601 format
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-appointments-check: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}