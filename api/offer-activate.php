<?php
if (!proveedor())
{
    json_response("ko", "No puedes realizar esta acción.", 3905545310);
}

// Comprobar que se ha indicado una fecha de vencimiento :: inicio
if (!isset($_POST["expires_at"]) || empty($_POST["expires_at"]))
{
    json_response("ko", "Debes indicar una fecha de vencimiento", 4238968847);
}
// Comprobar que se ha indicado una fecha de vencimiento :: fin

// Comprobar que la oferta no tiene ninguna oferta aceptada o activa :: inicio
$query = "SELECT * FROM `offers` WHERE 1
AND deleted_at IS NULL
AND request_id = :request_id
AND status_id IN (3,7)"; // aceptada, activada
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 0)
{
    json_response("ko", "Hay otra oferta aceptada o activada. No se puede activar otra oferta.", 350347226);
}
// Comprobar que la oferta no tiene ninguna oferta aceptada o activa :: fin

// Comprobar que la solicitud está en un estado que permita activar la oferta :: inicio
$query = "SELECT * FROM `requests` WHERE 1
AND deleted_at IS NULL
AND id = :request_id
AND status_id IN (4)";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1)
{
    json_response("ko", "La solicitud no está marcada como 'en curso'. No se puede activar la oferta.", 1502275168);
}
// Comprobar que la solicitud está en un estado que permita activar la oferta :: fin

// Comprobar que sólo hay una oferta en estado 4 (en curso) :: inicio
$query = "SELECT * FROM `offers` WHERE 1
AND deleted_at IS NULL
AND request_id = :request_id
AND status_id = 4";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":request_id", $_POST["request_id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1)
{
    json_response("ko", "No hay una única oferta marcada como 'en curso' para esta solicitud. No se puede activar la oferta", 943599309);
}
// Comprobar que sólo hay una oferta en estado 4 (en curso) :: fin

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `offers` WHERE request_id = :request_id AND status_id = 4 AND deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    $offer_to_activate = $stmt->fetch();

    // Marcar como eliminadas todas aquellas ofertas de la solicitud excepto la que está en estado 4 (confirmada) :: inicio
    $query = "UPDATE `offers` SET deleted_at = CURRENT_TIMESTAMP WHERE 1
    AND request_id = :request_id
    AND status_id != 4
    AND deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    // Marcar como eliminadas todas aquellas ofertas de la solicitud excepto la que está en estado 4 (confirmada) :: fin

    $query = "UPDATE `offers` SET status_id = 7, activated_at = CURRENT_TIMESTAMP(), expires_at = :expires_at WHERE status_id = 4 AND request_id = :request_id"; // activada
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->bindValue(":expires_at", $_POST["expires_at"]);
    $stmt->execute();
    app_log("offer", $offer_to_activate["id"], "active", "request", $_POST["request_id"]);
    
    $query = "UPDATE `requests` SET status_id = 7 WHERE id = :request_id"; // activada
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    app_log("request", $_POST["request_id"], "active", "request", $_POST["request_id"]);

    $request = get_request($_POST["request_id"]);
    $sender_id = USER["id"];
    $receiver_id = $request["user_id"];    
    $title = "¡Oferta activa!";
    $description = "¡Tu solictud y oferta están activas!";

    // Notificar al cliente
    $email_subject = "¡Servicio activado con éxito!";
    $email_template = "offer-activate";
    notification_v2(
        $sender_id,
        $receiver_id,
        $_POST["request_id"],
        $title,
        $description,
        $email_subject,
        $email_template
    );

    // -------- Notificar al ADMIN --------
    notification_v2(
        $sender_id,
        ADMIN_ID, // asumiendo que tienes esta constante definida
        $_POST["request_id"],
        "¡Oferta activada!",
        "El proveedor ha activado una oferta para la solicitud #" . $_POST["request_id"],
        "¡Servicio activado con éxito!",
        "offer-activate", // crea una plantilla específica o usa la misma
        [
            "request" => $request,
            "offer" => $offer_to_activate,
        ]
    );

    // -------- Notificar al COMERCIAL --------
    // Buscar el comercial asociado al cliente de la solicitud
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
            "¡Oferta activada para tu cliente!",
            "Una oferta ha sido activada para la solicitud #" . $_POST["request_id"],
            "¡Oferta de tu cliente activada!",
            "offer-activate", // pon la plantilla que prefieras
            [
                "request" => $request,
                "offer" => $offer_to_activate,
            ]
        );
    }

    $pdo->commit();

    define("BYPASS", true);
    $_POST["comment"] = "---- OFERTA ACTIVADA ----";
    require ROOT_DIR . "/api/request-add-provider-comment.php";

    json_response("ok", "Oferta activada", 1026959706);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG)
    {
        json_response("ko", $e->getMessage(), 211500276);
    }
    json_response("ko", "No se ha podido activar la oferta. Inténtalo de nuevo, por favor.", 2587598536);
}
