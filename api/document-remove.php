<?php
$user = new User();

$file_name_dir = __DIR__ . "/document-remove.log";
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
    json_response("ko", "Documento no encontrado", 624496308);
}

if (!user_can_access_request($res["request_id"]))
{
    json_response("ko", "No puedes acceder al documento", 2275496088);
}

try {
    unlink(ROOT_DIR . "/" . DOCUMENTS_DIR . "/" . $res["url"]);
    
    $query = "DELETE FROM `request_files` WHERE id = :file_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":file_id", $_POST["file_id"]);
    $stmt->execute();
    //code...
} catch (\Throwable $th) {
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . ($th->getMessage()) . "\n", FILE_APPEND | LOCK_EX);
}

json_response("ok", "", 3978649557);

?>