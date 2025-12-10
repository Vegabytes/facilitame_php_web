<?php
$mid = 8844552212;
$name = $data["name"] ?? "Cliente";
$advisory_name = $data["advisory_name"] ?? "una asesoría";
?>
<p style="font-size:1.2rem"><b>¡Hola <?php echo htmlspecialchars($name) ?>!</b></p>
<p>Te informamos que <b><?php echo htmlspecialchars($advisory_name) ?></b> te ha vinculado como cliente en Facilítame.</p>
<p>A partir de ahora, tu asesoría podrá gestionar tus servicios y ayudarte con tus trámites administrativos de forma más eficiente.</p>
<p>Puedes acceder a tu cuenta en cualquier momento desde:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/login">Acceder a Facilítame</a></b></p>
<p>Si tienes cualquier duda, puedes contactar directamente con tu asesoría a través de la plataforma.</p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilítame</b></p>