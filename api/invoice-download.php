<?php

$user = new User();

$query = "SELECT inv.filename
FROM `invoices` inv
JOIN requests req ON inv.request_id = req.id
JOIN users u ON u.id = req.user_id
WHERE inv.id = :invoice_id
AND req.user_id = :user_id";


$stmt = $pdo->prepare($query);
$stmt->bindValue(":invoice_id", $_POST["invoice_id"]);
$stmt->bindValue(":user_id", $user->id);
$stmt->execute();
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if ($invoice === false)
{
    json_response("ko", "", 244343287);
}

$b64 = base64_encode(file_get_contents(ROOT_DIR . "/" . INVOICES_DIR . "/" . $invoice["filename"]));

$data = [
    "b64" => $b64,
    "filename" => $invoice["filename"]
];

json_response("ok", "", 409499404, $data);

?>