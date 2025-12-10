<?php
/**
 * API: Solicitar revisión de oferta
 * POST /api/offer-review-request
 *
 * Permitido para: clientes (dueño de la solicitud) y comerciales (del cliente)
 */

// Verificar que el usuario tiene acceso (solo clientes pueden solicitar revisiones)
if (!cliente() && !admin()) {
    json_response("ko", "No tienes permisos para solicitar revisiones.", 579238920);
}

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes solicitar una revisión de esta oferta.", 579238927);
}

// Comprobar que la solicitud está en estado 7 (activa)
$query = "SELECT * FROM `requests` WHERE 1
AND deleted_at IS NULL
AND id = :request_id
AND status_id = 7";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1) {
    json_response("ko", "La solicitud no está activa.", 2637163826);
}

// Comprobar que sólo hay una oferta activa para la solicitud
$query = "SELECT * FROM `offers` WHERE 1
AND deleted_at IS NULL
AND request_id = :request_id
AND status_id = 7";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1) {
    json_response("ko", "No hay una única oferta activa.", 3858400364);
}

try {
    $pdo->beginTransaction();

    $query = "UPDATE `requests` SET status_id = 8 WHERE id = :request_id"; // revisión solicitada
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    app_log("request", $_POST["request_id"], "review_requested", "request", $_POST["request_id"]);

    $request = get_request($_POST["request_id"]);
    $sender_id = USER["id"];
    $provider = get_request_provider($_POST["request_id"]);
    $receiver_id = $provider["id"];
    $title = "Revisión de oferta solicitada";
    $message = USER["name"] . " " . USER["lastname"] . " ha solicitado una revisión de su oferta";

    // Notificar al proveedor (básico)
    notification(
        $sender_id,
        $receiver_id,
        $_POST["request_id"],
        $title,
        $message
    );

    // --- Notificar al comercial (opcional) ---
    $query = "SELECT sc.id as sales_code_id, sc.code, u.id as commercial_user_id
              FROM customers_sales_codes csc
              JOIN sales_codes sc ON csc.sales_code_id = sc.id
              JOIN users u ON sc.user_id = u.id
              WHERE csc.customer_id = :customer_id
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":customer_id", $request["user_id"]);
    $stmt->execute();
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commercial) {
        notification_v2(
            $sender_id,
            $commercial["commercial_user_id"],
            $_POST["request_id"],
            "Tu cliente ha solicitado revisión de la oferta",
            $message,
            "Revisión de oferta solicitada por tu cliente",
            "offer-review-request", // plantilla para comercial
            [
                "request" => $request,
            ]
        );
    }

    // --- Notificar al ADMIN (opcional) ---
    notification_v2(
        $sender_id,
        ADMIN_ID,
        $_POST["request_id"],
        "Revisión de oferta solicitada",
        "Un cliente ha solicitado revisión de la oferta para la solicitud #" . $_POST["request_id"],
        "Revisión de oferta solicitada",
        "offer-review-request", // plantilla admin
        [
            "request" => $request,
        ]
    );

    $pdo->commit();

    define("BYPASS", true);
    $_POST["comment"] = "---- REVISIÓN SOLICITADA ----";
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "¡Solicitud de revisión enviada!<br><br>Nuestro equipo se pone manos a la obra, te actualizaremos con novedades.", 54255269);
} catch (Exception $e) {
    $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 248791863);
    }
    json_response("ko", "No se ha podido solicitar la revisión de la oferta. Inténtalo de nuevo, por favor.", 2412164303, $e->getMessage());
}
