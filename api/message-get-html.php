<?php
if (message_belongs_to_user($_POST["message_id"]) !== true)
{
    json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 1763537762);
}

try
{
    $pdo->beginTransaction();
    
    $query = "SELECT * FROM `messages_v2` WHERE id = :message_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":message_id", $_POST["message_id"]);
    $stmt->execute();
    $message = $stmt->fetchAll();

    if (count($message) !== 1) 
    {
        json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 1923288298);
    }

    $pdo->commit();

    $message = $message[0];
    $html = build_messages([$message]);

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