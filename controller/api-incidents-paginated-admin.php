<?php
/**
 * API: api-incidents-paginated-admin.php
 * Paginación y filtrado server-side para ADMIN
 * Solo incidencias activas (excluye cerradas status_id=10)
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358101);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $whereConditions = ["inc.status_id != 10"]; // Excluir cerradas
    
    // Búsqueda
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search1
            OR u.email LIKE :search2
            OR cat.name LIKE :search3
            OR ist.name LIKE :search4
            OR inc.details LIKE :search5
            OR CAST(inc.request_id AS CHAR) LIKE :search6
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
        $params[':search6'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*)
        FROM request_incidents inc
        LEFT JOIN requests req ON req.id = inc.request_id
        LEFT JOIN users u ON u.id = req.user_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN incident_statuses ist ON ist.id = inc.status_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Si no hay resultados, retornar vacío
    if ($totalRecords === 0) {
        json_response("ok", "", 9200002000, [
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
    
    // Obtener datos con notificaciones en el mismo query
    $dataQuery = "
        SELECT
            inc.id,
            inc.request_id,
            inc.status_id,
            ist.name AS status_name,
            inc.details,
            inc.created_at,
            req.user_id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_name,
            u.email AS customer_email,
            cat.name AS category_name,
            (SELECT COUNT(*) FROM notifications n
             WHERE n.request_id = inc.request_id
             AND n.receiver_id = :notif_user_id
             AND n.status = 0) > 0 AS has_notification
        FROM request_incidents inc
        LEFT JOIN requests req ON req.id = inc.request_id
        LEFT JOIN users u ON u.id = req.user_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN incident_statuses ist ON ist.id = inc.status_id
        WHERE $whereClause
        ORDER BY inc.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $params[':notif_user_id'] = USER['id'];
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $incidents = $stmt->fetchAll();
    
    // Formatear resultados
    $formattedIncidents = array_map(function($inc) {
        return [
            'id' => (int) $inc['id'],
            'request_id' => (int) $inc['request_id'],
            'user_id' => (int) $inc['user_id'],
            'customer_name' => trim($inc['customer_name']),
            'customer_email' => $inc['customer_email'] ?? '',
            'category_name' => $inc['category_name'] ?? '',
            'status_id' => (int) $inc['status_id'],
            'status_name' => $inc['status_name'] ?? '',
            'details' => $inc['details'] ?? '',
            'created_at' => is_null($inc['created_at']) ? '-' : fdate($inc['created_at']),
            'has_notification' => (bool) $inc['has_notification']
        ];
    }, $incidents);
    
    json_response("ok", "", 9200002000, [
        'data' => $formattedIncidents,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-incidents-paginated-admin: " . $e->getMessage());
    json_response("ko", "Error interno", 9500002000);
}