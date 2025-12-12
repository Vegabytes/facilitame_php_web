<?php
if (!cliente() || !user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes rechazar esta oferta.", 3286504615);
}

try {
    $pdo->beginTransaction();

    // Marcar la oferta como rechazada
    $query = "UPDATE `offers` SET status_id = 5, rejected_at = CURRENT_TIMESTAMP(), reject_reason = :reject_reason WHERE id = :offer_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":offer_id", $_POST["offer_id"]);
    $stmt->bindValue(":reject_reason", filter_var($_POST["reject_reason"], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $stmt->execute();

    app_log("offer", $_POST["offer_id"], "reject", "request", $_POST["request_id"]);

    $request = get_request($_POST["request_id"]);
    $sender_id = USER["id"];
    $receiver_id = get_request_provider($_POST["request_id"])["id"];
    $title = "Oferta rechazada";
    $message = USER["name"] . " " . USER["lastname"] . " ha rechazado una oferta";

    // Notificar al proveedor
    notification(
        $sender_id,
        $receiver_id,
        $_POST["request_id"],
        $title,
        $message
    );

    // --- Notificar también al comercial ---
    $query = "SELECT sc.id as sales_code_id, sc.code, u.id as commercial_user_id
              FROM customers_sales_codes csc
              JOIN sales_codes sc ON csc.sales_code_id = sc.id
              JOIN users u ON sc.user_id = u.id
              WHERE csc.customer_id = :customer_id
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":customer_id", $request["user_id"]); // El cliente creador de la solicitud
    $stmt->execute();
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commercial) {
        $commercial_id = $commercial["commercial_user_id"];
        $message_commercial = USER["name"] . " " . USER["lastname"] . " ha rechazado una oferta para la solicitud #" . $_POST["request_id"];
        notification_v2(
            $sender_id,
            $commercial_id,
            $_POST["request_id"],
            "Un cliente tuyo ha rechazado una oferta",
            $message_commercial,
            "Tu cliente ha rechazado una oferta",
            "offer-rejected", // plantilla email para comercial
            [
                "request" => $request,
                // puedes meter más campos aquí si quieres
            ]
        );
    }

    // --------- Notificar al ADMIN (opcional) ---------
    notification_v2(
        $sender_id,
        ADMIN_ID,
        $_POST["request_id"],
        "Oferta rechazada",
        "El cliente ha rechazado una oferta para la solicitud #" . $_POST["request_id"],
        "Oferta rechazada por el cliente",
        "offer-rejected", // Plantilla específica para admin
        [
            "request" => $request,
        ]
    );

    $pdo->commit();

    // Actualizar el estado de la solicitud
    $query = "SELECT * FROM `offers` WHERE request_id = :request_id AND deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $offers_total = count($offers);
    if ($offers_total === 1) {
        $query = "UPDATE `requests` SET status_id = 5 WHERE id = :request_id"; // Rechazada
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $_POST["request_id"]);
        $stmt->execute();
    } else {
        // Si hay alguna oferta activada, poner solicitud en activada
        $activated_offers = array_filter($offers, function($offer) {
            return $offer["status_id"] == 7;
        });

        if (count($activated_offers) > 0) {
            $query = "UPDATE `requests` SET status_id = 7 WHERE id = :request_id"; // Activada
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":request_id", $_POST["request_id"]);
            $stmt->execute();
        }
        else {
            // Caso actual: todas rechazadas → solicitud rechazada
            $rejected_offers = array_filter($offers, function($offer) {
                return $offer["status_id"] == 5;
            });
            if (count($rejected_offers) == $offers_total) {
                $query = "UPDATE `requests` SET status_id = 5 WHERE id = :request_id"; // Rechazada
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":request_id", $_POST["request_id"]);
                $stmt->execute();
            } else {
                $query = "UPDATE `requests` SET status_id = 2 WHERE id = :request_id"; // Oferta disponible
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":request_id", $_POST["request_id"]);
                $stmt->execute();
            }
        }
    }


    define("BYPASS", true);
    $_POST["comment"] = "---- OFERTA RECHAZADA ----";
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Oferta rechazada correctamente", 2527611799);
}
catch (Exception $e) {
    $pdo->rollBack();
    if (DEBUG) {
        json_response("ko", $e->getMessage(), 2641812887);
    }
    json_response("ko", "No se ha podido rechazar la oferta. Inténtalo de nuevo, por favor.", 496688580);
}
