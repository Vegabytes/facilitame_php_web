<?php
try
{
    $pdo->beginTransaction();

    $service_form = get_service_form($_POST["service_id"]);
    
    $pdo->commit();

    $file_name_dir = __DIR__ . "/app-service-form-get.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($service_form) . "\n", FILE_APPEND | LOCK_EX);

    json_response("ok", "", 2629522297, $service_form);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 3741406198);
}