<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    // 1. Borrar servicios excluidos en db actualmente
    $query = "DELETE FROM `sales_rep_excludes_category` WHERE sales_rep_id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();

    // 2. Insertar exclusiones (si existe alguna)
    if (isset($_POST["category_id"]) && !empty($_POST["category_id"]))
    {
        foreach ($_POST["category_id"] as $category_id)
        {
            $query = "INSERT INTO `sales_rep_excludes_category` SET sales_rep_id = :sales_rep_id, category_id = :category_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
            $stmt->bindValue(":category_id", $category_id);
            $stmt->execute();
            // $id_insertada = $pdo->lastInsertId();
        }
    }


    $pdo->commit();

    json_response("ok", "Actualizado", 2693470492);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 3440016322);
}