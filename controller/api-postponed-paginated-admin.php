<?php
/**
 * API: api-postponed-paginated-admin.php
 * Solicitudes aplazadas (status_id = 10) - ADMIN
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358109);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [':notif_user_id' => USER['id']];
    $whereConditions = ["req.status_id = 10"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR cat.name LIKE :search3
            OR DATE_FORMAT(req.created_at, '%d/%m/%Y') LIKE :search4
            OR CAST(req.id AS CHAR) LIKE :search5
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Count
    $countQuery = "
        SELECT COUNT(*) 
        FROM requests req
        LEFT JOIN users u ON u.id = req.user_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        if ($key !== ':notif_user_id') {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $totalRecords = intval($stmt->fetchColumn());
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Data con notificaciones vía subquery
    $dataQuery = "
        SELECT
            req.id,
            req.user_id,
            req.created_at,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_name,
            u.email AS customer_email,
            cat.name AS category_name,
            (SELECT COUNT(*) FROM notifications n
             WHERE n.request_id = req.id
             AND n.receiver_id = :notif_user_id
             AND n.status = 0) > 0 AS has_notification
        FROM requests req
        LEFT JOIN users u ON u.id = req.user_id
        LEFT JOIN categories cat ON cat.id = req.category_id
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
    $postponed = $stmt->fetchAll();
    
    $formattedPostponed = array_map(function($post) {
        return [
            'id' => (int)$post['id'],
            'user_id' => (int)$post['user_id'],
            'customer_name' => trim($post['customer_name']),
            'customer_email' => $post['customer_email'] ?? '',
            'category_name' => $post['category_name'] ?? '',
            'created_at' => is_null($post['created_at']) ? '-' : fdate($post['created_at']),
            'has_notification' => (bool)$post['has_notification']
        ];
    }, $postponed);
    
    $result = [
        'data' => $formattedPostponed,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200004000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-postponed-paginated-admin: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500004000);
}