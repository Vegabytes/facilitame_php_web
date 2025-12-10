<?php
global $pdo;

$user_id = USER['id']; // Asumiendo que USER es el usuario logueado

// Si quieres filtrar SOLO las incidencias del cliente:
$query = "
    SELECT i.*, r.category_id, c.name as category_name, r.status_id as request_status_id
    FROM request_incidents i
    JOIN requests r ON i.request_id = r.id
    JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = :user_id
    ORDER BY i.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':user_id', $user_id);
$stmt->execute();
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$info = compact("incidents");
?>
