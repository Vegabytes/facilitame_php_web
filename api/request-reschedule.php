<?php
if (!user_can_access_request($_POST["request_id"]))
    json_response("ko", "No puedes reagendar esta solicitud.", 4258076846);

try {
    $pdo->beginTransaction();

    $rescheduled_at = $_POST["rescheduled_at"] ?? null;
    if (!$rescheduled_at) {
        json_response("ko", "Debes seleccionar una fecha de reagendado.", 1002348);
    }

    $query = "UPDATE `requests` SET status_id = 10, rescheduled_at = :rescheduled_at WHERE id = :request_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->bindValue(":rescheduled_at", $rescheduled_at);
    $stmt->execute();

    app_log("request", $_POST["request_id"], "reschedule", "request", $_POST["request_id"]);

    set_toastr("ok", "Solicitud aplazada correctamente.");

    $pdo->commit();

    // -------- Notificaciones después del commit ---------
    $request = get_request($_POST["request_id"]);
    $client = get_user($request["user_id"]);
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);

    // ----------- Cliente
    $title = "Tu solicitud ha sido aplazada";
    $desc = "La solicitud #" . $request["id"] . " ha sido aplazada al " . fdate($rescheduled_at) . ".";
    notification_v2(
        USER["id"],
        $client["id"],
        $request["id"],
        $title,
        $desc,
        $title,
        "reschedule_notification_generic",
        [ "nombre" => $client["name"], "fecha" => fdate($rescheduled_at), "id" => $request["id"] ]
    );

    // ----------- Comercial
    if ($sales_rep_id) {
        $title = "Una solicitud ha sido aplazada";
        $desc = "La solicitud #" . $request["id"] . " ha sido aplazada por el usuario al " . fdate($rescheduled_at) . ".";
        notification_v2(
            USER["id"],
            $sales_rep_id,
            $request["id"],
            $title,
            $desc,
            $title,
            "reschedule_notification_generic", // Usar la misma plantilla para comercial
            [ "nombre" => get_user($sales_rep_id)["name"], "fecha" => fdate($rescheduled_at), "id" => $request["id"] ]
        );
    }

    // ----------- Admin
    notification_v2(
        USER["id"],
        ADMIN_ID,
        $request["id"],
        "Solicitud aplazada",
        "La solicitud #" . $request["id"] . " ha sido aplazada por el usuario al " . fdate($rescheduled_at) . ".",
        "Solicitud aplazada",
        "reschedule_notification_generic", // Usar/crear plantilla específica para admin
        [ "nombre" => "Admin", "fecha" => fdate($rescheduled_at), "id" => $request["id"] ]
    );

    json_response("ok", "Solicitud aplazada correctamente.", 3301361025);

} catch (Exception $e) {
    $pdo->rollBack();

    if (defined('DEBUG') && DEBUG === true) {
        json_response("ko", $e->getMessage(), 2798683574);
    } else {
         json_response("ko", $e->getMessage(), 2798683574);
        //json_response("ko", "Ha ocurrido un error.<br>Inténtalo de nuevo en unos minutos, por favor.", 2798683574);
    }
}
?>
