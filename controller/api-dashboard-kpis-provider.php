<?php
/**
 * API: api-dashboard-kpis-provider.php
 * Endpoint consolidado para KPIs del dashboard del proveedor
 * Reduce 4 llamadas API a 1 sola
 */

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358210);
}

global $pdo;

try {
    // Si no tiene categorías, devolver todos los conteos a 0
    if (empty(USER["categories"])) {
        $result = [
            'solicitudes' => 0,
            'incidencias' => 0,
            'revisiones' => 0,
            'aplazadas' => 0
        ];
        json_response("ok", "", 9200010001, $result);
    }
    
    $categories = USER["categories"];
    
    // Query consolidada: obtiene los 4 conteos en una sola consulta
    $query = "
        SELECT 
            -- Solicitudes activas (status NOT IN 8, 9, 10)
            (
                SELECT COUNT(*) 
                FROM requests req 
                WHERE req.status_id NOT IN (8, 9, 10) 
                AND req.category_id IN ($categories)
            ) AS solicitudes,
            
            -- Incidencias activas (status != 10)
            (
                SELECT COUNT(*) 
                FROM request_incidents inc
                INNER JOIN requests req ON req.id = inc.request_id
                WHERE inc.status_id != 10 
                AND req.category_id IN ($categories)
            ) AS incidencias,
            
            -- Revisiones pendientes (status = 8)
            (
                SELECT COUNT(*) 
                FROM requests req 
                WHERE req.status_id = 8 
                AND req.category_id IN ($categories)
            ) AS revisiones,
            
            -- Aplazadas (status = 10)
            (
                SELECT COUNT(*) 
                FROM requests req 
                WHERE req.status_id = 10 
                AND req.category_id IN ($categories)
            ) AS aplazadas
    ";
    
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result = [
        'solicitudes' => (int) $row['solicitudes'],
        'incidencias' => (int) $row['incidencias'],
        'revisiones' => (int) $row['revisiones'],
        'aplazadas' => (int) $row['aplazadas']
    ];
    
    json_response("ok", "", 9200010000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-kpis-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500010000);
}