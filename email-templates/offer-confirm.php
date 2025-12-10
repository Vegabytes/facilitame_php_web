<?php
$mid = 1619771251;
?>
<p>Hola, <?php echo mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") ?></p>
<p>Tu asesor ha recibido la confirmación de la oferta que has aceptado. Ahora tu solicitud está en marcha, y nuestro equipo se asegurará de que todo se gestione perfectamente.</p>
<p>Puedes consultar todos los detalles o gestionar cualquier seguimiento desde nuestra plataforma:</p>
<p><b><a href="<?php echo ROOT_URL ?>/request?id=<?php echo $request_id ?>">Acceder a la App</a></b></p>
<p>Estamos aquí para ayudarte en cualquier momento.</p>
<p>Un cordial saludo,<br><b>El equipo de Facilítame.</b></p>