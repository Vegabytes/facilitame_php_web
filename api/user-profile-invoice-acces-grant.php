<?php
$query = "SELECT * FROM `users`
WHERE 1
AND id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($res) !== 1)
{
    json_response("ko", "No se ha podido actualizar el consentimiento de acceso a tus facturas", 4112173764);
}

$db_user = $res[0];

if (empty($_POST["allow-invoice-acces"]) || $_POST["allow-invoice-acces"] != "1" || !isset($_POST["allow-invoice-acces"]))
{
    json_response("ko", "Debes aceptar el consentimiento de acceso a tus facturas para actualizarlo", 2051397491);
}

try
{
    $pdo->beginTransaction();
    
    $query = "UPDATE `users` SET allow_invoice_access = 1, allow_invoice_access_granted_at = CURRENT_TIMESTAMP() WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();

    app_log("customer", USER["id"], "invoice_access_granted");
    
    $pdo->commit();

    json_response("ok", "Consentimiento de acceso a mis facturas aceptado", 1382047708);
}
catch (Exception $e)
{
    $pdo->rollBack();
    json_response("ko", "No se ha podido actualizar el consentimiento de acceso a tus facturas", 2715084570);
}

?>