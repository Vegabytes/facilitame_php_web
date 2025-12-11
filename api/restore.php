<?php
if (!isset($_POST["token"]) || empty($_POST["token"]))
{
    json_response("ko", "", 3348953930);
}

if ($_POST["password"] !== $_POST["confirm-password"])
{
    json_response("ko", "Las contraseñas no coinciden.", 2971638626);
}

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `password_recovery_tokens` WHERE 1
    AND token = :token";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":token", $_POST["token"]);
    $stmt->execute();
    $token = $stmt->fetch();
    
    if ($token === false)
    {
        json_response("ko", "", 3918199399);
        exit;
    }
    
    if (!is_null($token["used_at"]))
    {
        json_response("ko", "Este enlace de recuperación ya ha sido utilizado.", 3574448833);
    }
    
    $now = new DateTime("now");
    $token_expires_at = new DateTime("@" . strtotime($token["expires_at"]));
    
    if ($now > $token_expires_at)
    {
        json_response("ko", "Este enlace de recuperación ha caducado.", 2391536808);
    }
    
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    
    $query = "UPDATE `users` SET password = :password_hash WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":password_hash", $password_hash);
    $stmt->bindValue(":user_id", $token["user_id"]);
    $stmt->execute();

    $query = "UPDATE `password_recovery_tokens` SET used_at = CURRENT_TIMESTAMP() WHERE id = :token_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":token_id", $token["id"]);
    $stmt->execute();

    // app_log("customer", $token["user_id"], "recovery_token_used");

    $pdo->commit();

    set_toastr("ok", "Se ha restablecido la contraseña.Ya puedes iniciar sesión");
}
catch (\Throwable $th)
{
    $pdo->rollBack();
    json_response("ko", "Ha ocurrido un error.", 1403333717);
}

json_response("ok", "", 1785599604);