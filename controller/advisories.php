<?php
// /controller/advisories.php

$user = new User();
$info["advisories"] = []; // Los datos se cargan vía API con JavaScript
compact("info");
?>