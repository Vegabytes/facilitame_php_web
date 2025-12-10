<?php
/**
 * API: api-requests-paginated-sales.php
 * Paginaci��n y filtrado server-side para COMERCIAL
 * Solicitudes de clientes asociados al comercial via sales_codes
 */

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358300);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
$offset = ($page - 1) * $limit;

try {
    $params = [':sales_user_id' => USER['id']];
    $whereConditions = ["req.status_id NOT IN (8, 9, 10)"];
    
    // Filtro por estado espec��fico
    if ($status > 0) {
        $whereConditions[] = "req.status_id = :status_filter";
        $params[':status_filter'] = $status;
        
        // Si se filtra por un estado excluido (8, 9, 10), quitar la exclusi��n general
        if (in_array($status, [8, 9, 10])) {
            $whereConditions = array_filter($whereConditions, function($cond) {
                return strpos($cond, 'NOT IN (8, 9, 10)') === false;
            });
            $whereConditions = array_values($whereConditions);
        }
    }
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR cat.name LIKE :search3
            OR sta.status_name LIKE :search4
            OR DATE_FORMAT(req.request_date, '%d/%m/%Y') LIKE :search5
            OR CAST(req.id AS CHAR) LIKE :search6
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
        $params[':search6'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        INNER JOIN users u ON u.id = req.user_id
        INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE sc.user_id = :sales_user_id
        AND $whereClause
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
            u.email AS customer_email,
            cat.name AS category_name,
            sta.status_name AS status,
            (SELECT COUNT(*) FROM notifications n
             WHERE n.request_id = req.id
             AND n.receiver_id = :notif_user_id
             AND n.status = 0) > 0 AS has_notification
        FROM requests req
        INNER JOIN users u ON u.id = req.user_id
        INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE sc.user_id = :sales_user_id
        AND $whereClause
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
            'customer_email' => $req['customer_email'] ?? '',
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
    
    json_response("ok", "", 9200003000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-requests-paginated-sales: " . $e->getMessage() . " l��nea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500003000);
}