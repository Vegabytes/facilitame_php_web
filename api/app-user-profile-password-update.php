<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

$check = ["currentPassword", "newPassword"];
foreach ($check as $ch)
{
    if (!isset($_POST[$ch]) || empty($_POST[$ch]) || $_POST[$ch] == "")
    json_response("ko", "Faltan campos obligatorios", 1658426916);
}

try
{
    $pdo->beginTransaction();
    
    $query = "SELECT * FROM `users` WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $res = $stmt->fetch();

    if (!password_verify($_POST["currentPassword"], $res["password"]))
    {
        json_response("ko", "La contraseña actual no es correcta", 4005943614);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $query = "UPDATE `users` SET password = :password WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":password", $newHash);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();

    json_response("ok", "Contraseña actualizada", 1246293194);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "Ha ocurrido un error";
    json_response("ko", $msg, 2446022558);
}

?>