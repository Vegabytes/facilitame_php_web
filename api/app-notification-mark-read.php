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

    $query = "UPDATE `notifications` SET status = 1 WHERE id = :notification_id AND receiver_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":notification_id", $_POST["notification_id"]);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    
    $pdo->commit();

    json_response("ok", "", 2952315201);
}
catch (Exception $e)
{
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 3563392319);
    }
    else
    {
        json_response("ko", "", 3877236183);
    }
}

?>