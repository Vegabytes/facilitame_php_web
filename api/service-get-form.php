<?php
$data = get_service_form($_POST["service_id"]);
if ($data === false)
{
    json_response("ko", "No se localiza el formulario", 2810671275);
}
else
{
    json_response("ok", "", 165593519, $data);
}
?>