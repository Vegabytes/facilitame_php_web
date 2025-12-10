<?php
$mid = 2422945360;
?>
<p>Hola, <?php echo mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") ?>,</p>
<p>La solicitud <b>#<?php echo $data["id"] ?></b> ha sido <b>aplazada</b> por el usuario.</p>
<p>La nueva fecha prevista es <b><?php echo $data["fecha"] ?></b>.</p>
<p>Puedes consultar todos los detalles en la plataforma y ponerte en contacto si fuera necesario.</p>
<p>Un saludo,<br><b>El equipo de Facilítame.</b></p>
