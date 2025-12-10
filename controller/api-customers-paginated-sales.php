<?php
/**
 * API: api-customers-paginated-sales.php
 * Clientes paginados para COMERCIAL - OPTIMIZADO
 * 
 * Cambios:
 * - Eliminadas subqueries correlacionadas (total_requests, active_requests)
 * - Usar LEFT JOIN + GROUP BY en su lugar
 */

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358302);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $sales_user_id = (int) USER['id'];
    
    // WHERE base
    $whereSearch = "";
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereSearch = " AND (
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR u.phone LIKE :search3
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
    }
    
    // COUNT
    $countQuery = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        WHERE sc.user_id = :sales_user_id
        AND u.deleted_at IS NULL
        $whereSearch
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->bindValue(':sales_user_id', $sales_user_id, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // DATA - Con LEFT JOIN para conteos (evita subqueries correlacionadas)
    $dataQuery = "
        SELECT 
            u.id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS full_name,
            u.email,
            u.phone,
            u.email_verified_at,
            u.created_at,
            COUNT(r.id) AS total_requests,
            SUM(CASE WHEN r.status_id NOT IN (8, 9, 10) THEN 1 ELSE 0 END) AS active_requests
        FROM users u
        INNER JOIN customers_sales_codes csc ON csc.customer_id = u.id
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        LEFT JOIN requests r ON r.user_id = u.id
        WHERE sc.user_id = :sales_user_id
        AND u.deleted_at IS NULL
        $whereSearch
        GROUP BY u.id, u.name, u.lastname, u.email, u.phone, u.email_verified_at, u.created_at
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $stmt->bindValue(':sales_user_id', $sales_user_id, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedCustomers = [];
    foreach ($customers as $c) {
        $formattedCustomers[] = [
            'id' => (int) $c['id'],
            'full_name' => trim($c['full_name']),
            'email' => $c['email'] ?? '',
            'phone' => $c['phone'] ?? '',
            'is_verified' => !is_null($c['email_verified_at']),
            'created_at' => is_null($c['created_at']) ? '-' : fdate($c['created_at']),
            'total_requests' => (int) $c['total_requests'],
            'active_requests' => (int) $c['active_requests']
        ];
    }
    
    $result = [
        'data' => $formattedCustomers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200003002, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-customers-paginated-sales: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500003002);
}