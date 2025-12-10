<?php
if (!proveedor() || !user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes confirmar esta oferta.", 375559329);
}

// VALIDACIÓN: solo bloquea si la solicitud ya está ACTIVADA (7)
$query = "SELECT 1 FROM `requests` WHERE deleted_at IS NULL AND id = :request_id AND status_id = 7";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
if ($stmt->fetch()) {
    json_response("ko", "La solicitud está activada. No se puede confirmar otra oferta.", 2582341913);
}

// Validaciones de datos de entrada
if (isset($_POST["commission"]) && (trim($_POST["commission"]) == "" || $_POST["commission_type"] == "")) {
    json_response("ko", "Debes indicar la cantidad y tipo de comisión.", 914800812);
}
if (isset($_POST["total_amount"]) && ($_POST["total_amount"] < 0 || $_POST["total_amount"] === "")) {
    json_response("ko", "Debes indicar el importe mensual total de la oferta.", 2777221677);
}

// RECUERDA: tener creado el estado 11 = Desactivada (solo para ofertas)

try {
    $pdo->beginTransaction();

    // 1) Desactivar TODAS las demás ofertas de la solicitud (quedan históricas / no vigentes)
    $stmt = $pdo->prepare("
        UPDATE `offers`
        SET status_id = 11
        WHERE request_id = :request_id
          AND id <> :offer_id
          AND deleted_at IS NULL
          AND status_id IN (2,3,4,5,7,9)
    ");
    $stmt->execute([
        ":request_id" => $_POST["request_id"],
        ":offer_id"   => $_POST["offer_id"],
    ]);

    // 2) Poner ESTA oferta en EN CURSO (4) + comisión o importe
    if (isset($_POST["commission"])) {
        $stmt = $pdo->prepare("
            UPDATE `offers`
            SET status_id = 4,
                commision_type_id = :commission_type_id,
                commision = :commision
            WHERE id = :offer_id
        ");
        $stmt->execute([
            ":offer_id"            => $_POST["offer_id"],
            ":commission_type_id"  => $_POST["commission_type"],
            ":commision"           => $_POST["commission"],
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE `offers`
            SET status_id = 4,
                total_amount = :total_amount,
                commision_type_id = 3
            WHERE id = :offer_id
        ");
        $stmt->execute([
            ":offer_id"     => $_POST["offer_id"],
            ":total_amount" => $_POST["total_amount"],
        ]);
    }
    app_log("offer", $_POST["offer_id"], "confirmed", "request", $_POST["request_id"]);

    // 3) La SOLICITUD pasa a EN CURSO (4)
    $stmt = $pdo->prepare("UPDATE `requests` SET status_id = 4 WHERE id = :request_id");
    $stmt->execute([":request_id" => $_POST["request_id"]]);
    app_log("request", $_POST["request_id"], "confirmed", "request", $_POST["request_id"]);

    // 4) Notificaciones
    $request    = get_request($_POST["request_id"]);
    $sender_id  = USER["id"];
    $receiver_id= $request["user_id"];
    $title = "¡Oferta confirmada!";
    $description = "Tu asesor ha recibido tu aceptación y está ultimando los detalles para la activación del servicio.";
    $email_subject  = "¡Tu oferta ha sido confirmada!";
    $email_template = "offer-confirm";

    // Cliente
    notification_v2(
        $sender_id, $receiver_id, $_POST["request_id"],
        $title, $description, $email_subject, $email_template,
        ["request" => $request]
    );

    // Admin
    notification_v2(
        $sender_id, ADMIN_ID, $_POST["request_id"],
        "¡Oferta confirmada!",
        "El proveedor ha confirmado una oferta para la solicitud #" . $_POST["request_id"],
        "¡Oferta confirmada para el cliente!",
        $email_template,
        ["request" => $request]
    );

    // Comercial (si existe)
    $stmt = $pdo->prepare("
        SELECT u.id AS commercial_user_id
        FROM customers_sales_codes csc
        JOIN sales_codes sc ON csc.sales_code_id = sc.id
        JOIN users u ON sc.user_id = u.id
        WHERE csc.customer_id = :customer_id
        LIMIT 1
    ");
    $stmt->execute([":customer_id" => $request["user_id"]]);
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commercial) {
        notification_v2(
            $sender_id, $commercial["commercial_user_id"], $_POST["request_id"],
            "¡Oferta confirmada para tu cliente!",
            "El proveedor ha confirmado una oferta para la solicitud #" . $_POST["request_id"],
            "¡Oferta confirmada para tu cliente!",
            $email_template,
            ["request" => $request]
        );
    }

    $pdo->commit();

    // 5) Traza visible (sin romper el flujo)
    define("BYPASS", true);
    $_POST["comment"]    = "---- OFERTA CONFIRMADA (en curso) ----";
    $_POST["request_id"] = $_POST["request_id"];
    try { ob_start(); require ROOT_DIR . "/api/request-add-provider-comment.php"; ob_end_clean(); } catch (Throwable $t) {}

    json_response("ok", "Oferta confirmada", 3892507279);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (defined("DEBUG") && DEBUG) {
        json_response("ko", $e->getMessage(), 4083187563);
    }
    json_response("ko", "No se ha podido confirmar la oferta. Inténtalo de nuevo, por favor.", 4140666451);
}
