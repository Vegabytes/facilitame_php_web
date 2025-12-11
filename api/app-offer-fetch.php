<?php
if (!IS_MOBILE_APP) {
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `offers` WHERE id = :offer_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":offer_id", $_POST["file_id"]);
    $stmt->execute();
    $offer = $stmt->fetch();

    if (!$offer)
    {
        json_response("ko", "No se puede acceder a la oferta", 1024775655);
    }

    if (!user_can_access_request($offer["request_id"]))
    {
        json_response("ko", "No puedes acceder a la oferta", 1277076772);
    }

    $b64 = base64_encode(file_get_contents(ROOT_DIR . "/" . DOCUMENTS_DIR . "/" . $offer["offer_file"]));
    $data = [
        "b64" => $b64,
        "filename" => $offer["offer_file"]
    ];

    $pdo->commit();

    json_response("ok", "", 2316929526, $data);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "";
    json_response("ko", $msg, 3893098093);
}







?>