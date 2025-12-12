<?php
if (user_can_access_request($_POST["request_id"]) !== true) {
    json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 625367886);
}

try {
    $pdo->beginTransaction();

    // 1) Insertar mensaje
    $stmt = $pdo->prepare("
        INSERT INTO `messages_v2`
        SET content = :content, user_id = :user_id, request_id = :request_id
    ");
    $stmt->bindValue(":content", htmlspecialchars($_POST["message"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":request_id", $_POST["request_id"]);
    $stmt->execute();
    $message_id = $pdo->lastInsertId();

    app_log("message", $message_id, "create", "request", $_POST["request_id"]);

    // 2) Datos comunes para notificaciones
    $request     = get_request($_POST["request_id"]);
    $sender_id   = USER["id"];
    $receiver_id = cliente()
        ? get_request_provider($_POST["request_id"])["id"]
        : $request["user_id"];

    $title       = "Nuevo mensaje";
    $description = cliente()
        ? "Nuevo mensaje de " . USER["name"] . " " . USER["lastname"]
        : "Nuevo mensaje de tu asesor";

    // 3) Notificar a la otra parte
    if (!cliente()) {
        // Asesor -> cliente (con email)
        $email_subject  = "Notificaci��n importante de tu asesor";
        $email_template = "message-store";
        notification_v2(
            $sender_id,
            $receiver_id,
            $_POST["request_id"] . "#tab-chat",
            $title,
            $description,
            $email_subject,
            $email_template
        );
    } else {
        // Cliente -> asesor (sin email, como ya ten��as)
        notification(
            $sender_id,
            $receiver_id,
            $_POST["request_id"] . "#tab-chat",
            $title,
            $description
        );
    }

// Comercial (si existe) �� igual que el proveedor: NOTIFICACI�0�7N normal (sin email)
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
    $commercial_title = cliente()
        ? "Nuevo mensaje de " . USER["name"] . " " . USER["lastname"]   // cliente -> asesor
        : "Nuevo mensaje para tu cliente";                               // asesor -> cliente

    $commercial_desc = "Se ha enviado un nuevo mensaje en la solicitud #" . $_POST["request_id"];

    notification(
        $sender_id,
        $commercial["commercial_user_id"],
        $_POST["request_id"] . "#tab-chat",
        $commercial_title,
        $commercial_desc
    );
}


    // 5) Commit y respuesta ��nica
    $pdo->commit();

    $data = ["message_id" => $message_id];
    json_response("ok", "", 1809095224, $data);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (defined("DEBUG") && DEBUG === true) {
        json_response("ko", $e->getMessage(), 2747131672);
    } else {
        json_response("ko", "Ha ocurrido un error.<br><br>El mensaje no se ha podido enviar.", 3094047378);
    }
}
