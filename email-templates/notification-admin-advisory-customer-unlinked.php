<?php
$mid = 8844552216;
$advisory_name = $data["advisory_name"] ?? "Asesoría";
$advisory_cif = $data["advisory_cif"] ?? "";
$customer_name = $data["customer_name"] ?? "Cliente";
$customer_email = $data["customer_email"] ?? "";
?>
<p style="font-size:1.2rem"><b>Cliente desvinculado de asesoría</b></p>
<p>Una asesoría ha desvinculado a un cliente:</p>
<table style="border-collapse: collapse; margin: 15px 0;">
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Asesoría:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($advisory_name) ?></td>
    </tr>
    <?php if ($advisory_cif): ?>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">CIF:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($advisory_cif) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Cliente:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($customer_name) ?></td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email cliente:</td>
        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($customer_email) ?></td>
    </tr>
</table>
<p>El cliente sigue activo en la plataforma pero ya no está vinculado a esta asesoría.</p>
<br>
<p>Atentamente,<br><b>Sistema Facilitame</b></p>
