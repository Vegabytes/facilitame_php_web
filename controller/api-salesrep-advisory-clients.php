<?php
/**
 * API: Listar clientes de una asesoría (para comercial)
 * Endpoint: /api-salesrep-advisory-clients
 * Method: GET
 * Params: advisory_id, search, limit
 */

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358313);
}

global $pdo;
$salesUserId = USER['id'];

$advisory_id = isset($_GET['advisory_id']) ? (int)$_GET['advisory_id'] : 0;
if (!$advisory_id) {
    json_response("ko", "ID de asesoría requerido", 4001358313);
}

// Verificar que la asesoría pertenece al comercial
$stmt = $pdo->prepare("
    SELECT 1 FROM advisories a
    INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
    INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
    WHERE a.id = ? AND sc.user_id = ?
");
$stmt->execute([$advisory_id, $salesUserId]);
if (!$stmt->fetch()) {
    json_response("ko", "Sin acceso a esta asesoría", 4031358314);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $whereConditions = ["ca.advisory_id = :advisory_id"];
    $params = [':advisory_id' => $advisory_id];
    
    if ($search !== '') {
        $whereConditions[] = "(u.name LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM customers_advisories ca
        INNER JOIN users u ON ca.customer_id = u.id
        WHERE {$whereClause}
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Query principal
    $query = "
        SELECT 
            u.id,
            CONCAT(u.name, ' ', COALESCE(u.lastname, '')) as name,
            u.email,
            u.phone,
            u.nif_cif,
            u.email_verified_at,
            u.created_at
        FROM customers_advisories ca
        INNER JOIN users u ON ca.customer_id = u.id
        WHERE {$whereClause}
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $from = $totalRecords > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $totalRecords);
    
    $responseData = [
        "data" => $customers,
        "pagination" => [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_records" => $totalRecords,
            "per_page" => $limit,
            "from" => $from,
            "to" => $to
        ]
    ];
    
    json_response("ok", "", 9200010103, $responseData);
    
} catch (Exception $e) {
    error_log("Error en api-salesrep-advisory-clients: " . $e->getMessage());
    json_response("ko", "Error al obtener clientes", 9500010103);
}