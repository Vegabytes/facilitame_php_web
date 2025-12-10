<?php
/**
 * API: api-logs-paginated-sales.php
 * Logs paginados para COMERCIAL - OPTIMIZADO
 * 
 * Cambios:
 * - Usar link_id (no target_id) para JOIN con requests
 *   (target_id puede ser message, offer, etc. - link_id siempre apunta a request)
 * - Usar prepared statements consistentes
 * - Seleccionar solo campos necesarios (no log.*)
 */

if (!comercial()) {
    json_response('ko', 'No autorizado', 4031358501);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $comercial_id = (int) USER["id"];
    
    // Obtener customers del comercial en 1 query
    $stmt = $pdo->prepare("
        SELECT DISTINCT csc.customer_id 
        FROM customers_sales_codes csc
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        WHERE sc.user_id = ?
    ");
    $stmt->execute([$comercial_id]);
    $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customer_ids)) {
        json_response('ok', '', 9200005001, [
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 0,
                'per_page' => $limit,
                'from' => 0,
                'to' => 0
            ]
        ]);
        return;
    }
    
    $in_customers = implode(",", array_map("intval", $customer_ids));
    
    // WHERE base: logs de requests de los clientes del comercial
    // Usamos link_id (NO target_id) porque link_id siempre apunta a la request
    $baseWhere = "
        log.link_type = 'request'
        AND req.user_id IN ($in_customers)
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $baseWhere .= " AND (
            CONCAT(u.name, ' ', IFNULL(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR log.target_type LIKE :search3
            OR log.event LIKE :search4
            OR log.data LIKE :search5
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    // COUNT
    $countQuery = "
        SELECT COUNT(*) as total
        FROM log
        INNER JOIN users u ON u.id = log.triggered_by
        INNER JOIN requests req ON req.id = log.link_id
        WHERE $baseWhere
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // DATA - Solo campos necesarios
    $dataQuery = "
        SELECT 
            log.id,
            log.target_type,
            log.target_id,
            log.event,
            log.data,
            log.link_type,
            log.link_id,
            log.created_at,
            CONCAT(u.name, ' ', IFNULL(u.lastname, '')) AS triggered_by_name,
            u.email AS triggered_by_email
        FROM log
        INNER JOIN users u ON u.id = log.triggered_by
        INNER JOIN requests req ON req.id = log.link_id
        WHERE $baseWhere
        ORDER BY log.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response('ok', '', 9200005001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-logs-paginated-sales: " . $e->getMessage() . " línea " . $e->getLine());
    json_response('ko', $e->getMessage(), 9500005001);
}