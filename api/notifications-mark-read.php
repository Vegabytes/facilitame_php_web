<?php
$user = new User();

try
{
    $pdo->beginTransaction();

    $query = "UPDATE `notifications` SET status = 1 WHERE receiver_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user->id);
    $stmt->execute();

    app_log("notification", 0, "mark_all_read", "customer", $user->id);

    $pdo->commit();

    json_response("ok", "", 4006048779);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "No se han podido marcar las notificaciones como leídas", 98608092);
}
