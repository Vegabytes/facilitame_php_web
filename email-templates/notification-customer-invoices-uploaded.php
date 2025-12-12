<?php
$mid = 8844552218;
$advisory_name = $data["advisory_name"] ?? "Tu asesoría";
$count = $data["count"] ?? 1;
?>
<p style="font-size:1.2rem"><b>Nuevas facturas en tu cuenta</b></p>
<p><strong><?php echo htmlspecialchars($advisory_name) ?></strong> ha subido <?php echo $count ?> factura<?php echo $count > 1 ? 's' : '' ?> a tu cuenta.</p>
<p>Puedes ver tus facturas accediendo a tu panel de cliente:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/invoices">Ver mis facturas</a></b></p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilitame</b></p>
