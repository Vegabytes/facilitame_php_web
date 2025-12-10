<?php
/**
 * API: Listado paginado de citas (Cliente) - v3 optimizada
 * GET /api-customer-appointments-paginated
 */
global $pdo;

if (!cliente()) {
    json_response("ko", "Acceso denegado", 403);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 15;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $limit;

try {
    $customer_id = (int)USER['id'];
    
    // Construir WHERE
    $where = ["aa.customer_id = ?"];
    $params = [$customer_id];

    // Por defecto ocultar finalizadas y canceladas (solo mostrar si se filtra explícitamente)
    if ($status !== '' && in_array($status, ['solicitado', 'agendado', 'finalizado', 'cancelado'])) {
        $where[] = "aa.status = ?";
        $params[] = $status;
    } else {
        // Sin filtro explícito: excluir finalizadas y canceladas
        $where[] = "aa.status NOT IN ('finalizado', 'cancelado')";
    }
    
    $where_sql = implode(' AND ', $where);
    
    // Query única con SQL_CALC_FOUND_ROWS
    $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            aa.id,
            aa.advisory_id,
            aa.type,
            aa.department,
            aa.reason,
            aa.status,
            aa.scheduled_date,
            aa.proposed_date,
            aa.needs_confirmation_from,
            aa.proposed_by,
            aa.created_at,
            aa.updated_at,
            a.razon_social AS advisory_name,
            (SELECT COUNT(*) FROM advisory_messages am
             WHERE am.appointment_id = aa.id
             AND am.sender_type = 'advisory'
             AND am.is_read = 0) AS unread_messages
        FROM advisory_appointments aa
        INNER JOIN advisories a ON a.id = aa.advisory_id
        WHERE $where_sql
        ORDER BY
            (aa.needs_confirmation_from = 'customer') DESC,
            FIELD(aa.status, 'solicitado', 'agendado', 'finalizado', 'cancelado'),
            aa.updated_at DESC
        LIMIT :pagination_limit OFFSET :pagination_offset
    ";

    $stmt = $pdo->prepare($sql);
    // Bind los parámetros de WHERE
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    // Bind LIMIT y OFFSET como enteros
    $stmt->bindValue(':pagination_limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':pagination_offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $appointments = $stmt->fetchAll();
    
    // Total con FOUND_ROWS
    $total_records = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
    
    $from = $total_records > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total_records);
    
    json_response("ok", "Citas obtenidas", 200, [
        'appointments' => $appointments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $limit,
            'from' => $from,
            'to' => $to
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-customer-appointments-paginated: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}