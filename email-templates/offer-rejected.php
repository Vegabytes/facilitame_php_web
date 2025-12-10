<?php
$mid = $mid ?? time();
?>
<p>Hola, <?php echo isset($user["name"]) ? mb_convert_case($user["name"], MB_CASE_TITLE, "UTF-8") : "usuario/a" ?>,</p>
<p>Lamentamos informarte que la oferta correspondiente a la solicitud <b>#<?php echo $request_id ?></b> ha sido <b>rechazada</b> por el cliente.</p>

<?php if (!empty($request["title"])): ?>
    <p><b>Asunto de la solicitud:</b> <?php echo htmlspecialchars($request["title"]); ?></p>
<?php endif; ?>

<p>Puedes consultar los detalles o realizar un seguimiento desde la plataforma:</p>
<p>
    <b><a href="<?php echo ROOT_URL ?>/request?id=<?php echo $request_id ?>">👉 Acceder a la App</a></b>
</p>
<p>Si tienes alguna duda o necesitas más información, nuestro equipo está disponible para ayudarte.</p>
<p>Un cordial saludo,<br><b>El equipo de Facilítame.</b></p>
