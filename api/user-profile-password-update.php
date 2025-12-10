<?php
$query = "SELECT * FROM `users`
WHERE 1
AND id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($res) !== 1)
{
    json_response("ko", "No se ha podido actualizar la contraseña", 1624412740);
}

$db_user = $res[0];

if (!password_verify($_POST["current_password"], $db_user["password"]))
{
    json_response("ko", "La contraseña no es correcta", 1697521514);
}

if ($_POST["new_password"] !== $_POST["new_password_confirm"])
{
    json_response("ko", "La nueva contraseña y su confirmación no coinciden", 3420455302);
}


$password_hash = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

try
{
    $pdo->beginTransaction();
    
    $query = "UPDATE `users` SET password = :password_hash WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":password_hash", $password_hash);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();

    app_log("customer", USER["id"], "customer_update_password");
    
    $pdo->commit();

    json_response("ok", "Contraseña actualizada", 3536834522);
}
catch (Exception $e)
{
    $pdo->rollBack();
    json_response("ko", "No se ha podido actualizar la contraseña", 2456150085);
}

?>