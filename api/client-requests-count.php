<?php
/**
 * API: api-client-requests-count.php
 * Cuenta las solicitudes activas del cliente
 */

if (!cliente()) {
    json_response("ko", "No autorizado", 4031358204);
}

global $pdo;

try {
    $query = "
        SELECT COUNT(*) as count
        FROM requests
        WHERE user_id = :user_id
        AND status_id NOT IN (9)
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $row = $stmt->fetch();
    
    $result = [
        'count' => intval($row['count'])
    ];
    
    json_response("ok", "", 9200104000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-client-requests-count: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500104000);
}