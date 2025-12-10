<?php
/**
 * API: Facturas paginadas para cliente
 * Endpoint: /api/invoices-paginated-customer
 * 
 * Agrupa por servicio (request) con facturas desplegables
 */

if (!cliente()) {
    json_response("ko", "No autorizado", 4031359101);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    $user_id = (int) USER["id"];
    
    $params = [$user_id];
    $whereConditions = ["req.user_id = ?", "req.status_id = 7", "req.deleted_at IS NULL"];
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(cat.name LIKE ? OR req.id LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = intval($stmt->fetch()['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos paginados
    $dataQuery = "
        SELECT 
            req.id,
            req.category_id,
            req.status_id,
            cat.name AS category_name,
            sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE $whereClause
        ORDER BY req.request_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    // Verificar facturas del mes actual
    $current_year = date("Y");
    $current_month = date("m");
    
    $formattedRequests = [];
    foreach ($requests as $req) {
        // Comprobar si tiene factura del mes actual
        $checkQuery = "
            SELECT id FROM invoices 
            WHERE request_id = ? 
            AND YEAR(invoice_date) = ? 
            AND MONTH(invoice_date) = ?
            AND deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute([$req['id'], $current_year, $current_month]);
        $hasCurrentMonthInvoice = $stmt->fetch() !== false;
        
        // Obtener todas las facturas de este servicio
        $invoicesQuery = "
            SELECT 
                id,
                filename,
                description,
                type,
                invoice_date,
                created_at
            FROM invoices
            WHERE request_id = ?
            AND deleted_at IS NULL
            ORDER BY invoice_date DESC, created_at DESC
        ";
        $stmtInv = $pdo->prepare($invoicesQuery);
        $stmtInv->execute([$req['id']]);
        $invoices = $stmtInv->fetchAll();
        
        // Formatear facturas
        $formattedInvoices = [];
        foreach ($invoices as $inv) {
            $formattedInvoices[] = [
                'id' => $inv['id'],
                'filename' => $inv['filename'],
                'description' => $inv['description'] ?? '',
                'type' => $inv['type'] ?? '',
                'invoice_date' => $inv['invoice_date'],
                'invoice_date_formatted' => $inv['invoice_date'] ? date('d/m/Y', strtotime($inv['invoice_date'])) : '-',
                'created_at' => $inv['created_at']
            ];
        }
        
        // Última factura
        $lastInvoiceDate = null;
        if (!empty($invoices)) {
            $lastInvoiceDate = $invoices[0]['invoice_date'];
        }
        
        $formattedRequests[] = [
            'id' => $req['id'],
            'category_name' => $req['category_name'] ?? '',
            'status' => $req['status'] ?? '',
            'status_id' => $req['status_id'],
            'verified' => $hasCurrentMonthInvoice ? "1" : "0",
            'details' => function_exists('get_request_category_info') ? get_request_category_info($req) : '',
            'total_invoices' => count($formattedInvoices),
            'last_invoice_date' => $lastInvoiceDate,
            'last_invoice_formatted' => $lastInvoiceDate ? date('d/m/Y', strtotime($lastInvoiceDate)) : null,
            'invoices' => $formattedInvoices
        ];
    }
    
    $result = [
        'data' => $formattedRequests,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ];
    
    json_response("ok", "", 9200011001, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-invoices-paginated-customer: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500011001);
}