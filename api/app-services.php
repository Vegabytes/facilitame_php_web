<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

try
{
    $services = get_services();

    foreach ($services as &$service)
    {
        $service["category_img_uri"] = app_get_category_image_uri($service["id"]);
    }

    json_response("ok", "", 500461784, $services);
}
catch (Throwable $e)
{
    json_response("ko", "Error interno del servidor", 666666);
}