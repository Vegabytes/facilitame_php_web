<?php
$mid = 483945474;
?>
<p>Hola, <?php echo mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") ?></p>
<p>Nos complace informarte que tu asesor ha revisado tu solicitud y ha preparado una o varias ofertas para el servicio solicitado.</p>
<p>Puedes consultarlas y tomar una decisión accediendo a través del siguiente enlace:</p>
<p><b><a href="<?php echo ROOT_URL ?>/request?id=<?php echo $request_id ?>">👉Ver ofertas en la aplicación</a></b></p>
<p>Gracias por confiar en Facilítame. Nuestro equipo está disponible para cualquier consulta que pueda surgir.</p>
<p>Atentamente,<br><b>El equipo de Facilítame.</b></p>