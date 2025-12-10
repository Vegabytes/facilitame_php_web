<?php
if (!admin())
{
    header("Location:home?r=1126016187");
    exit;
}
global $pdo;

$user_id = intval($_GET["id"] ?? 0);
if ($user_id <= 0) {
    header("Location:home");
    exit;
}

$info = [];
if ($_SERVER['REMOTE_ADDR'] == '62.117.137.219')
{
    $target_user = new User($user_id);
    $info["target_user"] = $target_user;
    $info["customers"] = $target_user->getCustomersV2();
    $info["excluded_services"] = $target_user->getExcludedServices();
    $info["services"] = get_services();

    if ($target_user->role == "comercial")
    {        
        $info["comisionables"] = array_filter($target_user->requests, function ($req) {
            return (in_array($req["status_id"], [7,8]));
        });
    }
}
else
{
    $user = new User();
    $info["sales_rep"] = get_sales_rep($user_id);
    $query =
        "SELECT users.*, 0 AS services_number, roles.name AS role_name
    FROM `users`
    JOIN customers_sales_codes csc ON csc.customer_id = users.id
    JOIN model_has_roles mhr ON mhr.model_id = users.id
    JOIN roles ON roles.id = mhr.role_id
    WHERE 1
    AND csc.sales_code_id IN
    (
        SELECT id FROM sales_codes WHERE deleted_at IS NULL AND user_id = :sales_rep_id
    )";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $user_id);
    $stmt->execute();
    $sales_rep_customers = $stmt->fetchAll();
    foreach ($sales_rep_customers as $i => $customer)
    {
        $query =
            "SELECT COUNT(r.id) AS count
        FROM `requests` r    
        WHERE 1
        AND r.user_id = :user_id
        AND deleted_at IS NULL";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $customer["id"]);
        $stmt->execute();
        $count = $stmt->fetch();

        $sales_rep_customers[$i]["services_number"] = $count["count"];
        $sales_rep_customers[$i]["phone"] = phone($customer["phone"]);
    }
    // Solicitudes asociadas :: inicio
    $query =
        "SELECT r.*
    FROM requests r
    JOIN users u ON u.id = r.user_id
    JOIN customers_sales_codes cdc ON cdc.customer_id = u.id
    JOIN sales_codes codes ON codes.user_id = :sales_rep_id
    WHERE 1
    AND r.deleted_at IS NULL -- AND r.status_id IN (7,8)";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $user_id);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    // Solicitudes asociadas :: fin
    
    // Comisión total :: inicio
    $commissions = [];
    foreach ($requests as $req)
    {
        if (in_array($req["status_id"], [7, 8])) // Activada / Revisión solicitada
        {
            $commissions["solid"][$req["commision_type"]] += $req["commision"];
        }
        else
        {
            $commissions["potential"][$req["commision_type"]] += $req["commision"];
        }
    }
    // Comisión total :: fin
    
    
    
    
    // Servicios excluidos :: inicio
    $services = get_services();
    $excluded_services = get_excluded_services($user_id);
    // Servicios excluidos :: fin
    
    
    $info["customers"] = $sales_rep_customers;
    $info["requests"] = $requests;
    $info["commissions"] = $commissions;
    $info["services"] = $services;
    $info["excluded_services"] = $excluded_services;
}
compact("info");