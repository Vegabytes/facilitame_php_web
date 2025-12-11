<?php
// controller/api-commissions-paginated.php

if (!admin() && !comercial()) {
    json_response('ko', 'No autorizado', 4031358401);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$offset = ($page - 1) * $limit;

try {
    // Obtener todas las solicitudes con ofertas activas
    $requests = request_get_all();
    $active_requests = [];
    
    foreach ($requests as &$r) {
        foreach ($r["offers"] as $o) {
            if (in_array($r["status_id"], [7, 8])) {
                $r["active_offer"] = $o;
                $active_requests[] = $r;
                break;
            }
        }
    }
    
    // Si es comercial, filtrar
    if (comercial()) {
        $sales_rep_requests = get_requests();
        $sales_rep_requests_ids = array_map(function ($req) {
            return $req["id"];
        }, $sales_rep_requests);
        $active_requests = array_filter($active_requests, function ($req) use ($sales_rep_requests_ids) {
            return in_array($req["id"], $sales_rep_requests_ids);
        });
        $active_requests = array_values($active_requests);
    }
    
    // Añadir provider info
    $category_ids = [];
    foreach ($active_requests as $r) {
        $category_ids[$r["category_id"]] = true;
    }
    $category_ids = array_keys($category_ids);
    
    $provider_ids = [];
    $category_to_provider = [];
    if ($category_ids) {
        $in = implode(',', array_map('intval', $category_ids));
        $stmt = $pdo->query("SELECT category_id, provider_id FROM provider_categories WHERE category_id IN ($in)");
        foreach ($stmt as $row) {
            $category_to_provider[$row['category_id']] = $row['provider_id'];
            $provider_ids[$row['provider_id']] = true;
        }
    }
    
    $provider_names = [];
    if ($provider_ids) {
        $in = implode(',', array_map('intval', array_keys($provider_ids)));
        $stmt = $pdo->query("SELECT id, name, lastname FROM users WHERE id IN ($in)");
        foreach ($stmt as $row) {
            $provider_names[$row['id']] = trim($row['name'] . ' ' . $row['lastname']);
        }
    }
    
    foreach ($active_requests as &$r) {
        $provider_id = $category_to_provider[$r["category_id"]] ?? null;
        $r["provider_id"] = $provider_id;
        $r["provider_name"] = $provider_names[$provider_id] ?? '';
    }
    unset($r);
    
    // Filtrar por año/mes y calcular comisiones
    $filtered_requests = [];
    $admin_total = 0;
    $sales_rep_total = 0;
    
    foreach ($active_requests as $r) {
        $commission_detail = commission_get_detail($r);
        $total_commission = $commission_detail["admin_commission"] + $commission_detail["sales_rep_commission"];
        
        if (intval($total_commission) == 0) continue;

        // Filtrar por fecha de activación
        $activated_at = $r["active_offer"]["activated_at"] ?? null;
        if (empty($activated_at)) continue;

        $activated_date = strtotime($activated_at);
        $activated_year = intval(date('Y', $activated_date));
        $activated_month = intval(date('m', $activated_date));
        
        if ($activated_year != $year || $activated_month != $month) continue;
        
        // Filtrar por búsqueda
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $searchable = strtolower(
                $r["id"] . ' ' .
                ($r["customer_name"] ?? '') . ' ' .
                ($r["category_display"] ?? '') . ' ' .
                ($r["sales_rep"]["sales_rep_name"] ?? '')
            );
            if (strpos($searchable, $searchLower) === false) continue;
        }
        
        $admin_total += $commission_detail["admin_commission"];
        $sales_rep_total += $commission_detail["sales_rep_commission"];
        
        $filtered_requests[] = [
            'id' => $r['id'],
            'customer_name' => $r['customer_name'] ?? '',
            'category_display' => $r['category_display'] ?? '',
            'activated_at' => !empty($r["active_offer"]["activated_at"]) ? date("d/m/Y", strtotime($r["active_offer"]["activated_at"])) : '-',
            'expires_at' => !empty($r["active_offer"]["expires_at"]) ? date("d/m/Y", strtotime($r["active_offer"]["expires_at"])) : '-',
            'total_amount' => $r["active_offer"]["total_amount"] ?? 0,
            'commission_type_id' => $r["active_offer"]["commision_type_id"] ?? null,
            'commission_value' => $r["active_offer"]["commision"] ?? '-',
            'sales_rep_name' => $r["sales_rep"]["sales_rep_name"] ?? '',
            'admin_commission' => $commission_detail["admin_commission"],
            'sales_rep_commission' => $commission_detail["sales_rep_commission"],
            'total_commission' => $total_commission
        ];
    }
    
    $totalRecords = count($filtered_requests);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Paginar
    $paginated = array_slice($filtered_requests, $offset, $limit);
    
    $result = [
        'data' => $paginated,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ],
        'totals' => [
            'admin_total' => $admin_total,
            'sales_rep_total' => $sales_rep_total
        ]
    ];
    
    json_response('ok', '', 9200004001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-commissions-paginated: " . $e->getMessage() . " línea " . $e->getLine());
    json_response('ko', $e->getMessage(), 9500004001);
}