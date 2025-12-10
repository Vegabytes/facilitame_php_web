<?php

// Envío del email con el enlace de activación :: inicio
$name_sanitized = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$name = $_POST["name"] === $name_sanitized ? " $name_sanitized " : "";
ob_start();
?>
<p style="font-size:1.2rem"><b>Bienvenido<?php echo $name; ?>a Facilítame</b></p>
<p>¡Es un placer tenerte con nosotros!</p>
<p>Con Facilitame podrás ahorrar y simplificar la gestión de todos tus servicios en un solo lugar.</p>
<p>Para empezar a disfrutar de todas las ventajas, por favor verifica tu cuenta:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/activate?token=<?php echo $verification_token ?>">👉 Verifica tu cuenta aquí</a></b></p>
<p>A partir de ahora, cuentas con nuestro equipo para cualquier consulta o gestión.</p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilítame</b></p>
<?php
$body = ob_get_clean();
$subject = "Activa tu cuenta de Facilítame";
$data["send"] = send_mail($_POST["email"], $_POST["name"], $subject, $body, 3869343253);
// Envío del email con el enlace de activación :: fin

$message = "";
json_response("ok", $message, 2376916132, $data);
?>