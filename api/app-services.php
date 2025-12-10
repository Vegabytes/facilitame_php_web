<?php
try
{
    $pdo->beginTransaction();

    $services = get_services();

    $file_name_dir = __DIR__ . "/app-services.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($services) . "\n", FILE_APPEND | LOCK_EX);

    foreach ($services as &$service)
    {
        $service["category_img_uri"] = app_get_category_image_uri($service["id"]);
    }
    
    $pdo->commit();

    json_response("ok", "", 500461784, $services);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 666666);
}