<?php
/**
 * API: api-incidents-paginated.php
 * Paginaci¨®n y filtrado server-side para incidencias
 */

if (!admin() && !proveedor() && !comercial()) {
    json_response("ko", "No autorizado", 4031358107);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereConditions = ["inc.status_id != 10"];
    
    if (proveedor()) {
        if (empty(USER["categories"])) {
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200002001, $result);
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
            json_response("ok", "", 9200002002, $result);
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
            json_response("ok", "", 9200002003, $result);
        }
        
        $customer_ids_str = implode(",", $customer_ids);
        $whereConditions[] = "req.user_id IN ($customer_ids_str)";
    }
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            cat.name LIKE :search1
            OR ist.name LIKE :search2
            OR inc.details LIKE :search3
            OR DATE_FORMAT(inc.created_at, '%d/%m/%Y') LIKE :search4
            OR CAST(inc.request_id AS CHAR) LIKE :search5
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
        FROM request_incidents inc
        LEFT JOIN requests req ON req.id = inc.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN incident_statuses ist ON ist.id = inc.status_id
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
            inc.id,
            inc.request_id,
            inc.status_id,
            ist.name AS status_name,
            inc.details,
            inc.created_at,
            inc.updated_at,
            cat.name AS category_name
        FROM request_incidents inc
        LEFT JOIN requests req ON req.id = inc.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN incident_statuses ist ON ist.id = inc.status_id
        WHERE $whereClause
        ORDER BY inc.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $incidents = $stmt->fetchAll();
    
    $formattedIncidents = [];
    foreach ($incidents as $inc) {
        $hasNotification = false;
        if (defined('NOTIFICATIONS') && !empty(NOTIFICATIONS) && is_array(NOTIFICATIONS)) {
            foreach (NOTIFICATIONS as $n) {
                if (isset($n['request_id']) && $n['request_id'] == $inc['request_id'] && isset($n['status']) && $n['status'] == 0) {
                    $hasNotification = true;
                    break;
                }
            }
        }
        
        $formattedIncidents[] = [
            'id' => $inc['id'],
            'request_id' => $inc['request_id'],
            'category_name' => $inc['category_name'] ?? '',
            'status_name' => $inc['status_name'] ?? '',
            'details' => $inc['details'] ?? '',
            'created_at' => is_null($inc['created_at']) ? '-' : fdate($inc['created_at']),
            'updated_at' => is_null($inc['updated_at']) ? '-' : fdate($inc['updated_at']),
            'has_notification' => $hasNotification,
            'status_badge' => get_badge_html_incidents($inc['status_name'] ?? '')
        ];
    }
    
    $result = [
        'data' => $formattedIncidents,
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
    error_log("Error en api-incidents-paginated: " . $e->getMessage() . " l¨ªnea " . $e->getLine());
    json_response("ko", $e->getMessage(), 9500002000);
}