<?php
if (!cliente() || !user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes aceptar esta oferta.", 61586214);
}

$request_id = (int)$_POST["request_id"];
$offer_id   = (int)$_POST["offer_id"];

// Validación básica de existencia
$request = get_request($request_id);
if (!$request || !empty($request["deleted_at"])) {
    json_response("ko", "La solicitud no existe o está eliminada.", 1002);
}

// (Opcional) podrías validar que la oferta pertenece a la solicitud y está disponible (2)
$stmt = $pdo->prepare("SELECT id, status_id FROM offers WHERE id = :oid AND request_id = :rid AND deleted_at IS NULL");
$stmt->execute([":oid" => $offer_id, ":rid" => $request_id]);
$offerRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$offerRow) {
    json_response("ko", "La oferta no existe para esta solicitud.", 1003);
}

try {
    $pdo->beginTransaction();

    // 1) Desactivar TODAS las demás ofertas (historificar)
    $stmt = $pdo->prepare("
        UPDATE offers
        SET status_id = 11
        WHERE request_id = :rid
          AND id <> :oid
          AND deleted_at IS NULL
          AND status_id IN (2,3,4,5,7,9)
    ");
    $stmt->execute([":rid" => $request_id, ":oid" => $offer_id]);

    // 2) Aceptar la oferta elegida
    $stmt = $pdo->prepare("UPDATE offers SET status_id = 3 WHERE id = :oid");
    $stmt->execute([":oid" => $offer_id]);
    app_log("offer", $offer_id, "accept", "request", $request_id);

    // 3) Solicitud -> ACEPTADA (3)
    $was_activated = ((int)$request["status_id"] === 7);
    $stmt = $pdo->prepare("UPDATE requests SET status_id = 3 WHERE id = :rid");
    $stmt->execute([":rid" => $request_id]);
    app_log("request", $request_id, "accepted", "request", $request_id);

    $pdo->commit();

    // Traza visible (no romper la respuesta si falla)
    define("BYPASS", true);
    $_POST["request_id"] = $request_id;
    $_POST["comment"] =
        "---- OFERTA ACEPTADA ----" .
        ($was_activated ? "\n(Nota: la solicitud estaba ACTIVADA; se ha puesto en ACEPTADA por cambio de oferta)" : "");
    try { ob_start(); require ROOT_DIR . "/api/request-add-provider-comment.php"; ob_end_clean(); } catch (Throwable $t) {}

    // Notificaciones (igual que tenías, quité duplicado)
    $request = get_request($request_id); // refrescado
    $sender_id   = USER["id"];
    $receiver_id = get_request_provider($request_id)["id"];
    $title   = "Oferta aceptada";
    $message = USER["name"] . " " . USER["lastname"] . " ha aceptado una oferta";

    notification($sender_id, $receiver_id, $request_id, $title, $message);

    // Comercial asociado
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
        $message_commercial = USER["name"] . " " . USER["lastname"] . " ha aceptado una oferta para la solicitud #{$request_id}";
        notification_v2(
            $sender_id,
            $commercial["commercial_user_id"],
            $request_id,
            "Un cliente tuyo ha aceptado una oferta",
            $message_commercial,
            "Tu cliente ha aceptado una oferta",
            "offer-accepted",
            ["request" => $request]
        );
    }

    json_response("ok", "¡Oferta aceptada!<br><br>Nuestro equipo se pone manos a la obra, te actualizaremos con novedades.", 3977094433);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 1791966303);
    }
    json_response("ko", "No se ha podido aceptar la oferta. Inténtalo de nuevo, por favor.", 1791966303);
}
