<?php
// Ejecutar diariamente con cron
require_once __DIR__ . "/../_required.php";
require_once __DIR__ . "/../functions.php";

$query = "SELECT acr.*, ac.subject, ac.message, u.email, u.name, u.lastname
          FROM advisory_communication_recipients acr
          INNER JOIN advisory_communications ac ON ac.id = acr.communication_id
          INNER JOIN users u ON u.id = acr.customer_id
          WHERE ac.importance = 'importante'
          AND acr.read_at IS NULL
          AND acr.reminder_sent = 0
          AND acr.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

$stmt = $pdo->prepare($query);
$stmt->execute();
$pending = $stmt->fetchAll();

foreach ($pending as $item) {
    $to_name = ucwords($item['name'] . ' ' . $item['lastname']);
    $subject = "⚠️ RECORDATORIO: " . $item['subject'];
    $body = "Este es un recordatorio de una comunicación importante que no has leído.<br><br>" . nl2br(htmlspecialchars($item['message']));
    
    send_mail($item['email'], $to_name, $subject, $body, $item['communication_id']);
    
    // Marcar como recordatorio enviado
    $update = $pdo->prepare("UPDATE advisory_communication_recipients SET reminder_sent = 1 WHERE id = ?");
    $update->execute([$item['id']]);
}