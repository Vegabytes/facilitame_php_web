<?php
$user = new User();
$info["customers"] = get_customers($user);
compact("info");
?>