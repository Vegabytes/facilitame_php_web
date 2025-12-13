<?php
$user = new User();

$file_name_dir = __DIR__ . "/document-fetch.log";
file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($_POST) . "\n", FILE_APPEND | LOCK_EX);

$query = "SELECT files.*
FROM `request_files` files
JOIN requests ON requests.id = files.request_id
WHERE 1
AND files.id = :file_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":file_id", $_POST["file_id"]);
$stmt->execute();
$res = $stmt->fetch();

if ($res === false)
{
    json_response("ko", "Documento no encontrado", 3390297074);
}

if (!user_can_access_request($res["request_id"]))
{
    json_response("ko", "No puedes acceder al documento", 430287054);
}

$b64 = base64_encode(file_get_contents(ROOT_DIR . "/" . DOCUMENTS_DIR . "/" . $res["url"]));

$data = [
    "b64" => $b64,
    "filename" => $res["filename"]
];

json_response("ok", "", 409499404, $data);

?>