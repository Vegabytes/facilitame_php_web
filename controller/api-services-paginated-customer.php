<?php
// controller/api-services-paginated-customer.php

if (!cliente()) {
    json_response("ko", "No autorizado", 4031358901);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $limit;

try {
    $user_id = (int)USER["id"];
    
    $params = [$user_id];
    $whereConditions = ["req.user_id = ?", "req.deleted_at IS NULL"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            cat.name LIKE ?
            OR req.id LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($status)) {
        $whereConditions[] = "sta.status_name = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = $row ? intval($row['total']) : 0;
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos paginados
    $dataQuery = "
        SELECT 
            req.id,
            req.category_id,
            req.status_id,
            req.request_date,
            req.updated_at,
            req.created_at,
            cat.name AS category_name,
            sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
        ORDER BY req.request_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    $formattedRequests = [];
    foreach ($requests as $req) {
        $formattedRequests[] = [
            'id' => $req['id'],
            'category_name' => $req['category_name'] ?? '',
            'status' => $req['status'] ?? '',
            'status_id' => intval($req['status_id']),
            'request_date' => $req['request_date'] ? fdate($req['request_date']) : '-',
            'updated_at' => $req['updated_at'] ? fdate($req['updated_at']) : '-',
            'request_info' => get_request_category_info($req)
        ];
    }
    
    $result = [
        'data' => $formattedRequests,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200009001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-services-paginated-customer: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500009001);
}