<?php
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
        'communications' => [],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 0,
            'total_records' => 0,
            'per_page' => 25,
            'from' => 0,
            'to' => 0
        ]
    ]);
}
$advisory_id = $advisory['advisory_id'];
// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 25;
$offset = ($page - 1) * $limit;
// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$importance = isset($_GET['importance']) ? trim($_GET['importance']) : '';
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
// Construir WHERE
$where = ["ac.advisory_id = ?", "acr.customer_id = ?"];
$params = [$advisory_id, USER['id']];
if ($search !== '') {
    $where[] = "(ac.subject LIKE ? OR ac.message LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($importance !== '' && in_array($importance, ['leve', 'media', 'importante'])) {
    $where[] = "ac.importance = ?";
    $params[] = $importance;
}
if ($month > 0 && $month <= 12) {
    $where[] = "MONTH(ac.created_at) = ?";
    $params[] = $month;
}
$where_clause = implode(' AND ', $where);
// Contar total
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM advisory_communications ac
    INNER JOIN advisory_communication_recipients acr ON acr.communication_id = ac.id
    WHERE $where_clause
");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);
// Obtener registros
$stmt = $pdo->prepare("
    SELECT ac.id, ac.subject, ac.message, ac.importance, ac.created_at,
           acr.is_read, acr.read_at,
           DATE_FORMAT(ac.created_at, '%d %b %Y') as created_at_display
    FROM advisory_communications ac
    INNER JOIN advisory_communication_recipients acr ON acr.communication_id = ac.id
    WHERE $where_clause
    ORDER BY ac.created_at DESC
    LIMIT :pagination_limit OFFSET :pagination_offset
");
// Bind los parámetros de WHERE
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param);
}
// Bind LIMIT y OFFSET como enteros
$stmt->bindValue(':pagination_limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':pagination_offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$communications = $stmt->fetchAll();

// Obtener archivos adjuntos para cada comunicación
$stmt_files = $pdo->prepare("SELECT id, filename, url, mime_type, filesize FROM advisory_communication_files WHERE communication_id = ?");
foreach ($communications as &$comm) {
    $stmt_files->execute([$comm['id']]);
    $comm['attachments'] = $stmt_files->fetchAll();
}
unset($comm);
$from = $total_records > 0 ? $offset + 1 : 0;
$to = min($offset + $limit, $total_records);
json_response("ok", "Comunicaciones obtenidas correctamente", 2001, [
    'communications' => $communications,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'per_page' => $limit,
        'from' => $from,
        'to' => $to
    ]
]);