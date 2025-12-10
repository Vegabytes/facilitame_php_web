<?php
global $pdo;

$requests = get_requests();
$customers = get_customers();

$info = compact("requests", "customers");

if (admin()) {
    $log = get_log();
    $info = array_merge($info, compact("log"));
} elseif (comercial()) {
    $comercial_id = (int)USER["id"];
    $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = ?");
    $stmt->execute([$comercial_id]);
    $sales_code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($sales_code_ids)) {
        $log = [];
    } else {
        $in_codes = implode(",", array_map("intval", $sales_code_ids));
        $stmt = $pdo->query("SELECT DISTINCT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($in_codes)");
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($customer_ids)) {
            $log = [];
        } else {
            $in_customers = implode(",", array_map("intval", $customer_ids));
            $query = "
                SELECT log.*, 
                       CONCAT(users.name, ' ', IFNULL(users.lastname, '')) AS triggered_by_name, 
                       users.email AS triggered_by_email
                FROM log
                JOIN users ON users.id = log.triggered_by
                JOIN requests ON requests.id = log.target_id
                WHERE requests.user_id IN ($in_customers)
                ORDER BY log.created_at DESC
            ";
            $log = $pdo->query($query)->fetchAll();
        }
    }

    $info = array_merge($info, compact("log"));
} elseif (proveedor()) {
    $provider_id = (int)USER["id"];
    $stmt = $pdo->prepare("SELECT category_id FROM provider_categories WHERE provider_id = ?");
    $stmt->execute([$provider_id]);
    $category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($category_ids)) {
        $log = [];
    } else {
        // Limpiar array (s¨®lo enteros ¨²nicos)
        $category_ids = array_unique(array_filter(array_map("intval", $category_ids)));
        $in_categories = implode(",", $category_ids);

        $stmt = $pdo->query("SELECT id FROM requests WHERE category_id IN ($in_categories)");
        $request_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($request_ids)) {
            $log = [];
        } else {
            $request_ids = array_unique(array_filter(array_map("intval", $request_ids)));
            $in_requests = implode(",", $request_ids);

            // Si quieres sacar m¨¢s datos de user, une a la tabla users
            $query = "
                SELECT log.*,
                       CONCAT(users.name, ' ', IFNULL(users.lastname, '')) AS triggered_by_name, 
                       users.email AS triggered_by_email
                FROM log
                JOIN users ON users.id = log.triggered_by
                WHERE log.target_type IN ('request','message','message_provider','offer','incident','invoice','notification','document')
 AND log.target_id IN ($in_requests)
                ORDER BY log.created_at DESC
            ";
            $log = $pdo->query($query)->fetchAll();
        }
    }
    $info = array_merge($info, compact("log"));
} else {
    exit("1837137620");
}
