<?php
header('Content-Type: application/json');

if (!asesoria()) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

$advisory_user_id = $_SESSION['user_id'];

// Obtener IDs de clientes del asesor desde customers_advisories
$clientsStmt = $pdo->prepare("SELECT customer_id FROM customers_advisories WHERE advisory_id = ?");
$clientsStmt->execute([$advisory_user_id]);
$client_ids = $clientsStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($client_ids)) {
    echo json_encode([
        'status' => 'ok',
        'data' => [
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => 0,
                'total_pages' => 0,
                'from' => 0,
                'to' => 0
            ]
        ]
    ]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($client_ids), '?'));

// Query base - usando commissions_admin
$baseQuery = "FROM commissions_admin ca
              INNER JOIN requests r ON ca.request_id = r.id
              INNER JOIN users u ON r.user_id = u.id
              LEFT JOIN categories c ON r.category_id = c.id
              WHERE r.user_id IN ($placeholders)";

$params = $client_ids;

// Filtro de búsqueda
if (!empty($search)) {
    $baseQuery .= " AND (u.name LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Contar total
$countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Obtener datos paginados
$dataQuery = "SELECT 
                ca.id,
                ca.value as amount,
                ca.recurring,
                ca.activated_at,
                ca.deactivated_at,
                ca.request_id,
                CONCAT(u.name, ' ', u.lastname) as customer_name,
                COALESCE(c.name, 'Sin categoría') as category_name,
                CASE 
                    WHEN ca.deactivated_at IS NOT NULL THEN 'desactivada'
                    WHEN ca.activated_at IS NOT NULL THEN 'activa'
                    ELSE 'pendiente'
                END as status
              " . $baseQuery . "
              ORDER BY ca.activated_at DESC, ca.id DESC
              LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($dataQuery);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear fechas
foreach ($data as &$row) {
    $row['activated_at'] = $row['activated_at'] ? date('d/m/Y', strtotime($row['activated_at'])) : '-';
    $row['deactivated_at'] = $row['deactivated_at'] ? date('d/m/Y', strtotime($row['deactivated_at'])) : '-';
    $row['recurring'] = $row['recurring'] ? 'Sí' : 'No';
}

$from = $totalRecords > 0 ? $offset + 1 : 0;
$to = min($offset + $limit, $totalRecords);

echo json_encode([
    'status' => 'ok',
    'data' => [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $from,
            'to' => $to
        ]
    ]
]);