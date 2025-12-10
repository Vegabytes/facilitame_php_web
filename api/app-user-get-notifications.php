<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $notifications = get_notifications();

    json_response("ok", "", 2428593592, $notifications);
}
catch (Exception $e)
{
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 3344771237);
    }
    else
    {
        json_response("ko", "", 53352858);
    }
}

?>