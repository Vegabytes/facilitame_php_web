<?php
use Ramsey\Uuid\Uuid;
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

$check = ["name", "lastname", "email"];
foreach ($check as $ch)
{
    if (!isset($_POST[$ch]) || empty($_POST[$ch]) || $_POST[$ch] == "")
    json_response("ko", "Faltan campos obligatorios", 1201475172);
}

try
{
    $pdo->beginTransaction();
    
    $query = "SELECT * FROM `users` WHERE email = :email AND id != :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $res = $stmt->fetch();

    if ($res !== false)
    {
        json_response("ko", "El email indicado no está disponible", 482202304);
    }

    $query = "UPDATE `users` SET name = :name, lastname = :lastname, email = :email WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":name", $_POST["name"]);
    $stmt->bindValue(":lastname", $_POST["lastname"]);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->execute();
    
    $pdo->commit();

    json_response("ok", "", 68284766);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "Ha ocurrido un error";
    json_response("ko", "$msg", 3716808592);
}

?>