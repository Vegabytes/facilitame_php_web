<?php
try
{
    $pdo->beginTransaction();
    
    $notification_id = (int) $_POST["notification_id"];
    
    // Si es comercial, verificar que la notificaci車n pertenece a sus clientes
    if (comercial())
    {
        $comercial_id = (int) USER['id'];
        
        // Verificar que la notificaci車n es de una petici車n de sus clientes
        $stmt = $pdo->prepare("
            SELECT n.id 
            FROM notifications n
            INNER JOIN requests req ON req.id = n.request_id
            INNER JOIN customers_sales_codes csc ON csc.customer_id = req.user_id
            INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
            WHERE n.id = ?
            AND sc.user_id = ?
            AND sc.deleted_at IS NULL
        ");
        $stmt->execute([$notification_id, $comercial_id]);
        
        if (!$stmt->fetch()) {
            json_response("ko", "No autorizado", 1240147688);
        }
    }
    else
    {
        // Para otros usuarios, usar la verificaci車n existente
        if (user_can_access_notification($notification_id) !== true)
        {
            json_response("ko", "No se puede marcar la notificaci車n como le赤da", 1240147687);
        }
    }
    
    // Marcar como le赤da
    $query = "UPDATE `notifications` SET status = 1 WHERE id = :notification_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":notification_id", $notification_id);
    $stmt->execute();
    
    // Log
    $query = "SELECT * FROM `notifications` WHERE id = :notification_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":notification_id", $notification_id);
    $stmt->execute();
    $notification = $stmt->fetch();
    app_log("notification", $notification_id, "mark_open", "request", $notification["request_id"]);
    
    $pdo->commit();
    json_response("ok", "", 2155113739);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 1480508904);
    }
    else
    {
        json_response("ko", "Ha ocurrido un error", 1142366576);
    }
}