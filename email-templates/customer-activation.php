<?php
$mid = 8844552211;
$name = $data["name"] ?? "Cliente";
$token = $data["token"] ?? "";
$advisory_name = $data["advisory_name"] ?? "tu asesoría";
?>
<p style="font-size:1.2rem"><b>¡Bienvenido <?php echo htmlspecialchars($name) ?> a Facilítame!</b></p>

<p><?php echo htmlspecialchars($advisory_name) ?> te ha dado de alta en Facilítame, tu plataforma de gestión administrativa.</p>

<p>Para activar tu cuenta y establecer tu contraseña, haz clic en el siguiente enlace:</p>

<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/activate?token=<?php echo $token ?>">Activa tu cuenta aquí</a></b></p>

<p><small><b>Importante:</b> Este enlace es válido durante 24 horas.</small></p>

<p>Con Facilítame podrás ahorrar y simplificar la gestión de todos tus servicios en un solo lugar.</p>

<p>A partir de ahora, cuentas con nuestro equipo para cualquier consulta o gestión.</p>

<br>
<p>Atentamente,<br><b>El Equipo de Facilítame</b></p>