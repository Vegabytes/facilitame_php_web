<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

try
{
    global $pdo;

    $query = "UPDATE `users` SET firebase_token = NULL WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();

    json_response("ok", "", 1140438821);
}
catch (Throwable $e)
{
    json_response("ko", MSG, 1330312505);
}