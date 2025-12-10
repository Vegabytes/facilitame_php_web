<?php
global $pdo;

$requests = get_requests();
$customers = get_customers();
$rescheduled_requests = [];
$info = compact("requests", "customers");

if (cliente())
{
    $notifications_card = merge_notifications_requests($requests, NOTIFICATIONS, 100);
    $sales_rep_code = get_customer_sales_rep_code(USER["id"]);
    $info = array_merge($info, compact("notifications_card", "sales_rep_code"));
}

if (proveedor())
{
    $incidents = get_incidents($requests);
    $statuses = get_statuses();
    foreach ($statuses as &$status)
    {
        if ($status["status_name"] == "Iniciado") $status["status_name"] = "Iniciada";
    }
    $reviews = get_reviews($requests);
    $rescheduled_requests = get_rescheduled_requests();

    $info = array_merge($info, compact("incidents", "statuses", "reviews", "rescheduled_requests"));
}

if (comercial())
{
    $query = "SELECT users.*, codes.code FROM `users` LEFT JOIN sales_codes codes ON codes.user_id = users.id WHERE users.id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $user = $stmt->fetch();
    $rescheduled_requests = get_rescheduled_requests();

    $info = array_merge($info, compact("user", "rescheduled_requests"));
}

if (admin())
{
    // $log = get_log();
    // $info = array_merge($info, compact("log"));
    $incidents = get_incidents($requests);
    $statuses = get_statuses();
    foreach ($statuses as &$status)
    {
        if ($status["status_name"] == "Iniciado") $status["status_name"] = "Iniciada";
    }
    $reviews = get_reviews($requests);
    $rescheduled_requests = get_rescheduled_requests();
    $info = array_merge($info, compact("incidents", "statuses", "reviews", "rescheduled_requests"));

}

?>