<?php
$user = new User();
if (!in_array($user->role, ["autonomo", "empresa", "particular"]))
{
    json_response("ko", "No puedes enviar invitaciones.", 1895103420);
}

if (!filter_var($_POST["email_friend"], FILTER_VALIDATE_EMAIL))
{
    json_response("ko", "Escribe una dirección válida", 1032748423);
}

try
{
    $pdo->beginTransaction();
    
    $query = "SELECT * FROM `users` WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":email", $_POST["email_friend"]);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($res))
    {
        $pdo->rollBack();
        json_response("ko", "El email indicado ya está registrado en Facilítame", 2797098458);        
    }

    // Envío del email con id de usuario en b64 :: inicio
    ob_start();
    ?>
    <p style="font-size:1.2rem"><b>¡Hola!</b></p>
    <br>
    <p><?php echo username() ?> te ha invitado a unirte a Facilítame haciendo click en el siguiente enlace: </p>
    <p>Haz click <b><a target="_blank" href="<?php echo ROOT_URL ?>/sign-up?referal=<?php echo base64_encode($user->id) ?>">aquí</a></b>.</p>
    <br>
    <p><b>¡Te esperamos!</b></p>
    <?php
    $body = ob_get_clean();    
    send_mail($_POST["email_friend"], "", "¡Únete a Facilítame!", $body, 3664684080);
    // Envío del email con id de usuario en b64 :: fin

    app_log("invite", "", "customer_invite_send");

    $message = "¡Invitación enviada!";
    json_response("ok", $message, 2908874014);
    
    $pdo->commit();
}
catch (Throwable $e)
{
    $pdo->rollBack();
}

?>