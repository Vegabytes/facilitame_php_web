<?php
// controller/api-incidents-paginated-customer.php

if (!cliente()) {
    json_response("ko", "No autorizado", 4031359001);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $user_id = (int)USER["id"];
    
    $params = [$user_id];
    $whereConditions = ["r.user_id = ?"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            c.name LIKE ?
            OR i.details LIKE ?
            OR i.id LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM request_incidents i
        JOIN requests r ON i.request_id = r.id
        JOIN categories c ON r.category_id = c.id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = $row ? intval($row['total']) : 0;
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos paginados
    $dataQuery = "
        SELECT 
            i.id,
            i.request_id,
            i.details,
            i.status_id,
            i.incident_category_id,
            i.created_at,
            i.updated_at,
            r.category_id,
            c.name AS category_name,
            r.status_id AS request_status_id,
            ic.name AS incident_category_name
        FROM request_incidents i
        JOIN requests r ON i.request_id = r.id
        JOIN categories c ON r.category_id = c.id
        LEFT JOIN incident_categories ic ON ic.id = i.incident_category_id
        WHERE $whereClause
        ORDER BY i.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $incidents = $stmt->fetchAll();
    
    $formattedIncidents = [];
    foreach ($incidents as $inc) {
        $formattedIncidents[] = [
            'id' => $inc['id'],
            'request_id' => $inc['request_id'],
            'details' => $inc['details'] ?? '',
            'status_id' => intval($inc['status_id']),
            'incident_category_id' => intval($inc['incident_category_id']),
            'incident_category_name' => $inc['incident_category_name'] ?? 'General',
            'category_name' => $inc['category_name'] ?? '',
            'request_status_id' => intval($inc['request_status_id']),
            'created_at' => fdate($inc['created_at']),
            'updated_at' => $inc['updated_at'] ? fdate($inc['updated_at']) : '-'
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
    
    json_response("ok", "", 9200010001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-incidents-paginated-customer: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500010001);
}