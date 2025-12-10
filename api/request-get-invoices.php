<?php
if (!cliente())
{
    json_response("ko", "", 3237595746);
}

$user = new User();

$query = "SELECT inv.*, DATE_FORMAT(inv.invoice_date, '%d/%m/%Y') AS invoice_date_formatted 
FROM `invoices` inv
JOIN requests req ON inv.request_id = req.id
JOIN users u ON u.id = req.user_id
WHERE 1
AND inv.request_id = :request_id
AND req.user_id = :user_id
ORDER BY inv.invoice_date DESC";

$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->bindValue(":user_id", $user->id);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [
    "invoices" => $invoices
];
json_response("ok", "", 461423443, $data);

?>