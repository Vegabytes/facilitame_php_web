<?php
header('Content-Type: application/json');

if (!asesoria()) {
    json_response("ko", "No autorizado", 4001);
}

global $pdo;

// Obtener el ID real de la asesoría
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_row = $stmt->fetch();

if (!$advisory_row) {
    json_response("ko", "Asesoría no encontrada", 4004);
}

$advisory_id = $advisory_row['id'];
$communication_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$communication_id) {
    json_response("ko", "ID de comunicación no válido", 4002);
}

// Obtener comunicación (verificando que pertenece a esta asesoría)
$stmt = $pdo->prepare("
    SELECT id, subject, message, importance, target_type, target_subtype, created_at
    FROM advisory_communications
    WHERE id = ? AND advisory_id = ?
");
$stmt->execute([$communication_id, $advisory_id]);
$communication = $stmt->fetch();

if (!$communication) {
    json_response("ko", "Comunicación no encontrada", 4004);
}

// Obtener destinatarios
$stmt = $pdo->prepare("
    SELECT 
        acr.id,
        acr.customer_id,
        acr.is_read,
        acr.sent_at,
        acr.read_at,
        acr.reminder_sent,
        u.name,
        u.lastname,
        u.email
    FROM advisory_communication_recipients acr
    INNER JOIN users u ON u.id = acr.customer_id
    WHERE acr.communication_id = ?
    ORDER BY acr.is_read ASC, u.name ASC
");
$stmt->execute([$communication_id]);
$recipients = $stmt->fetchAll();

// Formatear datos
$importance_labels = [
    'leve' => ['label' => 'Informativo', 'class' => 'info'],
    'media' => ['label' => 'Normal', 'class' => 'primary'],
    'importante' => ['label' => 'Importante', 'class' => 'danger']
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

$imp = $importance_labels[$communication['importance']] ?? $importance_labels['media'];

$formatted_recipients = [];
foreach ($recipients as $r) {
    $formatted_recipients[] = [
        'id' => $r['id'],
        'customer_id' => $r['customer_id'],
        'name' => ucwords($r['name'] . ' ' . $r['lastname']),
        'email' => $r['email'],
        'is_read' => (bool)$r['is_read'],
        'sent_at' => $r['sent_at'] ? date('d/m/Y H:i', strtotime($r['sent_at'])) : null,
        'read_at' => $r['read_at'] ? date('d/m/Y H:i', strtotime($r['read_at'])) : null,
        'reminder_sent' => (bool)$r['reminder_sent']
    ];
}

$read_count = count(array_filter($formatted_recipients, fn($r) => $r['is_read']));
$pending_count = count($formatted_recipients) - $read_count;

json_response("ok", "Comunicación obtenida correctamente", 2001, [
    'id' => $communication['id'],
    'subject' => $communication['subject'],
    'message' => $communication['message'],
    'importance' => $communication['importance'],
    'importance_label' => $imp['label'],
    'importance_class' => $imp['class'],
    'target_type' => $communication['target_type'],
    'target_label' => $target_labels[$communication['target_type']] ?? $communication['target_type'],
    'target_subtype' => $communication['target_subtype'],
    'created_at' => date('d/m/Y H:i', strtotime($communication['created_at'])),
    'recipients' => $formatted_recipients,
    'stats' => [
        'total' => count($formatted_recipients),
        'read' => $read_count,
        'pending' => $pending_count
    ]
]);