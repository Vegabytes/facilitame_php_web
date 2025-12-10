<?php
$mid = $mid ?? time();
?>
<p>Hola, <?php echo isset($user["name"]) ? mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") : "usuario/a" ?>,</p>
<p>Te informamos que se ha solicitado una <b>revisión</b> de la oferta correspondiente a la solicitud <b>#<?php echo $request_id ?></b> en Facilítame.</p>

<?php if (!empty($request["title"])): ?>
    <p><b>Asunto de la solicitud:</b> <?php echo htmlspecialchars($request["title"]); ?></p>
<?php endif; ?>

<p>Puedes consultar los detalles y realizar el seguimiento desde la plataforma:</p>
<p>
    <b><a href="<?php echo ROOT_URL ?>/request?id=<?php echo $request_id ?>">👉 Acceder a la App</a></b>
</p>
<p>Si tienes dudas, contacta con nuestro equipo en cualquier momento.</p>
<p>Un cordial saludo,<br><b>El equipo de Facilítame.</b></p>