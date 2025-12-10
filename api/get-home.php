<?php
$_GET["id"] = $_POST["requestId"];
require (CONTROLLER . "/home.php");
json_response("ok", "", 947166859, $info);
?>