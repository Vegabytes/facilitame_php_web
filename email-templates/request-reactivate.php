<?php
$mid = $mid ?? time();
?>
<p>Hola, <?php echo mb_convert_case($user["name"] ?? "", MB_CASE_TITLE, "UTF-8"); ?>,</p>

<p>Te informamos que tu solicitud <b>#<?php echo htmlspecialchars($data["id"] ?? "", ENT_QUOTES, 'UTF-8'); ?></b> ha sido <b>reactivada</b> con éxito.</p>

<?php if (!empty($data["motivo"])): ?>
    <p>Motivo de la reactivación:</p>
    <blockquote style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #ccc;">
        <?php echo nl2br(htmlspecialchars($data["motivo"], ENT_QUOTES, 'UTF-8')); ?>
    </blockquote>
<?php endif; ?>

<p>Puedes consultar todos los detalles y gestionarla desde nuestra plataforma.</p>

<p>Un saludo,<br><b>El equipo de Facilítame.</b></p>
