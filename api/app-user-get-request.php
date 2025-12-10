<?php
try
{
    // $pdo->beginTransaction();
    // $pdo->commit();

    require ROOT_DIR . "/controller/request.php";

    $request = $info["request"]; // viene del require anterior

    $category = get_category($request["category_id"]);


    $request["category_img_url"] = app_get_category_image_uri($request["category_id"]);
    $request["info"] = get_request_category_info($request);
    $request["category_name"] = $category["name"];
    $request["category_phone"] = $category["phone"];
    $request["offers"] = get_offers($request["id"]);
    $request["documents"] = get_documents($request["id"]);

    json_response("ok", "CORRECTO", 2637442395, $request);
}
catch (Throwable $e)
{
    // $pdo->rollBack();
    json_response("ko", $e->getMessage(), 1013918141);
}