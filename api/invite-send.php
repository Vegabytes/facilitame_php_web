<?php
if (!comercial())   json_response("ko", "No permitido", 2659831767);

$query = "SELECT code FROM `sales_codes` WHERE user_id = :user_id AND deleted_at IS NULL";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (count($res) !== 1)  json_response("ko", "No se localiza un código válido", 3208485837);

$code = $res[0]["code"];

// Envío del email con el código de comercial :: inicio
ob_start();
?>
<p style="font-size:1.2rem"><b>¡Hola!</b></p>
<br>
<p><?php echo username() ?> pensó en ti y te ha invitado a unirte a <b>Facilitame</b>, la app que hará tu vida mucho más fácil. Imagina tener todos tus servicios en un solo lugar... ¡y gratis! </p>
<p>¿Qué esperas? Súmate y empieza a disfrutar.</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/sign-up?code=<?php echo $code ?>">👉 Haz clic aquí para unirte</a></b>.</p>
<br>
<p>Nos vemos dentro,<br><b>El equipo de Facilitame</b></p>
<?php
$body = ob_get_clean();
send_mail($_POST["to"], "", "¡Te han invitado a Facilitame!", $body, 3033340190);
// Envío del email con el código de comercial :: fin

app_log("invite", 0, "send", "customer", USER["id"]);

$message = "Mensaje enviado";
json_response("ok", $message, 305323505);
?>