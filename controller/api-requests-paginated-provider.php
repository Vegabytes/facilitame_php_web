<?php
/**
 * API: api-requests-paginated-provider.php
 * Paginación y filtrado server-side para PROVEEDOR
 */

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
$offset = ($page - 1) * $limit;

try {
    // Sin categorías asignadas = sin resultados
    if (empty(USER["categories"])) {
        $result = [
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 0,
                'per_page' => $limit,
                'from' => 0,
                'to' => 0
            ]
        ];
        json_response("ok", "", 9200001001, $result);
    }
    
    $params = [];
    $whereConditions = [
        "req.status_id NOT IN (8, 9, 10)",
        "req.category_id IN (" . USER["categories"] . ")"
    ];
    
    // Filtro por estado específico
    if ($status > 0) {
        $whereConditions[] = "req.status_id = :status_filter";
        $params[':status_filter'] = $status;
        
        // Si se filtra por un estado excluido (8, 9, 10), quitar la exclusión general
        if (in_array($status, [8, 9, 10])) {
            $whereConditions = array_filter($whereConditions, function($cond) {
                return strpos($cond, 'NOT IN (8, 9, 10)') === false;
            });
        }
    }
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR cat.name LIKE :search2
            OR sta.status_name LIKE :search3
            OR DATE_FORMAT(req.request_date, '%d/%m/%Y') LIKE :search4
            OR CAST(req.id AS CHAR) LIKE :search5
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = intval($stmt->fetchColumn());
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos con notificaciones vía subquery
    $dataQuery = "
        SELECT
            req.id,
            req.user_id,
            req.request_date,
            req.status_id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            cat.name AS category_name,
            sta.status_name AS status,
            (SELECT COUNT(*) FROM notifications n
             WHERE n.request_id = req.id
             AND n.receiver_id = :notif_user_id
             AND n.status = 0) > 0 AS has_notification
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
        ORDER BY req.request_date DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $params[':notif_user_id'] = USER['id'];
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    $formattedRequests = array_map(function($req) {
        return [
            'id' => (int)$req['id'],
            'user_id' => (int)$req['user_id'],
            'customer_full_name' => trim($req['customer_full_name']),
            'category_name' => $req['category_name'] ?? '',
            'status' => $req['status'] ?? '',
            'status_id' => (int)$req['status_id'],
            'request_date' => is_null($req['request_date']) ? '-' : fdate($req['request_date']),
            'has_notification' => (bool)$req['has_notification']
        ];
    }, $requests);
    
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
    
    json_response("ok", "", 9200001000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-requests-paginated-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500001000);
}