<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

if (user_can_access_request($_POST["request_id"]) !== true)
{
    json_response("ko", "No se puede acceder a los mensajes", 2074368994);
}

try
{
    $messages = get_messages($_POST["request_id"]);

    $parsed = [];
    foreach ($messages as $m)
    {
        $aux = [];
        $aux["_id"] = $m["id"];
        $aux["text"] = $m["content"];
        $aux["createdAt"] = $m["created_at"];
        $aux["user"]["id"] = $m["user_id"];
        $aux["user"]["name"] = $m["user_id"] == USER["id"] ? USER["name"] : "Facilítame";

        $parsed[] = $aux;
    }

    $data["chat"] = $parsed;
    $data["current_user_id"] = USER["id"];
    $data["current_user_name"] = USER["name"];

    json_response("ok", "", 1628766346, $data);
}
catch (Exception $e)
{
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 1308734381);
    }
    else
    {
        json_response("ko", "", 1758810952);
    }
}