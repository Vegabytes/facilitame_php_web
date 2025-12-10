<?php
/**
 * API: api-reviews-paginated-provider.php
 * Paginación para solicitudes en revisión (status_id = 8) - PROVEEDOR
 */

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358202);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereConditions = ["req.status_id = 8"];
    
    if (empty(USER["categories"])) {
        $result = [
            'data' => [],
            'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
        ];
        json_response("ok", "", 9200003001, $result);
    }
    $whereConditions[] = "req.category_id IN (" . USER["categories"] . ")";
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR cat.name LIKE :search2
            OR DATE_FORMAT(req.created_at, '%d/%m/%Y') LIKE :search3
            OR CAST(req.id AS CHAR) LIKE :search4
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $row = $stmt->fetch();
    $totalRecords = intval($row['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    $dataQuery = "
        SELECT 
            req.id,
            req.user_id,
            req.status_id,
            req.created_at,
            req.updated_at,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_name,
            cat.name AS category_name,
            sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
        ORDER BY req.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
    
    $formattedReviews = [];
    foreach ($reviews as $rev) {
        $hasNotification = false;
        if (defined('NOTIFICATIONS') && !empty(NOTIFICATIONS) && is_array(NOTIFICATIONS)) {
            foreach (NOTIFICATIONS as $n) {
                if (isset($n['request_id']) && $n['request_id'] == $rev['id'] && isset($n['status']) && $n['status'] == 0) {
                    $hasNotification = true;
                    break;
                }
            }
        }
        
        $formattedReviews[] = [
            'id' => $rev['id'],
            'user_id' => $rev['user_id'],
            'customer_name' => trim($rev['customer_name']),
            'category_name' => $rev['category_name'] ?? '',
            'status' => $rev['status'] ?? '',
            'status_id' => $rev['status_id'],
            'created_at' => is_null($rev['created_at']) ? '-' : fdate($rev['created_at']),
            'updated_at' => is_null($rev['updated_at']) ? '-' : fdate($rev['updated_at']),
            'has_notification' => $hasNotification,
            'status_badge' => get_badge_html($rev['status'] ?? $rev['status_id'])
        ];
    }
    
    $result = [
        'data' => $formattedReviews,
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
    error_log("Error en api-reviews-paginated-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500003000);
}