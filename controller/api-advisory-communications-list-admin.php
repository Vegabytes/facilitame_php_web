<?php
/**
 * API: Lista de comunicaciones de una asesoría (Admin)
 * /controller/api-advisory-communications-list-admin.php
 */

global $pdo;
header('Content-Type: application/json');

if (!admin()) {
    json_response("ko", "No autorizado", 4001);
}

// Obtener advisory_id desde GET
$advisory_id = isset($_GET['advisory_id']) ? (int)$_GET['advisory_id'] : 0;

if (!$advisory_id) {
    json_response("ko", "advisory_id requerido", 4000);
}

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
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
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

$formatted = [];
foreach ($communications as $comm) {
    $imp = $importance_labels[$comm['importance']] ?? $importance_labels['media'];
    $formatted[] = [
        'id' => $comm['id'],
        'title' => $comm['subject'],
        'subject' => $comm['subject'],
        'message' => $comm['message'],
        'importance' => $imp['class'],
        'importance_label' => $imp['label'],
        'importance_class' => $imp['class'],
        'importance_icon' => $imp['icon'],
        'target_type' => $comm['target_type'],
        'target_label' => $target_labels[$comm['target_type']] ?? $comm['target_type'],
        'created_at' => date('d/m/Y H:i', strtotime($comm['created_at'])),
        'total_recipients' => (int)$comm['total_recipients'],
        'read_count' => (int)$comm['read_count'],
        'pending_count' => (int)$comm['total_recipients'] - (int)$comm['read_count']
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