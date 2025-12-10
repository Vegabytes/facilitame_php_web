<?php
/**
 * API: api-dashboard-kpis-admin.php
 * Endpoint consolidado para KPIs del dashboard del admin
 * Reduce 4 llamadas API a 1 sola
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358410);
}

global $pdo;

try {
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM requests WHERE status_id NOT IN (8, 9, 10)) AS solicitudes,
            (SELECT COUNT(*) FROM request_incidents WHERE status_id != 10) AS incidencias,
            (SELECT COUNT(*) FROM requests WHERE status_id = 8) AS revisiones,
            (SELECT COUNT(*) FROM requests WHERE status_id = 10) AS aplazadas
    ";
    
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result = [
        'solicitudes' => (int) $row['solicitudes'],
        'incidencias' => (int) $row['incidencias'],
        'revisiones' => (int) $row['revisiones'],
        'aplazadas' => (int) $row['aplazadas']
    ];
    
    json_response("ok", "", 9200010200, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-kpis-admin: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500010200);
}