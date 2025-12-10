<?php
if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes modificar incidencias en esta solicitud.", 1572518624);
}

try {
    $pdo->beginTransaction();

    $query = "UPDATE `request_incidents` SET status_id = 2 WHERE id = :incident_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":incident_id", $_POST["incident_id"]);
    $stmt->execute();

    $request = get_request($_POST["request_id"]);
    $sender_id = USER["id"];
    $receiver_id = get_request_user($_POST["request_id"])["id"];
    $title = "Gestionando incidencia";
    $message = "La incidencia está siendo gestionada.";

    // Notificar al usuario propietario
    notification(
        $sender_id,
        $receiver_id,
        $_POST["request_id"],
        $title,
        $message
    );

    // Notificar al admin
    notification(
        $sender_id,
        ADMIN_ID,
        $_POST["request_id"],
        $title,
        $message
    );

    // Notificar al comercial, si existe
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);
    if ($sales_rep_id) {
        notification(
            $sender_id,
            $sales_rep_id,
            $_POST["request_id"],
            $title,
            $message
        );
    }

    app_log("incident", $_POST["incident_id"], "mark_active", "request", $_POST["request_id"]);

    $pdo->commit();
    
    define("BYPASS", true);
    $_POST["comment"] = "---- INCIDENCIA ID ". $_POST["incident_id"] ." EN GESTIÓN ----\n";
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Incidencia en gestión", 693987665);
}
catch (Exception $e) {
    $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 2535084262);
    }
    json_response("ko", "No se ha podido actualizar la incidencia. Inténtalo de nuevo, por favor.", 98537354);
}
?>
