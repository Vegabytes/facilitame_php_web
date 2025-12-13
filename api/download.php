<?php
$user = new User();

// $query = "SELECT files.*
// FROM `request_files` files
// JOIN requests ON requests.id = files.request_id
// WHERE 1
// AND files.id = :file_id";
// $stmt = $pdo->prepare($query);
// $stmt->bindValue(":file_id", $_POST["file_id"]);
// $stmt->execute();
// $res = $stmt->fetch();


if ($_POST["type"] == "offer")
{
    $query = "SELECT * FROM `offers` WHERE id = :file_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":file_id", $_POST["file_id"]);
    $stmt->execute();
    $res = $stmt->fetch();
}


if ($res === false)
{
    json_response("ko", "Archivo no encontrado", 3390297074);
}

if (!user_can_access_request($res["request_id"]))
{
    json_response("ko", "No puedes acceder al documento", 430287054);
}


if ($_POST["type"] == "offer")
{
    $filepath = ROOT_DIR . "/" . DOCUMENTS_DIR . "/" . $res["offer_file"];
    $b64 = base64_encode(file_get_contents($filepath));
}

if ($b64 == "")
{
    json_response("ko", "No se encuentra el archivo.", 756771818, []);
}

$data = [
    "b64" => $b64,
    // "filename" => $res["filename"]
];

json_response("ok", "", 221574200, $data);

?>