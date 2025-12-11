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

$user = new User();
$sales_rep = get_sales_rep($user_id);

// Si no se encuentra el usuario como comercial, redirigir con mensaje
if (empty($sales_rep)) {
    header("Location:home?error=user_not_found");
    exit;
}

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

// Solicitudes asociadas
$query =
    "SELECT r.*
FROM requests r
JOIN users u ON u.id = r.user_id
JOIN customers_sales_codes cdc ON cdc.customer_id = u.id
JOIN sales_codes codes ON codes.user_id = :sales_rep_id
WHERE 1
AND r.deleted_at IS NULL";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":sales_rep_id", $user_id);
$stmt->execute();
$requests = $stmt->fetchAll();

// Comisión total
$commissions = [];
foreach ($requests as $req)
{
    $type = $req["commision_type"] ?? 'default';
    if (in_array($req["status_id"], [7, 8])) // Activada / Revisión solicitada
    {
        $commissions["solid"][$type] = ($commissions["solid"][$type] ?? 0) + ($req["commision"] ?? 0);
    }
    else
    {
        $commissions["potential"][$type] = ($commissions["potential"][$type] ?? 0) + ($req["commision"] ?? 0);
    }
}

// Servicios excluidos
$services = get_services();
$excluded_services = get_excluded_services($user_id);

// Variables para la vista
$customers = $sales_rep_customers;

$info = compact("sales_rep", "customers", "requests", "commissions", "services", "excluded_services");
