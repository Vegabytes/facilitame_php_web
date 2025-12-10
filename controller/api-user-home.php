<?php
// controller api
$requests = get_requests();
$customers = get_customers();

$info = compact("requests", "customers");

if (cliente())
{
    $notifications_card = merge_notifications_requests($requests, NOTIFICATIONS, 3);
    $info = array_merge($info, compact("notifications_card"));
}

?>