<?php
try
{
    // $pdo->beginTransaction();
    // $pdo->commit();

    $requests = get_requests();
    foreach ($requests as &$req)
    {
        $req["category_img_uri"] = app_get_category_image_uri($req["category_id"]);
    }


    json_response("ok", "CORRECTO", 4055677263, $requests);
}
catch (Throwable $e)
{
    // $pdo->rollBack();
    json_response("ko", $e->getMessage(), 585985617);
}