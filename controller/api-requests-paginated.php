<?php
/**
 * API: api-requests-paginated.php
 * Paginación y filtrado server-side
 */

//require_once __DIR__ . '/../config/init.php';

if (!admin() && !proveedor() && !comercial()) {
    json_response("ko", "No autorizado", 4031358106);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereConditions = ["req.status_id NOT IN (8, 9, 10)"];
    
    if (proveedor()) {
        if (empty(USER["categories"])) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001001, $result);
        }
        $whereConditions[] = "req.category_id IN (" . USER["categories"] . ")";
    } elseif (comercial()) {
        $query = "SELECT id FROM sales_codes WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($code_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001002, $result);
        }
        
        $code_ids_str = implode(",", $code_ids);
        $query = "SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($code_ids_str)";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($customer_ids)) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001003, $result);
        }
        
        $customer_ids_str = implode(",", $customer_ids);
        $whereConditions[] = "req.user_id IN ($customer_ids_str)";
    }
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR cat.name LIKE :search2
            OR sta.status_name LIKE :search3
            OR DATE_FORMAT(req.request_date, '%d/%m/%Y') LIKE :search4
            OR CAST(req.id AS CHAR) LIKE :search5
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
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
            req.request_date,
            req.updated_at,
            req.status_id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            cat.name AS category_name,
            sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
        ORDER BY req.request_date DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    $formattedRequests = [];
    foreach ($requests as $req) {
        $hasNotification = false;
        if (defined('NOTIFICATIONS') && !empty(NOTIFICATIONS) && is_array(NOTIFICATIONS)) {
            foreach (NOTIFICATIONS as $n) {
                if (isset($n['request_id']) && $n['request_id'] == $req['id'] && isset($n['status']) && $n['status'] == 0) {
                    $hasNotification = true;
                    break;
                }
            }
        }
        
        $formattedRequests[] = [
            'id' => $req['id'],
            'customer_full_name' => trim($req['customer_full_name']),
            'category_name' => $req['category_name'] ?? '',
            'status' => $req['status'] ?? '',
            'status_id' => $req['status_id'],
            'request_date' => is_null($req['request_date']) ? '-' : fdate($req['request_date']),
            'updated_at' => is_null($req['updated_at']) ? '-' : fdate($req['updated_at']),
            'has_notification' => $hasNotification,
            'status_badge' => get_badge_html($req['status'] ?? $req['status_id'])
        ];
    }
    
    $result = [
        'data' => $formattedRequests,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200001000, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-requests-paginated: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500001000);
}