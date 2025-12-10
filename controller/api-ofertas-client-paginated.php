<?php
if (!cliente()) {
    json_response("ko", "No autorizado", 4031358203);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $userId = USER["id"];
    $params = [':user_id' => $userId];
    $whereConditions = ["req.user_id = :user_id", "o.deleted_at IS NULL"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(cat.name LIKE :search OR o.offer_title LIKE :search2 OR CAST(req.id AS CHAR) LIKE :search3)";
        $params[':search'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Query con SQL_CALC_FOUND_ROWS
    $dataQuery = "
        SELECT SQL_CALC_FOUND_ROWS
            o.id,
            o.request_id,
            o.offer_title,
            o.status_id,
            o.total_amount,
            o.created_at,
            o.activated_at,
            cat.name AS category_name,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS proveedor_name
        FROM offers o
        INNER JOIN requests req ON req.id = o.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN users u ON u.id = o.provider_id
        WHERE $whereClause
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $ofertas = $stmt->fetchAll();
    
    // Total sin repetir query
    $totalRecords = intval($pdo->query("SELECT FOUND_ROWS()")->fetchColumn());
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Formatear
    $data = [];
    foreach ($ofertas as $o) {
        $isActivated = !empty($o['activated_at']);
        
        $data[] = [
            'id' => $o['id'],
            'request_id' => $o['request_id'],
            'titulo' => $o['offer_title'] ?: 'Oferta',
            'category_name' => $o['category_name'] ?: '',
            'proveedor_name' => trim($o['proveedor_name']),
            'ahorro' => floatval($o['total_amount'] ?? 0),
            'status' => $isActivated ? 'aceptada' : 'disponible',
            'badge_color' => $isActivated ? 'success' : 'primary',
            'badge_text' => $isActivated ? 'Aceptada' : 'Disponible',
            'created_at' => $o['created_at'] ? fdate($o['created_at']) : '-'
        ];
    }
    
    json_response("ok", "", 9200103000, [
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
    error_log("Error en api-ofertas-client-paginated: " . $e->getMessage());
    json_response("ko", "Error interno", 9500103000);
}