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

// Usar la nueva función que soporta comerciales (7) y proveedores (2)
$user_profile = get_user_profile($user_id);

// Si no se encuentra el usuario, redirigir con mensaje
if (empty($user_profile)) {
    header("Location:home?error=user_not_found");
    exit;
}

// Determinar el tipo de usuario
$is_comercial = ($user_profile["role_id"] == 7);
$is_proveedor = ($user_profile["role_id"] == 2);

// Alias para compatibilidad con la vista existente
$sales_rep = $user_profile;

// Variables específicas según el tipo de usuario
$customers = [];
$requests = [];
$commissions = [];
$services = [];
$excluded_services = [];

if ($is_comercial) {
    // Obtener clientes del comercial
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

    $customers = $sales_rep_customers;

    // Solicitudes asociadas al comercial
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

    // Servicios excluidos (solo para comerciales)
    $services = get_services();
    $excluded_services = get_excluded_services($user_id);
}
elseif ($is_proveedor) {
    // Obtener las categorías asignadas al proveedor
    $stmt = $pdo->prepare("SELECT category_id FROM provider_categories WHERE provider_id = :provider_id");
    $stmt->bindValue(":provider_id", $user_id);
    $stmt->execute();
    $category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($category_ids)) {
        // Obtener solicitudes de las categorías del proveedor
        $placeholders = implode(",", array_fill(0, count($category_ids), "?"));
        $query =
            "SELECT r.*, u.name AS customer_name, u.lastname AS customer_lastname, u.email AS customer_email, c.name AS category_name
        FROM requests r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE r.category_id IN ($placeholders)
        AND r.deleted_at IS NULL
        ORDER BY r.created_at DESC
        LIMIT 50";
        $stmt = $pdo->prepare($query);
        $stmt->execute($category_ids);
        $requests = $stmt->fetchAll();
    }
}

$info = compact("sales_rep", "user_profile", "is_comercial", "is_proveedor", "customers", "requests", "commissions", "services", "excluded_services");
