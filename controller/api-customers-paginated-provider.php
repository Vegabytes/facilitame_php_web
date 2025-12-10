<?php
/**
 * API: api-customers-paginated-provider.php
 * Clientes paginados para proveedor - OPTIMIZADO
 */

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358701);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Usar categorías ya disponibles en USER
    if (empty(USER["categories"])) {
        json_response('ok', '', 9200007001, [
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
    
    $params = [];
    $whereSearch = "";
    
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
    
    // COUNT - con JOIN y GROUP BY en vez de subquery
    $countQuery = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        INNER JOIN requests r ON r.user_id = u.id AND r.category_id IN ($categories)
        WHERE u.deleted_at IS NULL
        $whereSearch
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // DATA - con GROUP BY y COUNT agregado (evita subquery correlacionada)
    $dataQuery = "
        SELECT 
            u.id,
            u.name,
            u.lastname,
            u.email,
            u.phone,
            u.created_at,
            rol.name AS role_name,
            COUNT(r.id) AS services_number
        FROM users u
        INNER JOIN requests r ON r.user_id = u.id AND r.category_id IN ($categories)
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles rol ON rol.id = mhr.role_id
        WHERE u.deleted_at IS NULL
        $whereSearch
        GROUP BY u.id, u.name, u.lastname, u.email, u.phone, u.created_at, rol.name
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'id' => $customer['id'],
            'name' => $customer['name'] ?? '',
            'lastname' => $customer['lastname'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'created_at' => fdate($customer['created_at']),
            'role_name' => $customer['role_name'] ?? '',
            'services_number' => (int) $customer['services_number']
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
    
    json_response("ok", "", 9200007000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-customers-paginated-provider: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500007000);
}