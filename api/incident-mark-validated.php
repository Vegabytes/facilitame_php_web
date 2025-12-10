<?php

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes modificar incidencias en esta solicitud.", 1572518624);
}

try {
    $pdo->beginTransaction();

    // Usar el status recibido (2 = gestionando, 3 = validada)
    $new_status_id = intval($_POST["new_status_id"]);
    $incident_id = $_POST["incident_id"];

    $query = "UPDATE `request_incidents` SET status_id = :new_status_id WHERE id = :incident_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":new_status_id", $new_status_id);
    $stmt->bindValue(":incident_id", $incident_id);
    $stmt->execute();

    $request = get_request($_POST["request_id"]);

    $sender_id = USER["id"];
    $receiver_id = get_request_user($_POST["request_id"])["id"];

    if ($new_status_id === 3) {
        $title = "Incidencia validada";
        $message = "La incidencia ha sido validada por el cliente como resuelta.";
    } else if ($new_status_id === 2) {
        $title = "Incidencia NO resuelta";
        $message = "El cliente ha indicado que la incidencia sigue sin resolverse.";
    } else {
        throw new Exception("Estado de incidencia no válido");
    }

    // Notificar al usuario propietario
    notification($sender_id, $receiver_id, $_POST["request_id"], $title, $message);

    // Notificar al admin
    notification($sender_id, ADMIN_ID, $_POST["request_id"], $title, $message);

    // Notificar al comercial, si existe
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);
    if ($sales_rep_id) {
        notification($sender_id, $sales_rep_id, $_POST["request_id"], $title, $message);
    }

    app_log("incident", $incident_id, "mark_validated", "request", $_POST["request_id"]);

    $pdo->commit();

    define("BYPASS", true);
    $_POST["comment"] = "---- INCIDENCIA ID ". $incident_id ." ACTUALIZADA A STATUS $new_status_id ----\n";
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Incidencia actualizada correctamente.", 693987665);
}
catch (Exception $e) {
    $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 2535084262);
    }
    json_response("ko", "No se ha podido actualizar la incidencia. Inténtalo de nuevo, por favor.", 98537354);
}
