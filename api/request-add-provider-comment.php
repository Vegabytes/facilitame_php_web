<?php
$log = './mi_log_personal.log';
file_put_contents($log, date('c') . " ---- INICIO add_provider_comment.php ----\n", FILE_APPEND);

try {
    // Seguridad de acceso (respetando BYPASS)
    if ((!defined("BYPASS") || BYPASS !== true) && (!user_can_access_request($_POST["request_id"] ?? null))) {
        file_put_contents($log, date('c') . " ACCESO DENEGADO a request_id " . ($_POST["request_id"] ?? 'NULL') . "\n", FILE_APPEND);
        json_response("ko", "No puedes añadir comentarios", 2203617145);
    }

    $request_id = $_POST["request_id"] ?? null;
    $raw_comment = $_POST["comment"] ?? '';
    file_put_contents($log, date('c') . " Request ID recibido: {$request_id}\n", FILE_APPEND);

    if (!$request_id) {
        file_put_contents($log, date('c') . " ERROR: request_id vacío\n", FILE_APPEND);
        json_response("ko", "Falta request_id", 9990001);
    }

    // Carga comentario previo (si existe)
    $query = "SELECT comments FROM `provider_comments` WHERE request_id = :request_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result === false) {
        file_put_contents($log, date('c') . " No existe registro en provider_comments para request_id {$request_id}\n", FILE_APPEND);
        $previous = '';
    } else {
        $previous = (string)$result["comments"];
        file_put_contents($log, date('c') . " Comentario anterior length=" . strlen($previous) . "\n", FILE_APPEND);
    }

    // Sanitiza y concatena con nombre del usuario
    $new_comment = filter_var($raw_comment, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $user_name = trim(USER["name"] . " " . (USER["lastname"] ?? ""));
    $full_comment = $previous . "\n" . date("d/m/y H:i") . " [" . $user_name . "] :: " . $new_comment;
    file_put_contents($log, date('c') . " Comentario nuevo length=" . strlen($full_comment) . "\n", FILE_APPEND);

    // Transacción local
    $pdo->beginTransaction();
    file_put_contents($log, date('c') . " Comienza la transacción\n", FILE_APPEND);

    // UPDATE primero
    $stmt = $pdo->prepare("
        UPDATE `provider_comments`
        SET provider_id = :provider_id, comments = :comments
        WHERE request_id = :request_id
    ");
    $stmt->execute([
        ":provider_id" => USER["id"],
        ":comments"    => $full_comment,
        ":request_id"  => $request_id
    ]);

    file_put_contents($log, date('c') . " UPDATE ejecutado. Filas afectadas: {$stmt->rowCount()}\n", FILE_APPEND);

    // Si no afectó filas, INSERT de respaldo
    if ($stmt->rowCount() === 0) {
        file_put_contents($log, date('c') . " INSERT de respaldo en provider_comments\n", FILE_APPEND);
        $ins = $pdo->prepare("
            INSERT INTO `provider_comments` (request_id, provider_id, comments)
            VALUES (:request_id, :provider_id, :comments)
        ");
        $ins->execute([
            ":request_id"  => $request_id,
            ":provider_id" => USER["id"],
            ":comments"    => $full_comment
        ]);
        file_put_contents($log, date('c') . " INSERT OK. Filas: {$ins->rowCount()}\n", FILE_APPEND);
    }

    // Log de auditoría
    app_log("message_provider", "", "create", "request", $request_id);
    file_put_contents($log, date('c') . " app_log ejecutado\n", FILE_APPEND);

    $pdo->commit();
    file_put_contents($log, date('c') . " Commit de la transacción OK\n", FILE_APPEND);

    // Datos para respuesta (si se usa standalone)
    $data = ["comments" => html_entity_decode($full_comment)];

    // Notificaciones (con guards y logs)
    try {
        $request  = get_request($request_id);
        $provider = get_request_provider($request_id);
        $sales_rep_id = customer_get_sales_rep($request["user_id"]);

        file_put_contents(
            $log,
            date('c') . " Notificaciones: admin=" . (int)admin() .
            " comercial=" . (int)comercial() . " proveedor=" . (int)proveedor() .
            " provider_id=" . ($provider['id'] ?? 'NULL') .
            " sales_rep_id=" . ($sales_rep_id ?? 'NULL') . "\n",
            FILE_APPEND
        );

        $msg_admin   = "El administrador ha añadido un comentario interno en la solicitud #" . $request["id"];
        $msg_sales   = "El equipo de ventas ha añadido un comentario interno en la solicitud #" . $request["id"];
        $msg_provider= "El colaborador ha añadido un comentario interno en la solicitud #" . $request["id"];

        if (admin()) {
            if (!empty($provider['id'])) notification(USER["id"], $provider["id"], $request["id"], "Nuevo mensaje interno", $msg_admin);
            if (!empty($sales_rep_id))   notification(USER["id"], $sales_rep_id,  $request["id"], "Nuevo mensaje interno", $msg_admin);
        } elseif (comercial()) {
            if (!empty($provider['id'])) notification(USER["id"], $provider["id"], $request["id"], "Nuevo mensaje interno", $msg_sales);
            notification(USER["id"], ADMIN_ID, $request["id"], "Nuevo mensaje interno", $msg_sales);
        } elseif (proveedor()) {
            notification(USER["id"], ADMIN_ID, $request["id"], "Nuevo mensaje interno", $msg_provider);
            if (!empty($sales_rep_id)) notification(USER["id"], $sales_rep_id, $request["id"], "Nuevo mensaje interno", $msg_provider);
        }

        file_put_contents($log, date('c') . " Notificaciones enviadas\n", FILE_APPEND);
    } catch (Throwable $nt) {
        file_put_contents($log, date('c') . " ERROR en notificaciones: {$nt->getMessage()}\n", FILE_APPEND);
        // seguimos, no rompemos el flujo
    }

    // Si se invoca directamente, responde; si BYPASS, no emitir JSON
    if (!defined("BYPASS") || BYPASS !== true) {
        file_put_contents($log, date('c') . " Salida exitosa JSON\n", FILE_APPEND);
        json_response("ok", "", 2099468703, $data);
    } else {
        file_put_contents($log, date('c') . " BYPASS activo, no se envía JSON\n", FILE_APPEND);
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        file_put_contents($log, date('c') . " Rollback de la transacción\n", FILE_APPEND);
    }
    file_put_contents($log, date('c') . " ERROR (catch): {$e->getMessage()}\n", FILE_APPEND);
    if (!defined("BYPASS") || BYPASS !== true) {
        if (DEBUG) json_response("ko", $e->getMessage(), 1214154014);
        json_response("ko", "No se ha podido añadir el comentario.", 1214154014);
    }
    // En BYPASS, silencioso: no enviar JSON para no romper al llamador
}