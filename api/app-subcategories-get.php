<?php
// if (!admin())
// {
//     header("HTTP/1.1 404");
//     exit;
// }

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `categories` WHERE parent_id = :parent_id ORDER BY list_order";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":parent_id", $_POST["parent_id"]);
    $stmt->execute();
    $subcategories = $stmt->fetchAll();
    
    $pdo->commit();

    json_response("ok", "", 3182196396, $subcategories);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 3336631781);
}