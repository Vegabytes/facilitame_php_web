<?php
/**
 * API: Listado paginado de citas (Asesoria) v2
 * GET /api-advisory-appointments-paginated
 * 
 * Cambios v2:
 * - ORDER BY prioriza needs_confirmation_from='advisory' (propuestas de clientes)
 * - Ya devuelve proposed_date, needs_confirmation_from, proposed_by via aa.*
 */
global $pdo;

if (!asesoria()) {
    json_response("ko", "Acceso denegado", 403);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$offset = ($page - 1) * $limit;

try {
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response("ko", "Asesoria no encontrada", 404);
    }
    
    // Construir query
    $where_clauses = ["aa.advisory_id = ?"];
    $params = [$advisory['id']];
    
    if (!empty($search)) {
        $where_clauses[] = "(u.name LIKE ? OR u.lastname LIKE ? OR aa.reason LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($status)) {
        $where_clauses[] = "aa.status = ?";
        $params[] = $status;
    } else {
        // Por defecto ocultar finalizadas y canceladas
        $where_clauses[] = "aa.status NOT IN ('finalizado', 'cancelado')";
    }
    
    if (!empty($department)) {
        $where_clauses[] = "aa.department = ?";
        $params[] = $department;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    // Contar total
    $count_sql = "
        SELECT COUNT(*) as total
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        WHERE $where_sql
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetch()['total'];
    $total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
    
    // Obtener citas con LEFT JOIN optimizado para mensajes no leidos
    // ORDER BY v2: Prioriza citas que necesitan confirmacion de asesoria
    $sql = "
        SELECT 
            aa.*,
            CONCAT(u.name, ' ', u.lastname) as customer_name,
            u.email as customer_email,
            COALESCE(unread.cnt, 0) as unread_messages
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        LEFT JOIN (
            SELECT appointment_id, COUNT(*) as cnt
            FROM advisory_messages
            WHERE sender_type = 'customer' AND (is_read = 0 OR is_read IS NULL)
            GROUP BY appointment_id
        ) unread ON unread.appointment_id = aa.id
        WHERE $where_sql
        ORDER BY 
            CASE WHEN aa.needs_confirmation_from = 'advisory' THEN 0 ELSE 1 END,
            CASE WHEN COALESCE(unread.cnt, 0) > 0 THEN 0 ELSE 1 END,
            CASE aa.status 
                WHEN 'solicitado' THEN 1 
                WHEN 'agendado' THEN 2 
                ELSE 3 
            END,
            CASE 
                WHEN aa.status = 'agendado' AND aa.scheduled_date >= NOW() THEN 0
                ELSE 1
            END,
            COALESCE(aa.scheduled_date, aa.proposed_date) ASC,
            aa.updated_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
    // From y to
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
    error_log("Error en api-advisory-appointments-paginated: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}