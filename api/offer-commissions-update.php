<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}


try
{
    $pdo->beginTransaction();

    $deactivated_at = ($_POST["deactivated_at"] === "") ? NULL : $_POST["deactivated_at"];

    $query = "UPDATE `commissions_admin` SET value = :value, recurring = :recurring, activated_at = :activated_at, deactivated_at  =:deactivated_at WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":id", $_POST["id"]);
    $stmt->bindValue(":value", $_POST["value"]);
    $stmt->bindValue(":recurring", $_POST["recurring"]);
    $stmt->bindValue(":activated_at", $_POST["activated_at"]);
    $stmt->bindValue(":deactivated_at", $deactivated_at);
    $stmt->execute();    

    $pdo->commit();

    json_response("ok", "Info actualizada", 1445547166);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    if (DEBUG)
    {
        json_response("ko", $e->getMessage(), 1461522424);
    }
    else
    {
        json_response("ko", MSG, 666666);
    }
}