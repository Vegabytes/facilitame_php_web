<?php

use Ramsey\Uuid\Uuid;

try
{
    $pdo->beginTransaction();

    $query = "SELECT * FROM `users` WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false)
    {
        throw new Exception("", 1);
    }

    $token = Uuid::uuid4() . "-" . Uuid::uuid4() . "-" . Uuid::uuid4();
    $expires_at = (new DateTime("now"))->modify("+1 hour")->format("Y-m-d H:i:s");

    $query = "DELETE FROM `password_recovery_tokens` WHERE user_id = :user_id AND used_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user["id"]);
    $stmt->execute();

    $query = "INSERT INTO `password_recovery_tokens` SET
    user_id = :user_id,
    token = :token,
    expires_at = :expires_at";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user["id"]);
    $stmt->bindValue(":token", $token);
    $stmt->bindValue(":expires_at", $expires_at);
    $stmt->execute();
    $token_id = $pdo->lastInsertId();
    app_log("customer", $user["id"], "recovery_token_created", "customer", $user["id"], $user["id"]);

    // Envío del email con instrucciones de recuperación de contraseña :: inicio
    ob_start();
?>
    <p style="font-size:1.2rem"><b>Hola <?php echo $user["name"] ?>,</b></p>
    <br>
    <p>Hemos recibido su solicitud para restablecer la contraseña de tu cuenta en Facilitame. Por favor, haga clic en el botón a continuación para crear una nueva contraseña:</p>

    <p style="font-size:1.5rem; color:#00C2CB!important"><b><a target="_blank" href="<?php echo ROOT_URL ?>/restore?token=<?php echo $token ?>">👉 Restablecer contraseña</a></b></p>
    <br>

    <p style="font-size:1.2rem;">Si no has realizado esta solicitud, te sugerimos ignorar este correo y ponerte en contacto con nuestro equipo si consideras que tu cuenta puede estar comprometida, a través del siguiente correo electrónico info@facilitame.es</p>
    <p style="font-size:1.2rem;">Gracias por utilizar Facilitame. Estamos a tu disposición para cualquier duda o consulta.</p>
    <p style="font-size:1.2rem;">Atentamente,<br><b>El equipo de Facilítame</b></p>
<?php
    $body = ob_get_clean();
    send_mail($_POST["email"], $user["name"], "Solicitud de recuperación de contraseña", $body, 3099047681);
    app_log("customer", $user["id"], "recovery_email_sent", "customer", $user["id"], $user["id"]);
    // Envío del email con instrucciones de recuperación de contraseña :: fin

    $pdo->commit();
}
catch (Throwable $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        var_dump($e->getMessage());exit;
    }
}

json_response("ok", "Si ya hay una cuenta de Facilítame asociada a " . htmlspecialchars($_POST["email"]) . ", te enviaremos por correo electrónico las instrucciones para restablecer tu contraseña.", 251623900);
exit;
?>