<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $pdo->beginTransaction();

    $query = "UPDATE `users` SET allow_invoice_access = 1, allow_invoice_access_granted_at = CURRENT_TIME() WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();

    $pdo->commit();

    $data = date("d/m/Y H:i:s");

    json_response("ok", "", 3119325539, $data);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "";
    json_response("ko", $msg, 3716785218);
}

?>