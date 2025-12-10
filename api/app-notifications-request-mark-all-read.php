<?php
if (IS_MOBILE_APP === false)
{
    header("HTTP/1.1 404");
    exit;
}

$file_name_dir = __DIR__ . "/app-notifications-request-mark-all-read.log";
try
{
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . $_POST["request_id"] . "\n", FILE_APPEND | LOCK_EX);

    $pdo->beginTransaction();

    $query = "UPDATE `notifications` SET status = 1 WHERE request_id = :request_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    
    $pdo->commit();

    json_response("ok", "", 3817507025);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    json_response("ko", MSG, 4147438577);
}