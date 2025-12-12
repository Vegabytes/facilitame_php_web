<?php
$mid = 8844552213;
$customer_name = $data["customer_name"] ?? "Un cliente";
$customer_email = $data["customer_email"] ?? "";
$advisory_name = $data["advisory_name"] ?? "tu asesoría";
?>
<p style="font-size:1.2rem"><b>Nuevo cliente vinculado</b></p>
<p><b><?php echo htmlspecialchars($customer_name) ?></b> se ha vinculado a <b><?php echo htmlspecialchars($advisory_name) ?></b> usando tu código de asesoría.</p>
<p>Datos del cliente:</p>
<ul>
    <li><b>Nombre:</b> <?php echo htmlspecialchars($customer_name) ?></li>
    <?php if ($customer_email): ?>
    <li><b>Email:</b> <?php echo htmlspecialchars($customer_email) ?></li>
    <?php endif; ?>
</ul>
<p>Ya puedes acceder a su perfil y gestionar sus servicios desde Facilítame:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/asesoria/clients">Ver mis clientes</a></b></p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilítame</b></p>
