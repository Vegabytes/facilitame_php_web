<?php
/**
 * API: api-dashboard-kpis-sales.php
 * Endpoint consolidado para KPIs del dashboard del comercial
 * Reduce 4 llamadas API a 1 sola
 */
if (!comercial()) {
    json_response("ko", "No autorizado", 4031358310);
}
global $pdo;
try {
    $salesUserId = USER['id'];
    
    // Query consolidada: obtiene los 4 conteos en una sola consulta
    $query = "
        SELECT 
            -- Solicitudes activas (status NOT IN 8, 9, 10)
            (
                SELECT COUNT(*) 
                FROM requests req
                INNER JOIN users u ON u.id = req.user_id
                INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
                INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
                WHERE sc.user_id = :sales_id_1
                AND req.status_id NOT IN (8, 9, 10)
            ) AS solicitudes,
            
            -- Clientes asociados
            (
                SELECT COUNT(DISTINCT csc.customer_id)
                FROM customers_sales_codes csc
                INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
                WHERE sc.user_id = :sales_id_2
            ) AS clientes,
            
            -- Aplazadas (status = 10)
            (
                SELECT COUNT(*) 
                FROM requests req
                INNER JOIN users u ON u.id = req.user_id
                INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
                INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
                WHERE sc.user_id = :sales_id_3
                AND req.status_id = 10
            ) AS aplazadas,
            
            -- Asesorías vinculadas
            (
                SELECT COUNT(DISTINCT a.id)
                FROM advisories a
                INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
                INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
                WHERE sc.user_id = :sales_id_4
            ) AS asesorias
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':sales_id_1', $salesUserId, PDO::PARAM_INT);
    $stmt->bindValue(':sales_id_2', $salesUserId, PDO::PARAM_INT);
    $stmt->bindValue(':sales_id_3', $salesUserId, PDO::PARAM_INT);
    $stmt->bindValue(':sales_id_4', $salesUserId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result = [
        'solicitudes' => (int) $row['solicitudes'],
        'clientes' => (int) $row['clientes'],
        'aplazadas' => (int) $row['aplazadas'],
        'asesorias' => (int) $row['asesorias']
    ];
    
    json_response("ok", "", 9200010100, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-kpis-sales: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500010100);
}