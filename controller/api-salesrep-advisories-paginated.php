<?php
/**
 * API: Listar asesorías vinculadas al código del comercial
 * Endpoint: /api-salesrep-advisories-paginated
 * Method: GET
 * Params: page, limit, search
 */

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358311);
}

global $pdo;
$salesUserId = USER['id'];

// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Base query
    $whereConditions = ["sc.user_id = :sales_user_id"];
    $params = [':sales_user_id' => $salesUserId];
    
    // Búsqueda
    if ($search !== '') {
        $whereConditions[] = "(a.razon_social LIKE :search OR a.cif LIKE :search OR a.email_empresa LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(DISTINCT a.id) as total
        FROM advisories a
        INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
        INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
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
            a.id,
            a.cif,
            a.razon_social,
            a.direccion,
            a.email_empresa,
            a.plan,
            a.estado,
            a.codigo_identificacion,
            a.created_at,
            (SELECT COUNT(*) FROM customers_advisories ca WHERE ca.advisory_id = a.id) as clients_count
        FROM advisories a
        INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
        INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
        WHERE {$whereClause}
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $advisories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular from/to
    $from = $totalRecords > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $totalRecords);
    
    $responseData = [
        "data" => $advisories,
        "pagination" => [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_records" => $totalRecords,
            "per_page" => $limit,
            "from" => $from,
            "to" => $to
        ]
    ];
    
    json_response("ok", "", 9200010101, $responseData);
    
} catch (Exception $e) {
    error_log("Error en api-salesrep-advisories-paginated: " . $e->getMessage());
    json_response("ko", "Error al obtener asesorías", 9500010101);
}