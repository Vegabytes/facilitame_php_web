<?php
$mid = 8844552214;
$advisory_name = $data["advisory_name"] ?? "Nueva asesoría";
$advisory_cif = $data["advisory_cif"] ?? "";
$advisory_email = $data["advisory_email"] ?? "";
$user_name = $data["user_name"] ?? "";
?>
<p style="font-size:1.2rem"><b>Nueva asesoría pendiente de aprobación</b></p>
<p>Se ha registrado una nueva asesoría que requiere tu aprobación:</p>
<table style="border-collapse: collapse; margin: 15px 0;">
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Razón Social:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($advisory_name) ?></td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">CIF:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($advisory_cif) ?></td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email empresa:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($advisory_email) ?></td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Usuario:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($user_name) ?></td>
    </tr>
</table>
<p>Accede al panel de administración para revisar y aprobar la solicitud:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/administrador/advisories">Ver asesorías pendientes</a></b></p>
<br>
<p>Atentamente,<br><b>Sistema Facilítame</b></p>
