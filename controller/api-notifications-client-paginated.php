<?php
if (!cliente()) {
    json_response("ko", "No autorizado", 4031358201);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
$offset = ($page - 1) * $limit;
$userId = USER["id"];

try {
    // Base WHERE - usa el nuevo índice directamente
    $whereConditions = ["n.user_id = :user_id"];
    $params = [':user_id' => $userId];
    
    // Filtro estado notificación
    if ($status !== null) {
        $whereConditions[] = "n.status = :notif_status";
        $params[':notif_status'] = $status;
    }
    
    // Búsqueda
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(cat.name LIKE :search OR CAST(req.id AS CHAR) LIKE :search2)";
        $params[':search'] = $searchTerm;
        $params[':search2'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Query principal con SQL_CALC_FOUND_ROWS
    $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            n.id AS notification_id,
            n.status AS notification_status,
            n.title AS notification_title,
            n.description AS notification_description,
            n.created_at AS notification_created_at,
            n.request_id AS id,
            req.status_id,
            cat.name AS category_name,
            sta.status_name AS status
        FROM notifications n
        INNER JOIN requests req ON req.id = n.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
        ORDER BY n.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    // Total en una sola llamada
    $totalRecords = intval($pdo->query("SELECT FOUND_ROWS()")->fetchColumn());
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Formatear
    $data = [];
    foreach ($notifications as $n) {
        $data[] = [
            'id' => $n['id'],
            'notification_id' => $n['notification_id'],
            'notification_status' => $n['notification_status'],
            'notification_title' => $n['notification_title'] ?: '',
            'notification_description' => $n['notification_description'] ?: '',
            'category_name' => $n['category_name'] ?: '',
            'status' => $n['status'] ?: '',
            'status_id' => $n['status_id'],
            'time_from' => $n['notification_created_at'] ? fdate($n['notification_created_at']) : '-',
            'is_unread' => ($n['notification_status'] == 0)
        ];
    }
    
    json_response("ok", "", 9200101000, [
        'data' => $data,
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
    error_log("Error api-notifications-client-paginated: " . $e->getMessage());
    json_response("ko", "Error interno", 9500101000);
}