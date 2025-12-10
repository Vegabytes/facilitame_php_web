<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();
    
    if (!isset($_POST["sales_rep_id"]) || empty($_POST["sales_rep_id"]) || $_POST["sales_rep_id"] == "")
    {
        json_response("ko", MSG, 3527189104);
    }

    $query = "SELECT id FROM `sales_codes` WHERE user_id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($codes))
    {
        $codes_string = implode(",", $codes);
        $query = "DELETE FROM `customers_sales_codes` WHERE sales_code_id IN ($codes_string)";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "DELETE FROM `sales_codes` WHERE user_id = :sales_rep_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
        $stmt->execute();
    }

    $query = "DELETE FROM `users` WHERE id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();

    $query = "DELETE FROM `model_has_roles` WHERE model_id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();

    app_log("customer", $_POST["sales_rep_id"], "sales_rep_delete");

    $pdo->commit();

    json_response("ok", "Comercial eliminado", 678739611);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 408166843);
}