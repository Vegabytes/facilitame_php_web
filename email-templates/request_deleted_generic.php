<?php
$mid = 987654321; // ID único del mensaje/plantilla
?>
<p>Hola, <?php echo mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8"); ?>,</p>

<p>La solicitud <b>#<?php echo $data["id"]; ?></b> ha sido <b>eliminada</b> por el usuario.</p>

<?php if (!empty($data["motivo"])): ?>
    <p>Motivo proporcionado:</p>
    <blockquote style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #ccc;">
        <?php echo nl2br(htmlspecialchars($data["motivo"], ENT_QUOTES, 'UTF-8')); ?>
    </blockquote>
<?php endif; ?>

<p>Puedes consultar todos los detalles en la plataforma y ponerte en contacto si fuera necesario.</p>

<p>Un saludo,<br><b>El equipo de Facilítame.</b></p>