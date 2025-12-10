<?php
$mid = 38688850;
?>
<p>Hola, <?php echo mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") ?></p>
<p>Tu asesor ha respondido a tu solicitud. Te invitamos a acceder a la plataforma para revisar el mensaje y continuar con el proceso.</p>
<p>Haz clic en el enlace para acceder:</p>
<p><b><a href="<?php echo ROOT_URL ?>/request?id=<?php echo $request_id ?>">👉 Revisar Mensaje</a></b></p>
<p>Gracias por utilizar Facilítame. Estamos aquí para asegurarnos de que recibas el mejor servicio posible.</p>
<p>Atentamente,<br><b>El equipo de Facilítame.</b></p>
