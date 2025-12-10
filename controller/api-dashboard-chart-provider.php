<?php
/**
 * API: api-dashboard-chart-provider.php
 * Endpoint para el gráfico del dashboard - agrupa por estado
 */

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358211);
}

global $pdo;

try {
    if (empty(USER["categories"])) {
        json_response("ok", "", 9200011001, [
            'chart_data' => [],
            'total' => 0
        ]);
    }
    
    $categories = USER["categories"];
    
    // Query agrupada por estado - mucho más eficiente que traer 1000 registros
    $query = "
        SELECT 
            sta.status_name AS status,
            COUNT(*) AS count
        FROM requests req
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE req.status_id NOT IN (8, 9, 10)
        AND req.category_id IN ($categories)
        GROUP BY req.status_id, sta.status_name
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $chartData = [];
    $total = 0;
    
    foreach ($rows as $row) {
        $chartData[] = [
            'category' => $row['status'] ?? 'Sin estado',
            'value' => (int) $row['count']
        ];
        $total += (int) $row['count'];
    }
    
    json_response("ok", "", 9200011000, [
        'chart_data' => $chartData,
        'total' => $total
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-chart-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500011000);
}