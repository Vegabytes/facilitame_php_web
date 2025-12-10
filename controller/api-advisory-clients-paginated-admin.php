<?php
/**
 * API Admin: Clientes de una asesoría
 * GET /api/advisory-clients-paginated-admin?advisory_id=13
 */
header('Content-Type: application/json');

if (!admin()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$advisory_id = isset($_GET['advisory_id']) ? (int)$_GET['advisory_id'] : 0;
if (!$advisory_id) {
    json_response("ko", "advisory_id requerido", 400);
}

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
    $totalRecords = $row ? intval($row['total']) : 0;
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
            (SELECT COUNT(*) FROM requests req WHERE req.user_id = u.id AND req.deleted_at IS NULL) AS services_number
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
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
    foreach ($customers as $c) {
        $formattedCustomers[] = [
            'id' => (int)$c['id'],
            'name' => trim(($c['name'] ?? '') . ' ' . ($c['lastname'] ?? '')),
            'email' => $c['email'] ?? '',
            'phone' => $c['phone'] ?? '',
            'nif_cif' => $c['nif_cif'] ?? '',
            'email_verified_at' => $c['email_verified_at'],
            'created_at' => $c['created_at'] ? date('d/m/Y', strtotime($c['created_at'])) : '-',
            'client_type' => $c['client_type'] ?? '',
            'services_number' => intval($c['services_number'])
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
    error_log("Error en api-advisory-clients-paginated-admin: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 500);
}