<?php
$file_name_dir = __DIR__ . "/app-token-save-fcm.log";
file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . "Inicio" . "\n", FILE_APPEND | LOCK_EX);
try
{
    $pdo->beginTransaction();
    
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($_REQUEST) . "\n", FILE_APPEND | LOCK_EX);    

    $query = "UPDATE `users` SET firebase_token = :firebase_token, platform = :platform WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":firebase_token", $_REQUEST["push_token"]);
    $stmt->bindValue(":platform", $_REQUEST["platform"]);
    $stmt->execute();

    $pdo->commit();

    json_response("ok", "", 530058888);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 917494071);
}