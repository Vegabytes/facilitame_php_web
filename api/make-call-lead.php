<?php

if (!cliente())
{
    json_response("ko", "", 536038824);
}

try
{    
    $pdo->beginTransaction();

    $user = new User();

    $form_values = json_encode($_POST["form"]);
    
    $query = "INSERT INTO `requests` SET 
    category_id = :category_id,
    user_id = :user_id,
    call_providers = 1,
    code = '',
    allow_call = 1,
    form_values = :form_values,    
    status_id = 6,
    request_date = CURRENT_TIMESTAMP()";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":category_id", $_POST["category_id"]);
    $stmt->bindValue(":user_id", $user->id);
    $stmt->bindValue(":form_values", $form_values);
    $stmt->execute();
    $request_id = $pdo->lastInsertId();


    // Inserción en tabla de comentarios del proveedor :: inicio
    $query = "INSERT INTO `provider_comments`
    SET
    request_id = :request_id,
    comments = ''";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    // Inserción en tabla de comentarios del proveedor :: fin

    app_log("request", $request_id, "create", "request", $request_id, USER["id"], ["source" => "phone"]);
    
    $pdo->commit();

    json_response("ok", "", 45262344);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    if (DEBUG)
    {
        echo ($e->getMessage());
    }
    json_response("ko", "", 1062020378);
}

?>