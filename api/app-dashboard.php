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

    $pdo->beginTransaction();

    $data["user"]["name"] = USER["name"];
    $data["user"]["phone"] = USER["phone"];    

    $data["notifications_unread"] = $notifications["unread"];


    // Últimas 3 solicitudes ordenadas por fecha de actualización :: inicio
    $query = 
    "SELECT req.id, cat.name AS category_name, req.category_id, req.form_values
    FROM `requests` req
    LEFT JOIN categories cat ON cat.id = req.category_id
    WHERE 1
    AND req.user_id = :user_id
    AND req.deleted_at IS NULL
    ORDER BY req.updated_at DESC
    LIMIT 0,3";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$req)
    {
        $req["info"] = get_request_category_info($req);
        $req["category_img_uri"] = app_get_category_image_uri($req["category_id"]);
    }
    $data["last_requests"] = empty($requests) ? NULL : $requests;
    // Últimas 3 solicitudes ordenadas por fecha de actualización :: fin

    $data["user"]["profile_picture"] = "";

    $pdo->commit();

    json_response("ok", "", 3333646083, $data);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 1068418505);
    }
    else
    {
        json_response("ko", "", 1874957187);
    }
}

?>