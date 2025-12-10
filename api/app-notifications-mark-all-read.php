<?php
if (IS_MOBILE_APP === false)
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    $query = "UPDATE `notifications` SET status = 1 WHERE receiver_id = :receiver_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":receiver_id", USER["id"]);
    $stmt->execute();
    
    $pdo->commit();

    json_response("ok", "", 3340435085);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 750202233);
}