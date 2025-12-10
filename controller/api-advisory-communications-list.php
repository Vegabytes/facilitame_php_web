<?php
global $pdo;
header('Content-Type: application/json');
if (!asesoria()) {
    json_response("ko", "No autorizado", 4001);
}
// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();
if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 4004);
}
$advisory_id = $advisory['id'];
// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 25;
$offset = ($page - 1) * $limit;
// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$importance = isset($_GET['importance']) ? trim($_GET['importance']) : '';
// Construir WHERE
$where = ["ac.advisory_id = ?"];
$params = [$advisory_id];
if ($search !== '') {
    $where[] = "(ac.subject LIKE ? OR ac.message LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($importance !== '' && in_array($importance, ['leve', 'media', 'importante'])) {
    $where[] = "ac.importance = ?";
    $params[] = $importance;
}
$where_clause = implode(' AND ', $where);
// Contar total
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM advisory_communications ac
    WHERE $where_clause
");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
// Obtener registros con conteo de destinatarios vía subquery
$stmt = $pdo->prepare("
    SELECT
        ac.id,
        ac.subject,
        ac.message,
        ac.importance,
        ac.target_type,
        ac.target_subtype,
        ac.created_at,
        (SELECT COUNT(*) FROM advisory_communication_recipients acr WHERE acr.communication_id = ac.id) as total_recipients,
        (SELECT COUNT(*) FROM advisory_communication_recipients acr WHERE acr.communication_id = ac.id AND acr.is_read = 1) as read_count
    FROM advisory_communications ac
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
// Formatear datos
$importance_labels = [
    'leve' => ['label' => 'Informativo', 'class' => 'info', 'icon' => 'information'],
    'media' => ['label' => 'Normal', 'class' => 'primary', 'icon' => 'notification'],
    'importante' => ['label' => 'Importante', 'class' => 'danger', 'icon' => 'notification-bing']
];
$target_labels = [
    'all' => 'Todos los clientes',
    'autonomo' => 'Autónomos',
    'empresa' => 'Empresas',
    'particular' => 'Particulares',
    'comunidad' => 'Comunidades',
    'asociacion' => 'Asociaciones',
    'selected' => 'Selección manual'
];
// Obtener archivos adjuntos
$stmt_files = $pdo->prepare("SELECT id, filename, url, mime_type, filesize FROM advisory_communication_files WHERE communication_id = ?");

$formatted = [];
foreach ($communications as $comm) {
    $imp = $importance_labels[$comm['importance']] ?? $importance_labels['media'];

    // Obtener archivos de esta comunicación
    $stmt_files->execute([$comm['id']]);
    $attachments = $stmt_files->fetchAll();

    $formatted[] = [
        'id' => $comm['id'],
        'subject' => $comm['subject'],
        'message' => $comm['message'],
        'importance' => $comm['importance'],
        'importance_label' => $imp['label'],
        'importance_class' => $imp['class'],
        'importance_icon' => $imp['icon'],
        'target_type' => $comm['target_type'],
        'target_label' => $target_labels[$comm['target_type']] ?? $comm['target_type'],
        'created_at' => date('d/m/Y H:i', strtotime($comm['created_at'])),
        'total_recipients' => (int)$comm['total_recipients'],
        'read_count' => (int)$comm['read_count'],
        'pending_count' => (int)$comm['total_recipients'] - (int)$comm['read_count'],
        'attachments' => $attachments,
        'attachments_count' => count($attachments)
    ];
}
$from = $total_records > 0 ? $offset + 1 : 0;
$to = min($offset + $limit, $total_records);
json_response("ok", "Comunicaciones obtenidas correctamente", 2001, [
    'data' => $formatted,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'per_page' => $limit,
        'from' => $from,
        'to' => $to
    ]
]);