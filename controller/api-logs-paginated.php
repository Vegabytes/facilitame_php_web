<?php
/**
 * API: api-logs-paginated.php
 * Paginación y filtrado server-side para logs
 */

if (!admin() && !proveedor() && !comercial()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereConditions = ["1=1"];
    $joinConditions = "JOIN users ON users.id = log.triggered_by";
    
    // Filtrar según rol (igual que en el controlador)
    if (proveedor()) {
        $provider_id = (int)USER["id"];
        $stmt = $pdo->prepare("SELECT category_id FROM provider_categories WHERE provider_id = ?");
        $stmt->execute([$provider_id]);
        $category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($category_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002001, $result);
        }
        
        $category_ids = array_unique(array_filter(array_map("intval", $category_ids)));
        $in_categories = implode(",", $category_ids);
        $stmt = $pdo->query("SELECT id FROM requests WHERE category_id IN ($in_categories)");
        $request_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($request_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002002, $result);
        }
        
        $request_ids = array_unique(array_filter(array_map("intval", $request_ids)));
        $in_requests = implode(",", $request_ids);
        $whereConditions[] = "log.target_type IN ('request','message','message_provider','offer','incident','invoice','notification','document')";
        $whereConditions[] = "log.target_id IN ($in_requests)";
        
    } elseif (comercial()) {
        $comercial_id = (int)USER["id"];
        $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = ?");
        $stmt->execute([$comercial_id]);
        $sales_code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($sales_code_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002003, $result);
        }

        $in_codes = implode(",", array_map("intval", $sales_code_ids));
        $stmt = $pdo->query("SELECT DISTINCT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($in_codes)");
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($customer_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002004, $result);
        }

        // Obtener las solicitudes de los clientes del comercial
        $in_customers = implode(",", array_map("intval", $customer_ids));
        $stmt = $pdo->query("SELECT id FROM requests WHERE user_id IN ($in_customers)");
        $request_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($request_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002005, $result);
        }

        $in_requests = implode(",", array_map("intval", $request_ids));
        $whereConditions[] = "log.target_type IN ('request','message','message_provider','offer','incident','invoice','notification','document')";
        $whereConditions[] = "log.target_id IN ($in_requests)";
    }
    // Admin no necesita filtros adicionales
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(users.name, ''), ' ', COALESCE(users.lastname, '')) LIKE :search1
            OR users.email LIKE :search2
            OR log.target_type LIKE :search3
            OR log.event LIKE :search4
            OR log.data LIKE :search5
            OR CAST(log.target_id AS CHAR) LIKE :search6
            OR DATE_FORMAT(log.created_at, '%d/%m/%Y') LIKE :search7
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
        $params[':search6'] = $searchTerm;
        $params[':search7'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "SELECT COUNT(*) as total FROM log $joinConditions WHERE $whereClause";
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $row = $stmt->fetch();
    $totalRecords = $row ? intval($row['total']) : 0;
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos
    $dataQuery = "
        SELECT 
            log.id,
            log.triggered_by,
            CONCAT(COALESCE(users.name, ''), ' ', COALESCE(users.lastname, '')) AS triggered_by_name,
            users.email AS triggered_by_email,
            log.target_type,
            log.target_id,
            log.event,
            log.link_type,
            log.link_id,
            log.data,
            log.created_at
        FROM log
        $joinConditions
        WHERE $whereClause
        ORDER BY log.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    $result = [
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200002000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-logs-paginated: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500002000);
}