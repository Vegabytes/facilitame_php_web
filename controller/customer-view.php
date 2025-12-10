<?php
global $pdo;
$info = [];

$query = "SELECT * FROM `customers` WHERE user_id = :user_id AND deleted = 0 AND id = :customer_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->bindValue(":customer_id", $_GET["id"]);
$stmt->execute();
$customer = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($customer) || intval(count($customer)) !== 1)
{
    header("Location:home");
    exit;
}

$info["customer"] = $customer[0];
?>