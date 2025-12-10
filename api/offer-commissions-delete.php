<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    $query = "DELETE FROM `commissions_admin` WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":id", $_POST["id"]);
    $stmt->execute();

    $pdo->commit();

    json_response("ok", "Borrado correctamente", 2193430191);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    if (DEBUG)
    {
        json_response("ko", $e->getMessage(), 1737546547);
    }
    else
    {
        json_response("ko", MSG, 2163541079);
    }
}