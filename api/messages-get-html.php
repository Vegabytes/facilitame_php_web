<?php
if (user_can_access_request($_POST["request_id"]) !== true)
{
    json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 2074368994);
}

try
{
    $pdo->beginTransaction();
    
    $messages = get_messages($_POST["request_id"]);

    if (count($messages) < 1) 
    {
        $data = [
            "html" => ""
        ];
        json_response("ok", "", 1923288298);
    }

    $pdo->commit();

    $html = build_messages($messages, $_POST["request_id"]);

    $data = [
        "html" => $html
    ];
    json_response("ok", "", 3411642511, $data);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 311934696);
    }
    else
    {
        json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 311934696);
    }
}

?>