<?php
// controller/api-invoices-paginated-provider.php

if (!proveedor()) {
    json_response("ko", "No autorizado", 4031358801);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Usar categorías ya disponibles en USER
    $in_categories = USER["categories"];
    
    if (empty($in_categories)) {
        json_response('ok', '', 9200008001, [
            'data' => [],
            'pagination' => [
                'current_page' => 1, 'total_pages' => 1, 'total_records' => 0,
                'per_page' => $limit, 'from' => 0, 'to' => 0
            ]
        ]);
        return;
    }
    
    $current_year = date("Y");
    $current_month = date("m");
    
    // Construir WHERE para búsqueda
    $searchWhere = "";
    $searchParams = [];
    
    if (!empty($search)) {
        $searchWhere = "AND (CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE ? OR u.email LIKE ?)";
        $searchParams = ["%{$search}%", "%{$search}%"];
    }
    
    // =============================================
    // QUERY 1: Count + Clientes paginados en una sola query con SQL_CALC_FOUND_ROWS
    // =============================================
    $customersQuery = "
        SELECT SQL_CALC_FOUND_ROWS
            u.id,
            u.name,
            u.lastname,
            u.email,
            u.allow_invoice_access
        FROM users u
        INNER JOIN requests r ON r.user_id = u.id
        WHERE r.category_id IN ($in_categories)
          AND r.status_id = 7
          AND r.deleted_at IS NULL
          AND u.deleted_at IS NULL
          $searchWhere
        GROUP BY u.id
        ORDER BY u.lastname ASC, u.name ASC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($customersQuery);
    $paramIndex = 1;
    foreach ($searchParams as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    // Obtener total sin LIMIT
    $totalRecords = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    if (empty($customers)) {
        json_response('ok', '', 9200008001, [
            'data' => [],
            'pagination' => [
                'current_page' => $page, 'total_pages' => $totalPages, 'total_records' => $totalRecords,
                'per_page' => $limit, 'from' => 0, 'to' => 0
            ]
        ]);
        return;
    }
    
    // =============================================
    // QUERY 2: Servicios de estos clientes con última factura
    // =============================================
    $customer_ids = implode(",", array_map("intval", array_column($customers, 'id')));
    
    $requestsQuery = "
        SELECT
            req.id,
            req.user_id,
            sta.status_name,
            cat.name AS category_name,
            MAX(inv.invoice_date) AS last_invoice
        FROM requests req
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN invoices inv ON inv.request_id = req.id
        WHERE req.user_id IN ($customer_ids)
          AND req.status_id = 7
          AND req.category_id IN ($in_categories)
          AND req.deleted_at IS NULL
        GROUP BY req.id, req.user_id, sta.status_name, cat.name
    ";
    
    $stmt = $pdo->query($requestsQuery);
    $allRequests = $stmt->fetchAll();
    
    // Organizar por cliente
    $requestsByCustomer = [];
    foreach ($allRequests as $req) {
        $requestsByCustomer[$req['user_id']][] = $req;
    }
    
    // Construir respuesta
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $customerRequests = $requestsByCustomer[$customer['id']] ?? [];
        $pending = 0;
        $formattedRequests = [];
        
        foreach ($customerRequests as $req) {
            $lastInvoice = $req['last_invoice'] ?? '1970-01-01';
            $verified = "0";
            
            if ($lastInvoice && $lastInvoice !== '1970-01-01') {
                $lastYear = date("Y", strtotime($lastInvoice));
                $lastMonth = date("m", strtotime($lastInvoice));
                if ($current_year == $lastYear && $current_month == $lastMonth) {
                    $verified = "1";
                }
            }
            
            if ($verified === "0") $pending++;
            
            $formattedRequests[] = [
                'id' => $req['id'],
                'category_name' => $req['category_name'] ?? '',
                'status_name' => $req['status_name'] ?? '',
                'last_invoice' => $lastInvoice,
                'verified' => $verified
            ];
        }
        
        $customerVerified = $pending === 0 && count($formattedRequests) > 0 ? "1" : "0";
        
        $formattedCustomers[] = [
            'id' => $customer['id'],
            'name' => $customer['name'] ?? '',
            'lastname' => $customer['lastname'] ?? '',
            'email' => $customer['email'] ?? '',
            'verified' => $customerVerified,
            'allow_invoice_access' => $customer['allow_invoice_access'] ?? '0',
            'requests' => $formattedRequests,
            'total_requests' => count($formattedRequests),
            'pending_requests' => $pending
        ];
    }
    
    json_response("ok", "", 9200008001, [
        'data' => $formattedCustomers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-invoices-paginated-provider: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500008001);
}