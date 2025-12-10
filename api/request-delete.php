<?php
// Eliminar/desactivar solicitud con notificaciones POST-COMMIT (cliente, admin, comercial, proveedor único)

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", MSG, 1039333056);
}

$reason_raw = isset($_POST["reason"]) ? (string)$_POST["reason"] : '';
if (mb_strlen($reason_raw) < 15) {
    json_response("ko", "El motivo es obligatorio y debe tener al menos 15 caracteres.", 1754586386);
}

$request_id = isset($_POST["request_id"]) ? (int)$_POST["request_id"] : 0;
$request    = get_request($request_id);

// Si es cliente, solo puede eliminar si la solicitud está en estado 1 (pendiente/borrador)
if (cliente() && !in_array((int)$request["status_id"], [1], true)) {
    json_response("ko", "No puedes eliminar esta solicitud porque está en curso.", 3761764293);
}

try {
    $pdo->beginTransaction();

    // 1) Marcar solicitud como eliminada
    $query = "UPDATE `requests`
              SET deleted_at = CURRENT_TIMESTAMP(),
                  deleted_by = :current_user_id,
                  delete_reason = :reason,
                  status_id = 9
              WHERE id = :request_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":current_user_id", USER["id"], PDO::PARAM_INT);
    $stmt->bindValue(":reason", $reason_raw, PDO::PARAM_STR);
    $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);
    $stmt->execute();

    // 2) Eliminar notificaciones no leídas asociadas
    $query = "DELETE FROM `notifications`
              WHERE request_id = :request_id
                AND status = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);
    $stmt->execute();

    // 3) Marcar ofertas asociadas como eliminadas (status_id = 9)
    $query = "UPDATE `offers`
              SET status_id = 9
              WHERE request_id = :request_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);
    $stmt->execute();

    // 4) Log
    app_log("request", $request_id, "delete", "request", $request_id);

    $pdo->commit();

    // -------- Notificaciones DESPUÉS del commit --------
    // Refrescamos datos con estado final
    $request   = get_request($request_id);
    $sender_id = (int) USER["id"];
    $actor     = get_user($sender_id);
    $client    = get_user($request["user_id"]);

    // Mensajes base
    $actor_nombre = trim(($actor["name"] ?? "Un usuario") . " " . ($actor["lastname"] ?? ""));
    $title        = "Solicitud eliminada";
    $desc_base    = "{$actor_nombre} ha eliminado/desactivado la solicitud #{$request_id}.";

    // Sanitizamos para cuerpo in-app (evitar XSS) y acortamos para vista breve
    $reason_safe   = filter_var($reason_raw, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $motivo_breve  = mb_strlen($reason_safe) > 250 ? mb_substr($reason_safe, 0, 247) . '...' : $reason_safe;

    $email_subject  = "Solicitud eliminada";
    $email_template = "request_deleted_generic";

    // Payload para plantillas
    $payload = [
        "request"     => $request,
        "request_id"  => $request_id,
        "motivo"      => $reason_raw,     // la plantilla debe escapar si renderiza HTML
        "deleted_by"  => $sender_id,
        "actor_name"  => $actor_nombre,
        "client_name" => $client["name"] ?? "",
    ];

    // --- Cliente (propietario de la solicitud)
    $receiver_client_id = (int) $request["user_id"];
    notification_v2(
        $sender_id,
        $receiver_client_id,
        $request_id,
        $title,
        $desc_base . (!empty($motivo_breve) ? " Motivo: {$motivo_breve}" : ""),
        $email_subject,
        $email_template,
        $payload
    );

    // --- Admin
    if (defined("ADMIN_ID") && ADMIN_ID) {
        notification_v2(
            $sender_id,
            ADMIN_ID,
            $request_id,
            "Solicitud #{$request_id} eliminada",
            "Se ha eliminado/desactivado la solicitud #{$request_id}."
                . (!empty($motivo_breve) ? " Motivo: {$motivo_breve}" : ""),
            "Solicitud eliminada",
            $email_template,
            $payload
        );
    }

    // --- Comercial (si existe): mismo patrón SQL que en tu ejemplo
    $stmt = $pdo->prepare(
        "SELECT sc.id as sales_code_id, sc.code, u.id as commercial_user_id
         FROM customers_sales_codes csc
         JOIN sales_codes sc ON csc.sales_code_id = sc.id
         JOIN users u ON sc.user_id = u.id
         WHERE csc.customer_id = :customer_id
         LIMIT 1"
    );
    $stmt->bindValue(":customer_id", $request["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commercial) {
        $commercial_id = (int)$commercial["commercial_user_id"];
        notification_v2(
            $sender_id,
            $commercial_id,
            $request_id,
            "Solicitud de tu cliente eliminada",
            "Tu cliente tiene la solicitud #{$request_id} eliminada/desactivada."
                . (!empty($motivo_breve) ? " Motivo: {$motivo_breve}" : ""),
            "Solicitud de cliente eliminada",
            $email_template,
            $payload
        );
    }

    // --- Proveedor (único)
    $provider    = get_request_provider($request_id);
    $receiver_id = $provider["id"] ?? null;

    if ($receiver_id) {
        notification_v2(
            $sender_id,
            (int)$receiver_id,
            $request_id,
            "Solicitud del cliente eliminada",
            "La solicitud #{$request_id} ha sido eliminada/desactivada."
                . (!empty($motivo_breve) ? " Motivo: {$motivo_breve}" : ""),
            "Solicitud del cliente eliminada",
            $email_template,
            $payload
        );
    }

    json_response("ok", "Solicitud desactivada", 3507953485);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (defined('DEBUG') && DEBUG) {
        json_response("ko", $e->getMessage(), 3795313703);
    }
    json_response("ko", MSG, 3795313703);
}
