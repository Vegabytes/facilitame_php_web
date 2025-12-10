<?php
/**
 * API: api-advisories-paginated-admin.php
 * Asesorías paginadas para ADMIN
 * 
 * GET params:
 * - page: número de página (default 1)
 * - limit: registros por página (default 25, max 100)
 * - search: búsqueda por razón social, CIF, email, código
 * - status: filtrar por estado (pendiente/activo/suspendido)
 * - plan: filtrar por plan (gratuito/basic/estandar/pro/premium)
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$plan = isset($_GET['plan']) ? trim($_GET['plan']) : '';
$offset = ($page - 1) * $limit;

try {
    $whereConditions = ["1=1"];
    $params = [];
    
    // Filtro de búsqueda
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            a.razon_social LIKE :search1
            OR a.cif LIKE :search2
            OR a.email_empresa LIKE :search3
            OR a.codigo_identificacion LIKE :search4
            OR CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE :search5
        )";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    // Filtro por estado
    if (!empty($status) && in_array($status, ['pendiente', 'activo', 'suspendido'])) {
        $whereConditions[] = "a.estado = :status";
        $params[':status'] = $status;
    }
    
    // Filtro por plan
    if (!empty($plan) && in_array($plan, ['gratuito', 'basic', 'estandar', 'pro', 'premium'])) {
        $whereConditions[] = "a.plan = :plan";
        $params[':plan'] = $plan;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // COUNT total
    $countQuery = "
        SELECT COUNT(DISTINCT a.id)
        FROM advisories a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = (int) $stmt->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // KPIs globales (sin filtros de búsqueda, solo para mostrar totales reales)
    $kpiQuery = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activas,
            SUM(CASE WHEN estado = 'pendiente' OR estado IS NULL OR estado = '' THEN 1 ELSE 0 END) AS pendientes,
            SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) AS suspendidas
        FROM advisories
    ";
    $stmtKpi = $pdo->query($kpiQuery);
    $kpis = $stmtKpi->fetch(PDO::FETCH_ASSOC);
    
    // Total clientes gestionados por todas las asesorías
    $stmtClientes = $pdo->query("SELECT COUNT(*) FROM customers_advisories");
    $totalClientes = (int) $stmtClientes->fetchColumn();
    
    // Si no hay resultados
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
            ],
            'kpis' => [
                'total' => (int) $kpis['total'],
                'activas' => (int) $kpis['activas'],
                'pendientes' => (int) $kpis['pendientes'],
                'suspendidas' => (int) $kpis['suspendidas'],
                'total_clientes' => $totalClientes
            ]
        ]);
    }
    
    // DATA con conteos
    $dataQuery = "
        SELECT 
            a.id,
            a.razon_social,
            a.cif,
            a.email_empresa,
            a.codigo_identificacion,
            a.plan,
            a.estado,
            a.created_at,
            a.user_id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS user_name,
            u.email AS user_email,
            COUNT(DISTINCT ca.id) AS total_customers,
            COUNT(DISTINCT CASE WHEN ap.status IN ('solicitado', 'agendado') THEN ap.id END) AS pending_appointments,
            COUNT(DISTINCT CASE WHEN ai.is_processed = 0 THEN ai.id END) AS pending_invoices
        FROM advisories a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN customers_advisories ca ON ca.advisory_id = a.id
        LEFT JOIN advisory_appointments ap ON ap.advisory_id = a.id
        LEFT JOIN advisory_invoices ai ON ai.advisory_id = a.id
        WHERE $whereClause
        GROUP BY a.id, a.razon_social, a.cif, a.email_empresa, a.codigo_identificacion, 
                 a.plan, a.estado, a.created_at, a.user_id, u.name, u.lastname, u.email
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $advisories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    $formattedAdvisories = [];
    foreach ($advisories as $a) {
        $formattedAdvisories[] = [
            'id' => (int) $a['id'],
            'razon_social' => $a['razon_social'] ?? '',
            'cif' => $a['cif'] ?? '',
            'email_empresa' => $a['email_empresa'] ?? '',
            'codigo_identificacion' => $a['codigo_identificacion'] ?? '',
            'plan' => $a['plan'] ?? 'gratuito',
            'estado' => $a['estado'] ?? 'pendiente',
            'created_at' => $a['created_at'] ? fdate($a['created_at']) : '-',
            'user_id' => $a['user_id'] ? (int) $a['user_id'] : null,
            'user_name' => trim($a['user_name'] ?? ''),
            'user_email' => $a['user_email'] ?? '',
            'total_customers' => (int) $a['total_customers'],
            'pending_appointments' => (int) $a['pending_appointments'],
            'pending_invoices' => (int) $a['pending_invoices']
        ];
    }
    
    json_response("ok", "", 9200002000, [
        'data' => $formattedAdvisories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $totalRecords)
        ],
        'kpis' => [
            'total' => (int) $kpis['total'],
            'activas' => (int) $kpis['activas'],
            'pendientes' => (int) $kpis['pendientes'],
            'suspendidas' => (int) $kpis['suspendidas'],
            'total_clientes' => $totalClientes
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-advisories-paginated-admin: " . $e->getMessage() . " - Line: " . $e->getLine());
    json_response("ko", "Error: " . $e->getMessage(), 9500002000);
}