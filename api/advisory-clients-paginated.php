<?php
// api/advisory-clients-paginated.php
header('Content-Type: application/json');

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener el ID real de la asesoría
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_row = $stmt->fetch();

if (!$advisory_row) {
    json_response('ok', '', 200, [
        'data' => [],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 1,
            'total_records' => 0,
            'per_page' => 25,
            'from' => 0,
            'to' => 0
        ]
    ]);
}

$advisory_id = (int)$advisory_row['id'];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [$advisory_id];
    $whereConditions = ["ca.advisory_id = ?"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = intval($row['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos
    $dataQuery = "
        SELECT 
            u.id,
            u.name,
            u.lastname,
            u.email,
            u.phone,
            u.nif_cif,
            u.email_verified_at,
            u.created_at,
            ca.client_type,
            ca.client_subtype,
            rol.name AS role_name,
            (SELECT COUNT(*) FROM requests req WHERE req.user_id = u.id AND req.deleted_at IS NULL) AS services_number
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles rol ON rol.id = mhr.role_id
        WHERE $whereClause
        ORDER BY u.name ASC, u.lastname ASC
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
    $customers = $stmt->fetchAll();
    
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'id' => (int)$customer['id'],
            'name' => $customer['name'] ?? '',
            'lastname' => $customer['lastname'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'nif_cif' => $customer['nif_cif'] ?? '',
            'email_verified_at' => $customer['email_verified_at'],
            'created_at' => $customer['created_at'],
            'client_type' => $customer['client_type'] ?? '',
            'client_subtype' => $customer['client_subtype'] ?? '',
            'role_name' => $customer['role_name'] ?? '',
            'services_number' => intval($customer['services_number'])
        ];
    }
    
    json_response("ok", "", 200, [
        'data' => $formattedCustomers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-clients-paginated: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 500);
}
