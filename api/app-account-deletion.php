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

    $query = "UPDATE `users` SET deleted_at = CURRENT_TIMESTAMP() WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();    

    $pdo->commit();
    json_response("ok", "", 2120225554, $data);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 3661935276);
    }
    else
    {
        json_response("ko", "", 1503081266);
    }
}

?>