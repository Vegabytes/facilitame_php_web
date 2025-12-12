<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $pdo->beginTransaction();

    $file_name_dir = __DIR__ . "/app-chat-message-store.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($_POST) . "\n", FILE_APPEND | LOCK_EX);

    // Comprobar que la solicitud pertenece al usuario
    if (user_can_access_request($_POST["request_id"]))
    {
        $query = "INSERT INTO `messages_v2` SET offer_id = NULL, request_id = :request_id, user_id = :user_id, content = :content";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $_POST["request_id"]);
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->bindValue(":content", htmlspecialchars($_POST["text"], ENT_QUOTES, 'UTF-8'));
        $stmt->execute();
        $message_id = $pdo->lastInsertId();

        app_log("message", $message_id, "create", "request", $_POST["request_id"]);

        $request = get_request($_POST["request_id"]);
        $sender_id = USER["id"];
        $provider = get_request_provider($_POST["request_id"]);
        $sales_rep_id = customer_get_sales_rep($request["user_id"]);
        $receiver_id = cliente() ? $provider["id"] : $request["user_id"];
        $title = "Nuevo mensaje";
        $description = cliente() ? "Nuevo mensaje de " . USER["name"] . " " . USER["lastname"] : "Nuevo mensaje de tu asesor";

        // Notificar a destinatario principal (proveedor o cliente)
        notification(
            $sender_id,
            $receiver_id,
            $_POST["request_id"] . "#tab-chat",
            $title,
            $description
        );

        // Si el remitente es cliente, notificar también a admin y comercial
        if (cliente()) {
            // Notificar al admin
            notification(
                $sender_id,
                ADMIN_ID,
                $_POST["request_id"] . "#tab-chat",
                $title,
                $description
            );
            // Notificar al comercial si existe
            if ($sales_rep_id) {
                notification(
                    $sender_id,
                    $sales_rep_id,
                    $_POST["request_id"] . "#tab-chat",
                    $title,
                    $description
                );
            }
        }

    }
    else
    {
        json_response("ko", "", 3335492274);
    }

    $pdo->commit();

    json_response("ok", "", 3937329967);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "", 3625123544);
}

?>
