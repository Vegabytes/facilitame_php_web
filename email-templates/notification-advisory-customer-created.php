<?php
$mid = 8844552215;
$customer_name = $data["customer_name"] ?? "Nuevo cliente";
$customer_email = $data["customer_email"] ?? "";
$client_type = $data["client_type"] ?? "";
?>
<p style="font-size:1.2rem"><b>Cliente creado correctamente</b></p>
<p>Has creado un nuevo cliente en Facilítame:</p>
<table style="border-collapse: collapse; margin: 15px 0;">
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Nombre:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($customer_name) ?></td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($customer_email) ?></td>
    </tr>
    <?php if ($client_type): ?>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Tipo:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(ucfirst($client_type)) ?></td>
    </tr>
    <?php endif; ?>
</table>
<p>Se ha enviado un email de activación al cliente. Una vez que active su cuenta, podrás gestionar sus servicios.</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/asesoria/clients">Ver mis clientes</a></b></p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilítame</b></p>
