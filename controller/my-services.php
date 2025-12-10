<?php
// $services = get_user_services();
// $info = compact("services");
$requests = get_requests();
$statuses = get_statuses_names();
$info = compact("requests", "statuses");
?>