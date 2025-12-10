<?php
/**
 * API: api-logs-paginated-provider.php
 * Logs paginados para proveedor - OPTIMIZADO Y CORREGIDO
 */

if (!proveedor()) {
    json_response('ko', 'No autorizado', 4031358601);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Si no tiene categorías, retornar vacío
    if (empty(USER["categories"])) {
        json_response('ok', '', 9200006001, [
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
    }
    
    $categories = USER["categories"];
    
    // WHERE base: logs relacionados con requests de las categorías del proveedor
    // Usamos link_id (NO target_id) porque link_id siempre apunta a la request
    $baseWhere = "
        log.link_type = 'request'
        AND req.category_id IN ($categories)
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
    
    // COUNT - Solo 1 query con JOINs
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
    
    // DATA - Paginada
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
    
    json_response('ok', '', 9200006000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-logs-paginated-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response('ko', $e->getMessage(), 9500006000);
}