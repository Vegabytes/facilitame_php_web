<?php
try
{
    $pdo->beginTransaction();

    $file_name_dir = __DIR__ . "/app-token-remove-fcm.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($_REQUEST) . "\n", FILE_APPEND | LOCK_EX);    

    $pdo->commit();

    json_response("ko", "", 1140438821);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 1330312505);
}