<?php
global $pdo;
header('Content-Type: application/json');

if (!cliente()) {
    json_response("ko", "No autorizado", 4001);
}

// Obtener advisory_id del cliente
$stmt = $pdo->prepare("
    SELECT ca.advisory_id 
    FROM customers_advisories ca
    WHERE ca.customer_id = ?
");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ok", "No tienes una asesoría vinculada", 2001, [
        'invoices' => [],
        'total' => 0,
        'page' => 1,
        'has_more' => false
    ]);
}

$advisory_id = $advisory['advisory_id'];

// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtros
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 0;
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construir WHERE
$where = ["ai.advisory_id = ?", "ai.customer_id = ?"];
$params = [$advisory_id, USER['id']];

if ($month > 0 && $month <= 12) {
    $where[] = "ai.month = ?";
    $params[] = $month;
}

// Filtro por trimestre
if ($quarter > 0 && $quarter <= 4) {
    $quarterMonths = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12]
    ];
    $months = $quarterMonths[$quarter];
    $where[] = "ai.month IN (?, ?, ?)";
    $params[] = $months[0];
    $params[] = $months[1];
    $params[] = $months[2];
}

if ($tag !== '') {
    $where[] = "ai.tag = ?";
    $params[] = $tag;
}

// Filtro por tipo (gasto/ingreso)
if ($type !== '' && in_array($type, ['gasto', 'ingreso'])) {
    $where[] = "ai.type = ?";
    $params[] = $type;
}

// Filtro de búsqueda
if ($search !== '') {
    $where[] = "(ai.original_name LIKE ? OR ai.filename LIKE ? OR ai.notes LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where);

// Contar total
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM advisory_invoices ai
    WHERE $where_clause
");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Obtener registros
$stmt = $pdo->prepare("
    SELECT
        ai.id,
        ai.filename,
        ai.original_name,
        ai.tag,
        ai.type,
        ai.notes,
        ai.is_processed,
        ai.created_at,
        DATE_FORMAT(ai.created_at, '%d %b %Y') as created_at_formatted
    FROM advisory_invoices ai
    WHERE $where_clause
    ORDER BY ai.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response("ok", "Facturas obtenidas correctamente", 2001, [
    'invoices' => $invoices,
    'total' => $total_records,
    'page' => $page,
    'has_more' => ($offset + $limit) < $total_records
]);