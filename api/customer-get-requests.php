<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();
    
    $query = 
    "SELECT r.*, cat.name AS category_name
    FROM `requests` r
    JOIN categories cat ON cat.id = r.category_id
    WHERE 1
    AND r.deleted_at IS NULL
    AND r.user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $_POST["customer_id"]);
    $stmt->execute();
    $requests = $stmt->fetchAll();

    foreach ($requests as $i => $r)
    {
        $requests[$i]["status_display"] = get_badge_html($r["status_id"]);
    }
    
    $pdo->commit();

    json_response("ok", "", 4200437447, $requests);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "Error interno del servidor", 4031358105);
}





?>