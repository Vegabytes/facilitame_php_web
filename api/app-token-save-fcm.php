<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

try
{
    global $pdo;

    $query = "UPDATE `users` SET firebase_token = :firebase_token, platform = :platform WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":firebase_token", $_REQUEST["push_token"]);
    $stmt->bindValue(":platform", $_REQUEST["platform"]);
    $stmt->execute();

    json_response("ok", "", 530058888);
}
catch (Throwable $e)
{
    json_response("ko", "Error interno del servidor", 917494071);
}