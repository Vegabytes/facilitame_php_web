<?php

if (!proveedor()) {
    json_response("ko", "No puedes realizar esta acción.", 3905545310);
}

$req_id = $_POST["request_id"] ?? null;
$reason = trim($_POST["reactivation_reason"] ?? "");

if (!$req_id) {
    json_response("ko", "Falta request_id.", 1001);
}
if ($reason === "" || mb_strlen($reason) < 10) {
    json_response("ko", "Debes indicar un motivo (mín. 10 caracteres).", 1002);
}

try {
    $request = get_request($req_id);
    if (!$request || !empty($request["deleted_at"])) {
        json_response("ko", "La solicitud no existe.", 1003);
    }

    // (Opcional) Validar estados permitidos para reactivar

    $pdo->beginTransaction();

    // Actualiza la solicitud: activada (7) + motivo
    $stmt = $pdo->prepare("
        UPDATE requests
        SET status_id = 7, reactivation_reason = :reason
        WHERE id = :r
    ");
    $stmt->execute([":reason" => $reason, ":r" => $req_id]);

    app_log("request", $req_id, "reactivate", "request", $req_id, ["reason" => $reason]);

    // Reflejamos cambios en el array $request
    $request["status_id"] = 7;
    $request["reactivation_reason"] = $reason;

    // Cerramos transacción antes de notificar
    $pdo->commit();

    // ----------------- Notificaciones (POST-COMMIT) -----------------
    $sender_id   = USER["id"];
    $receiver_id = $request["user_id"];

    // Payload para plantillas minimalistas tipo $data["id"], $data["motivo"]
    $payload = [
        "id"     => (int) $req_id,
        "motivo" => $reason,

        // Compat extra por si otras plantillas leen estos campos:
        "request"             => $request,
        "reactivation_reason" => $reason,
    ];

    // Cliente
    notification_v2(
        $sender_id,
        $receiver_id,
        $req_id,
        "¡Solicitud reactivada!",
        "Tu solicitud ha sido reactivada.",
        "¡Solicitud reactivada con éxito!",
        "request-reactivate",
        $payload
    );

    // Admin
    notification_v2(
        $sender_id,
        ADMIN_ID,
        $req_id,
        "¡Solicitud reactivada!",
        "El proveedor ha reactivado la solicitud #$req_id",
        "¡Solicitud reactivada!",
        "request-reactivate",
        $payload
    );

    // Comercial asociado (si existe)
    $stmt = $pdo->prepare("
        SELECT u.id AS commercial_user_id
        FROM customers_sales_codes c
        JOIN sales_codes sc ON c.sales_code_id = sc.id
        JOIN users u ON sc.user_id = u.id
        WHERE c.customer_id = :uid
        LIMIT 1
    ");
    $stmt->execute([":uid" => $request["user_id"]]);
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($commercial["commercial_user_id"])) {
        notification_v2(
            $sender_id,
            (int) $commercial["commercial_user_id"],
            $req_id,
            "¡Solicitud reactivada para tu cliente!",
            "Se ha reactivado la solicitud #$req_id",
            "¡Solicitud de tu cliente reactivada!",
            "request-reactivate",
            $payload
        );
    }

    // ----------------- Traza visible (después del commit) -----------------
    define("BYPASS", true);
    $_POST["comment"] = "---- SOLICITUD REACTIVADA ----\nMotivo: " . $reason;
    $_POST["request_id"] = $req_id;
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Solicitud reactivada", 1026959706);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (defined('DEBUG') && DEBUG) {
        json_response("ko", $e->getMessage(), 211500276);
    }
    json_response("ko", "No se ha podido reactivar la solicitud. Inténtalo de nuevo, por favor.", 2587598536);
}
