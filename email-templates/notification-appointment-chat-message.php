<?php
$mid = 8844552217;
$sender_name = $data["sender_name"] ?? "Usuario";
$appointment_id = $data["appointment_id"] ?? "";
$message_preview = $data["message_preview"] ?? "";
?>
<p style="font-size:1.2rem"><b>Nuevo mensaje en tu cita</b></p>
<p><strong><?php echo htmlspecialchars($sender_name) ?></strong> te ha enviado un mensaje:</p>
<?php if ($message_preview): ?>
<div style="background: #f8f9fa; border-left: 4px solid #00c2cb; padding: 15px; margin: 15px 0; border-radius: 4px;">
    <p style="margin: 0; color: #333;">"<?php echo htmlspecialchars($message_preview) ?>"</p>
</div>
<?php endif; ?>
<p>Accede a la cita para ver el mensaje completo y responder:</p>
<p><b><a target="_blank" href="<?php echo ROOT_URL ?>/appointment?id=<?php echo $appointment_id ?>">Ver cita y responder</a></b></p>
<br>
<p>Atentamente,<br><b>El Equipo de Facilitame</b></p>
