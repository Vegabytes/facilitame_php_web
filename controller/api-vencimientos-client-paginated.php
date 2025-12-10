<?php
if (!cliente()) {
    json_response("ko", "No autorizado", 4031358202);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $userId = USER["id"];
    $params = [':user_id' => $userId];
    
    $whereConditions = [
        "req.user_id = :user_id",
        "req.status_id = 7",  // Solo finalizadas (más específico)
        "o.deleted_at IS NULL",
        "o.activated_at IS NOT NULL",
        "o.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"
    ];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(cat.name LIKE :search OR CAST(req.id AS CHAR) LIKE :search2)";
        $params[':search'] = $searchTerm;
        $params[':search2'] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Query con SQL_CALC_FOUND_ROWS
    $dataQuery = "
        SELECT SQL_CALC_FOUND_ROWS
            req.id AS request_id,
            cat.name AS category_name,
            o.expires_at AS fecha_vencimiento,
            o.total_amount AS ahorro_total,
            DATEDIFF(o.expires_at, CURDATE()) AS dias_para_vencer
        FROM offers o
        INNER JOIN requests req ON req.id = o.request_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        WHERE $whereClause
        ORDER BY o.expires_at ASC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $vencimientos = $stmt->fetchAll();
    
    // Total sin repetir query
    $totalRecords = intval($pdo->query("SELECT FOUND_ROWS()")->fetchColumn());
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Formatear
    $data = [];
    foreach ($vencimientos as $v) {
        $dias = intval($v['dias_para_vencer']);
        
        if ($dias < 30) {
            $urgencia = 'danger';
            $urgenciaLabel = 'Urgente';
        } elseif ($dias < 60) {
            $urgencia = 'warning';
            $urgenciaLabel = 'Próximo';
        } else {
            $urgencia = 'info';
            $urgenciaLabel = 'Planificado';
        }
        
        $data[] = [
            'id' => $v['request_id'],
            'request_id' => $v['request_id'],
            'category_name' => $v['category_name'] ?: '',
            'fecha_vencimiento' => $v['fecha_vencimiento'] ? fdate($v['fecha_vencimiento']) : '-',
            'dias_para_vencer' => $dias,
            'ahorro_total' => floatval($v['ahorro_total'] ?? 0),
            'urgencia' => $urgencia,
            'urgencia_label' => $urgenciaLabel
        ];
    }
    
    json_response("ok", "", 9200102000, [
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
    error_log("Error en api-vencimientos-client-paginated: " . $e->getMessage());
    json_response("ko", "Error interno", 9500102000);
}