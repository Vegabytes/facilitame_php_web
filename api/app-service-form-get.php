<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

try
{
    $service_form = get_service_form($_POST["service_id"]);
    json_response("ok", "", 2629522297, $service_form);
}
catch (Throwable $e)
{
    json_response("ko", "Error interno del servidor", 3741406198);
}