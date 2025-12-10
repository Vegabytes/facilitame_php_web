<?php
global $pdo;
$info = [];

$customers = get_customers();

// quotation :: inicio
$customer_ids = string_ids($customers);

$query = "SELECT * FROM `quotations` WHERE customer_id IN (:customer_ids) AND id = :quotation_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":customer_ids", $customer_ids);
$stmt->bindValue(":quotation_id", $_GET["id"]);
$stmt->execute();
$quotation = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($quotation) || intval(count($quotation)) !== 1)
{
    header("Location:home");
    exit;
}
$info["quotation"] = $quotation[0];
// quotation :: fin

$info["customers"] = $customers;

$info["quotation_items"] = get_quotation_items($_GET["id"]);

?>