<?php
/**
 * API: api-requests-paginated.php
 * VERSION DEBUG - Para identificar errores
 */

// Mostrar errores en desarrollo
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log de inicio
error_log("=== API REQUESTS PAGINATED START ===");



// Verificar autenticación
if (!function_exists('admin') || !function_exists('proveedor') || !function_exists('comercial')) {
    error_log("Funciones de rol no existen");
    json_response("ko", "Funciones de rol no disponibles", 4031358106);
    exit;
}

if (!admin() && !proveedor() && !comercial()) {
    error_log("Usuario no autorizado");
    json_response("ko", "No autorizado", 4031358106);
    exit;
}

error_log("Usuario autorizado - Rol: " . (admin() ? 'admin' : (proveedor() ? 'proveedor' : 'comercial')));

global $pdo;

if (!$pdo) {
    error_log("PDO no disponible");
    json_response("ko", "Base de datos no disponible", 5001358106);
    exit;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

error_log("Params: page=$page, limit=$limit, search=$search, offset=$offset");

try {
    $params = [];
    $whereConditions = ["req.status_id != 8"];
    
    // Filtros según rol
    if (proveedor()) {
        error_log("Rol: Proveedor");
        if (!defined('USER') || empty(USER["categories"])) {
            error_log("Proveedor sin categorías");
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001001, $result);
            exit;
        }
        $whereConditions[] = "req.category_id IN (" . USER["categories"] . ")";
    } elseif (comercial()) {
        error_log("Rol: Comercial");
        if (!defined('USER')) {
            error_log("USER no definido");
            json_response("ko", "USER no definido", 5001358107);
            exit;
        }
        
        $query = "SELECT id FROM sales_codes WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($code_ids)) {
            error_log("Comercial sin códigos");
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001002, $result);
            exit;
        }
        
        $code_ids_str = implode(",", $code_ids);
        $query = "SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($code_ids_str)";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($customer_ids)) {
            error_log("Comercial sin clientes");
            $result = [
                'data' => [],
                'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0, 'per_page' => $limit, 'from' => 0, 'to' => 0]
            ];
            json_response("ok", "", 9200001003, $result);
            exit;
        }
        
        $customer_ids_str = implode(",", $customer_ids);
        $whereConditions[] = "req.user_id IN ($customer_ids_str)";
    } else {
        error_log("Rol: Admin");
    }
    
    // Búsqueda
    if (!empty($search)) {
        error_log("Aplicando búsqueda: $search");
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
    error_log("WHERE: $whereClause");
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN users u ON u.id = req.user_id
        WHERE $whereClause
    ";
    
    error_log("Count query preparando...");
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $row = $stmt->fetch();
    $totalRecords = intval($row['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    error_log("Total records: $totalRecords, Total pages: $totalPages");
    
    // Obtener datos
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
    
    error_log("Data query preparando...");
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    error_log("Registros obtenidos: " . count($requests));
    
    // Formatear
    $formattedRequests = [];
    foreach ($requests as $i => $req) {
        error_log("Formateando registro $i");
        
        $hasNotification = false;
        // Verificar si NOTIFICATIONS existe
        if (defined('NOTIFICATIONS') && is_array(NOTIFICATIONS)) {
            foreach (NOTIFICATIONS as $n) {
                if (isset($n['request_id']) && $n['request_id'] == $req['id'] && isset($n['status']) && $n['status'] == 0) {
                    $hasNotification = true;
                    break;
                }
            }
        }
        
        // Formatear fecha - verificar si fdate existe
        $requestDate = '-';
        $updatedAt = '-';
        
        if (function_exists('fdate')) {
            $requestDate = is_null($req['request_date']) ? '-' : fdate($req['request_date']);
            $updatedAt = is_null($req['updated_at']) ? '-' : fdate($req['updated_at']);
        } else {
            error_log("ADVERTENCIA: fdate() no existe, usando formato básico");
            $requestDate = is_null($req['request_date']) ? '-' : date('d/m/Y', strtotime($req['request_date']));
            $updatedAt = is_null($req['updated_at']) ? '-' : date('d/m/Y', strtotime($req['updated_at']));
        }
        
        // Badge - verificar si get_badge_html existe
        $statusBadge = '';
        if (function_exists('get_badge_html')) {
            $statusBadge = get_badge_html($req['status'] ?? $req['status_id']);
        } else {
            error_log("ADVERTENCIA: get_badge_html() no existe");
            $statusBadge = '<span class="badge">' . htmlspecialchars($req['status'] ?? 'N/A') . '</span>';
        }
        
        $formattedRequests[] = [
            'id' => $req['id'],
            'customer_full_name' => trim($req['customer_full_name']),
            'category_name' => $req['category_name'] ?? '',
            'status' => $req['status'] ?? '',
            'status_id' => $req['status_id'],
            'request_date' => $requestDate,
            'updated_at' => $updatedAt,
            'has_notification' => $hasNotification,
            'status_badge' => $statusBadge
        ];
    }
    
    error_log("Formato completado, enviando respuesta...");
    
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
    error_log("=== ERROR CRÍTICO ===");
    error_log("Mensaje: " . $e->getMessage());
    error_log("Archivo: " . $e->getFile());
    error_log("Línea: " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    json_response("ko", "Error: " . $e->getMessage() . " en línea " . $e->getLine(), 9500001000);
}

error_log("=== API REQUESTS PAGINATED END ===");