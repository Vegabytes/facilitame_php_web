<?php
if (IS_MOBILE_APP === false)
{
    header("HTTP/1.1 404");
    exit;
}


try
{
    $pdo->beginTransaction();

    // 1. Obtener solicitudes activadas del usuario :: inicio
    $query = "SELECT req.*, cat.name  AS category_name, sta.status_name AS status
    FROM `requests` req
    LEFT JOIN `categories` cat ON cat.id = req.category_id
    LEFT JOIN `requests_statuses` sta ON sta.id = req.status_id
    WHERE req.user_id = " . USER["id"] . " AND req.status_id = 7 AND req.deleted_at IS NULL ORDER BY req.request_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll();

    foreach ($requests as $i => $request)
    {
        $requests[$i]["created_at_display"] = date("d/m/Y", strtotime($request["created_at"]));
        $requests[$i]["updated_at_display"] = !is_null($request["updated_at"]) ? date("d/m/Y", strtotime($request["updated_at"])) : "";
        $requests[$i]["request_info"] = get_request_category_info($request);
    }
    // 1. Obtener solicitudes activadas del usuario :: fin

    if (empty($requests))
    {
        json_response("ok", "", 1118557079);
    }


    foreach ($requests as $i => $request)
    {
        $query = "SELECT expires_at FROM `offers` WHERE request_id = :request_id AND status_id = 7";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $request["id"]);
        $stmt->execute();
        $active_offer = $stmt->fetch();

        if (!$active_offer || is_null($active_offer["expires_at"]))
        {
            $requests[$i]["expires_at"] = "";
            $requests[$i]["expires_at_display"] = "";
            continue;
        }

        $requests[$i]["expires_at"] = $active_offer["expires_at"];
        $requests[$i]["expires_at_display"] = date("d/m/Y", strtotime($active_offer["expires_at"]));
    }    
    
    $pdo->commit();

    json_response("ok", "", 326192848, $requests);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "Error interno del servidor", 621065561);
}