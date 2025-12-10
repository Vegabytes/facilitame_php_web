<?php
/**
 * API: api-dashboard-kpis-client.php
 * Endpoint consolidado para KPIs del dashboard del cliente
 */
if (!cliente()) {
    json_response("ko", "No autorizado", 4031358410);
}

global $pdo;

try {
    $userId = USER['id'];
    
    // Query consolidada optimizada
    $query = "
        SELECT 
            -- Notificaciones no leídas (usa el nuevo índice idx_notif_user_status_created)
            (
                SELECT COUNT(*) 
                FROM notifications n
                WHERE n.user_id = :user_id_1
                AND n.status = 0
            ) AS notificaciones,
            
            -- Vencimientos próximos (90 días)
            (
                SELECT COUNT(*) 
                FROM offers o
                INNER JOIN requests req ON req.id = o.request_id
                WHERE req.user_id = :user_id_2
                AND req.status_id = 7
                AND o.expires_at IS NOT NULL
                AND o.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ) AS vencimientos,
            
            -- Ofertas disponibles (status = 2)
            (
                SELECT COUNT(*) 
                FROM requests req 
                WHERE req.user_id = :user_id_3
                AND req.status_id = 2
            ) AS ofertas,
            
            -- Solicitudes activas
            (
                SELECT COUNT(*) 
                FROM requests req 
                WHERE req.user_id = :user_id_4
                AND req.status_id NOT IN (9)
            ) AS solicitudes
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id_1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id_2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id_3', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id_4', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    json_response("ok", "", 9200010200, [
        'notificaciones' => (int) $row['notificaciones'],
        'vencimientos' => (int) $row['vencimientos'],
        'ofertas' => (int) $row['ofertas'],
        'solicitudes' => (int) $row['solicitudes']
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-kpis-client: " . $e->getMessage());
    json_response("ko", "Error interno", 9500010200);
}