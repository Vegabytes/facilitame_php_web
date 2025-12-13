<?php
$user = new User();

try
{
    $pdo->beginTransaction();

    // Marcar notificaciones estándar como leídas
    $query = "UPDATE `notifications` SET status = 1 WHERE receiver_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user->id);
    $stmt->execute();
    $updated = $stmt->rowCount();

    // Marcar comunicaciones de asesoría como leídas (solo para clientes)
    if (cliente()) {
        $stmt = $pdo->prepare("UPDATE advisory_communication_recipients SET is_read = 1, read_at = NOW() WHERE customer_id = ? AND is_read = 0");
        $stmt->execute([$user->id]);
        $updated += $stmt->rowCount();
    }

    app_log("notification", 0, "mark_all_read", "customer", $user->id);

    $pdo->commit();

    echo json_encode(["success" => true, "status" => "ok", "message" => "", "data" => ["updated" => $updated]]);
    exit;
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "No se han podido marcar las notificaciones como leídas", 98608092);
}
