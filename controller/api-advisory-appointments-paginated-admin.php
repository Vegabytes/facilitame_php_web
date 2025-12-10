<?php
/**
 * API Admin: Citas de una asesoría
 * GET /api/advisory-appointments-paginated-admin?advisory_id=13
 */
header('Content-Type: application/json');

if (!admin()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$advisory_id = isset($_GET['advisory_id']) ? (int)$_GET['advisory_id'] : 0;
if (!$advisory_id) {
    json_response("ko", "advisory_id requerido", 400);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $limit;

try {
    $params = [$advisory_id];
    $whereConditions = ["aa.advisory_id = ?"];
    
    if (!empty($status)) {
        $whereConditions[] = "aa.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM advisory_appointments aa
        WHERE $whereClause
    ";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = $row ? (int)$row['total'] : 0;
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener citas
    $dataQuery = "
        SELECT 
            aa.id,
            aa.status,
            aa.reason,
            aa.department,
            aa.scheduled_date,
            aa.proposed_date,
            aa.needs_confirmation_from,
            aa.created_at,
            aa.updated_at,
            CONCAT(u.name, ' ', u.lastname) as customer_name,
            u.email as customer_email
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        WHERE $whereClause
        ORDER BY 
            CASE aa.status 
                WHEN 'solicitado' THEN 1 
                WHEN 'agendado' THEN 2 
                ELSE 3 
            END,
            COALESCE(aa.scheduled_date, aa.proposed_date, aa.created_at) DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
    $formattedAppointments = [];
    foreach ($appointments as $a) {
        $formattedAppointments[] = [
            'id' => (int)$a['id'],
            'status' => $a['status'],
            'reason' => $a['reason'] ?? '',
            'department' => $a['department'] ?? '',
            'scheduled_date' => $a['scheduled_date'] ? date('d/m/Y H:i', strtotime($a['scheduled_date'])) : null,
            'proposed_date' => $a['proposed_date'] ? date('d/m/Y H:i', strtotime($a['proposed_date'])) : null,
            'needs_confirmation_from' => $a['needs_confirmation_from'],
            'customer_name' => $a['customer_name'],
            'customer_email' => $a['customer_email'],
            'created_at' => $a['created_at'] ? date('d/m/Y', strtotime($a['created_at'])) : '-'
        ];
    }
    
    json_response("ok", "", 200, [
        'data' => $formattedAppointments,
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
    error_log("Error en api-advisory-appointments-paginated-admin: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 500);
}