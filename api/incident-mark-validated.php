<?php

file_put_contents('./php_incident_debug.log', "INICIO INCIDENT-MARK-VALIDATED\n", FILE_APPEND);
file_put_contents('./php_incident_debug.log', "POST: ".print_r($_POST, true)."\n", FILE_APPEND);

if (!user_can_access_request($_POST["request_id"])) {
    file_put_contents('./php_incident_debug.log', "NO ACCESS TO REQUEST\n", FILE_APPEND);
    json_response("ko", "No puedes modificar incidencias en esta solicitud.", 1572518624);
}

try {
    $pdo->beginTransaction();
    file_put_contents('./php_incident_debug.log', "TRANSACTION START\n", FILE_APPEND);

    // Usar el status recibido (2 = gestionando, 3 = validada)
    $new_status_id = intval($_POST["new_status_id"]);
    $incident_id = $_POST["incident_id"];
    file_put_contents('./php_incident_debug.log', "new_status_id: $new_status_id | incident_id: $incident_id\n", FILE_APPEND);

    $query = "UPDATE `request_incidents` SET status_id = :new_status_id WHERE id = :incident_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":new_status_id", $new_status_id);
    $stmt->bindValue(":incident_id", $incident_id);
    $stmt->execute();
    file_put_contents('./php_incident_debug.log', "UPDATE OK\n", FILE_APPEND);

    $request = get_request($_POST["request_id"]);
    file_put_contents('./php_incident_debug.log', "get_request OK\n", FILE_APPEND);

    $sender_id = USER["id"];
    $receiver_id = get_request_user($_POST["request_id"])["id"];
    file_put_contents('./php_incident_debug.log', "sender_id: $sender_id | receiver_id: $receiver_id\n", FILE_APPEND);

    if ($new_status_id === 3) {
        $title = "Incidencia validada";
        $message = "La incidencia ha sido validada por el cliente como resuelta.";
    } else if ($new_status_id === 2) {
        $title = "Incidencia NO resuelta";
        $message = "El cliente ha indicado que la incidencia sigue sin resolverse.";
    } else {
        file_put_contents('./php_incident_debug.log', "ESTADO NO VALIDO: $new_status_id\n", FILE_APPEND);
        throw new Exception("Estado de incidencia no válido");
    }

    // Notificar al usuario propietario
    notification($sender_id, $receiver_id, $_POST["request_id"], $title, $message);
    file_put_contents('./php_incident_debug.log', "Notificación usuario OK\n", FILE_APPEND);

    // Notificar al admin
    notification($sender_id, ADMIN_ID, $_POST["request_id"], $title, $message);
    file_put_contents('./php_incident_debug.log', "Notificación admin OK\n", FILE_APPEND);

    // Notificar al comercial, si existe
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);
    file_put_contents('./php_incident_debug.log', "sales_rep_id: $sales_rep_id\n", FILE_APPEND);
    if ($sales_rep_id) {
        notification($sender_id, $sales_rep_id, $_POST["request_id"], $title, $message);
        file_put_contents('./php_incident_debug.log', "Notificación comercial OK\n", FILE_APPEND);
    }

    app_log("incident", $incident_id, "mark_validated", "request", $_POST["request_id"]);
    file_put_contents('./php_incident_debug.log', "app_log OK\n", FILE_APPEND);

    $pdo->commit();
    file_put_contents('./php_incident_debug.log', "COMMIT OK\n", FILE_APPEND);

    define("BYPASS", true);
    $_POST["comment"] = "---- INCIDENCIA ID ". $incident_id ." ACTUALIZADA A STATUS $new_status_id ----\n";
    require ROOT_DIR . "/api/request-add-provider-comment.php";
    file_put_contents('./php_incident_debug.log', "COMMENT ADDED OK\n", FILE_APPEND);

    json_response("ok", "Incidencia actualizada correctamente.", 693987665);
}
catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents('./php_incident_debug.log', "EXCEPTION: ".$e->getMessage()."\n", FILE_APPEND);
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 2535084262);
    }
    json_response("ko", "No se ha podido actualizar la incidencia. Inténtalo de nuevo, por favor.", 98537354);
}
?>
