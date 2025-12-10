<?php
if (!admin() && !comercial() && !asesoria()) {
    header("Location:home");
    exit;
}

// Si es asesoria, solo define $info vacio
if (asesoria()) {
    $info = [];
} else {
    // Codigo para admin/comercial
    global $pdo;

    $requests = request_get_all();
    $active_requests = [];
    foreach ($requests as $r) {
        if (!empty($r["offers"])) {
            foreach ($r["offers"] as $o) {
                if (in_array($r["status_id"], [7, 8])) {
                    $r["active_offer"] = $o;
                    $active_requests[] = $r;
                    break;
                }
            }
        }
    }

    if (comercial()) {
        $sales_rep_requests = get_requests();
        $sales_rep_requests_ids = array_map(function($req) {
            return $req["id"];
        }, $sales_rep_requests);
        $active_requests = array_values(array_filter($active_requests, function($req) use ($sales_rep_requests_ids) {
            return in_array($req["id"], $sales_rep_requests_ids);
        }));
    }

    $category_ids = array_unique(array_column($active_requests, 'category_id'));
    $provider_names = [];
    $category_to_provider = [];

    if (!empty($category_ids)) {
        $in = implode(',', array_map('intval', $category_ids));
        $stmt = $pdo->query("SELECT category_id, provider_id FROM provider_categories WHERE category_id IN ($in)");
        if ($stmt) {
            foreach ($stmt as $row) {
                $category_to_provider[$row['category_id']] = $row['provider_id'];
            }
        }
        
        $provider_ids = array_unique(array_values($category_to_provider));
        if (!empty($provider_ids)) {
            $in = implode(',', array_map('intval', $provider_ids));
            $stmt = $pdo->query("SELECT id, name, lastname FROM users WHERE id IN ($in)");
            if ($stmt) {
                foreach ($stmt as $row) {
                    $provider_names[$row['id']] = trim($row['name'] . ' ' . $row['lastname']);
                }
            }
        }
    }

    foreach ($active_requests as $key => $r) {
        $provider_id = $category_to_provider[$r["category_id"]] ?? null;
        $active_requests[$key]["provider_id"] = $provider_id;
        $active_requests[$key]["provider_name"] = $provider_names[$provider_id] ?? '';
    }

    $commission_types = get_commissions_kp();

    $info = compact("active_requests", "commission_types", "provider_names");
}