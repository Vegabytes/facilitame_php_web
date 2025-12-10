<?php
$_GET["id"] = $_POST["requestId"];
require (CONTROLLER . "/request.php");
json_response("ok", "", 2423897503, $info);

?>