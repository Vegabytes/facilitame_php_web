<?php
if (IS_MOBILE_APP === false)
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `notifications` WHERE status = 0 AND receiver_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $res = $stmt->fetchAll();
    
    $pdo->commit();

    json_response("ok", "", 2135970022, count($res));
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 3665872587);
}