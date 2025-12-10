<?php
/**
 * API: api-customers-paginated-admin.php
 * Clientes paginados para ADMIN - OPTIMIZADO
 * 
 * Cambios:
 * - Eliminada subquery correlacionada (services_number)
 * - Usar LEFT JOIN + GROUP BY en su lugar
 * - Filtrar deleted_at
 * - No exponer datos sensibles
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358001);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Roles de clientes: 4 = aut¨®nomo, 5 = empresa, 6 = particular
    $clientRoles = '4, 5, 6';
    
    $whereSearch = "";
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereSearch = " AND (
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR u.phone LIKE :search3
            OR u.id = :search_id
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
    }
    
    // COUNT
    $countQuery = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        INNER JOIN model_has_roles mhr ON mhr.model_id = u.id
        WHERE mhr.role_id IN ($clientRoles)
        AND u.deleted_at IS NULL
        $whereSearch
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // DATA - Con LEFT JOIN para conteo de servicios (evita subquery correlacionada)
    $dataQuery = "
        SELECT 
            u.id,
            u.name,
            u.lastname,
            u.email,
            u.phone,
            u.created_at,
            u.email_verified_at,
            rol.name AS role_name,
            COUNT(r.id) AS services_number
        FROM users u
        INNER JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles rol ON rol.id = mhr.role_id
        LEFT JOIN requests r ON r.user_id = u.id
        WHERE mhr.role_id IN ($clientRoles)
        AND u.deleted_at IS NULL
        $whereSearch
        GROUP BY u.id, u.name, u.lastname, u.email, u.phone, u.created_at, u.email_verified_at, rol.name
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
    
    // Formatear datos (no exponer campos sensibles)
    $formattedCustomers = [];
    foreach ($customers as $c) {
        $formattedCustomers[] = [
            'id' => (int) $c['id'],
            'name' => $c['name'] ?? '',
            'lastname' => $c['lastname'] ?? '',
            'email' => $c['email'] ?? '',
            'phone' => $c['phone'] ?? '',
            'created_at' => $c['created_at'],
            'is_verified' => !is_null($c['email_verified_at']),
            'role_name' => $c['role_name'] ?? '',
            'services_number' => (int) $c['services_number']
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
    
    json_response("ok", "", 9200001001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-customers-paginated-admin: " . $e->getMessage() . " l¨ªnea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500001001);
}