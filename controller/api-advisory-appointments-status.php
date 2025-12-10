<?php
/**
 * API: Verificar actividad en citas de asesoría
 * GET /api-advisory-appointments-status?since=YYYY-MM-DD HH:MM:SS
 * 
 * Detecta nueva actividad (propuestas, mensajes, solicitudes)
 * para notificaciones en tiempo real
 */
global $pdo;

if (!asesoria()) {
    json_response("ko", "Acceso denegado", 403);
}

$since = isset($_GET['since']) ? trim($_GET['since']) : '';

// Validar timestamp
if (empty($since)) {
    $since = date('Y-m-d H:i:s', strtotime('-1 minute'));
} else {
    $since = date('Y-m-d H:i:s', strtotime($since));
}

try {
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 404);
    }
    
    $advisory_id = $advisory['id'];
    
    // Contar citas pendientes de confirmación por asesoría
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM advisory_appointments
        WHERE advisory_id = ?
        AND needs_confirmation_from = 'advisory'
        AND status NOT IN ('cancelado', 'finalizado')
    ");
    $stmt->execute([$advisory_id]);
    $pending_confirmation = (int)$stmt->fetch()['cnt'];
    
    // Contar mensajes no leídos de clientes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM advisory_messages am
        INNER JOIN advisory_appointments aa ON aa.id = am.appointment_id
        WHERE aa.advisory_id = ?
        AND am.sender_type = 'customer'
        AND (am.is_read = 0 OR am.is_read IS NULL)
    ");
    $stmt->execute([$advisory_id]);
    $unread_messages = (int)$stmt->fetch()['cnt'];
    
    // Contar nuevas citas desde el timestamp
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM advisory_appointments
        WHERE advisory_id = ?
        AND created_at > ?
        AND status = 'solicitado'
    ");
    $stmt->execute([$advisory_id, $since]);
    $new_appointments = (int)$stmt->fetch()['cnt'];
    
    // Obtener cambios recientes
    $stmt = $pdo->prepare("
        SELECT 
            aa.id,
            aa.status,
            aa.needs_confirmation_from,
            aa.updated_at,
            CONCAT(u.name, ' ', u.lastname) as customer_name
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        WHERE aa.advisory_id = ?
        AND aa.updated_at > ?
        ORDER BY aa.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$advisory_id, $since]);
    $recent_changes = $stmt->fetchAll();
    
    json_response("ok", "Estado verificado", 200, [
        'pending_confirmation' => $pending_confirmation,
        'unread_messages' => $unread_messages,
        'new_appointments' => $new_appointments,
        'recent_changes' => $recent_changes,
        'timestamp' => date('c')
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-appointments-status: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}