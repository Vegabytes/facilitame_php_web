<?php
// controller/api-advisory-invoices-paginated.php

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 0;
$offset = ($page - 1) * $limit;

try {
    // Función para formatear tamaño de archivo
    function formatFileSize($bytes) {
        if (!$bytes || $bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    $user_id = (int)USER["id"];
    
    // Obtener ID de la asesoría
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        json_response('ok', '', 200, [
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 0,
                'per_page' => $limit,
                'from' => 0,
                'to' => 0
            ],
            'stats' => [
                'this_month' => 0,
                'pending' => 0,
                'total' => 0,
                'gastos' => 0,
                'ingresos' => 0
            ]
        ]);
        return;
    }
    
    $advisory_id = (int)$advisory["id"];
    
    $params = [$advisory_id];
    $whereConditions = ["ai.advisory_id = ?"];
    
    // Filtro por búsqueda
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) LIKE ?
            OR u.nif_cif LIKE ?
            OR ai.filename LIKE ?
            OR ai.original_name LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Filtro por etiqueta
    if (!empty($tag)) {
        $whereConditions[] = "ai.tag = ?";
        $params[] = $tag;
    }
    
    // Filtro por estado
    if ($status === 'pending') {
        $whereConditions[] = "(ai.is_processed = 0 OR ai.is_processed IS NULL)";
    } elseif ($status === 'processed') {
        $whereConditions[] = "ai.is_processed = 1";
    }
    
    // Filtro por tipo (gasto/ingreso)
    if (!empty($type) && in_array($type, ['gasto', 'ingreso'])) {
        $whereConditions[] = "ai.type = ?";
        $params[] = $type;
    }

    // Filtro por mes
    if ($month > 0 && $month <= 12) {
        $whereConditions[] = "ai.month = ?";
        $params[] = $month;
    }

    // Filtro por trimestre
    if ($quarter > 0 && $quarter <= 4) {
        $quarterMonths = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];
        $months = $quarterMonths[$quarter];
        $whereConditions[] = "ai.month IN (?, ?, ?)";
        $params[] = $months[0];
        $params[] = $months[1];
        $params[] = $months[2];
    }

    $whereClause = implode(' AND ', $whereConditions);
    
    // Obtener estadísticas globales (sin filtros de búsqueda/tag/status/type)
    $current_month = date('n');
    $current_year = date('Y');
    
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_processed = 0 OR is_processed IS NULL THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN month = ? AND year = ? THEN 1 ELSE 0 END) as this_month,
            SUM(CASE WHEN type = 'gasto' OR type IS NULL THEN 1 ELSE 0 END) as gastos,
            SUM(CASE WHEN type = 'ingreso' THEN 1 ELSE 0 END) as ingresos
        FROM advisory_invoices
        WHERE advisory_id = ?
    ";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute([$current_month, $current_year, $advisory_id]);
    $stats = $statsStmt->fetch();
    
    // Contar total con filtros
    $countQuery = "
        SELECT COUNT(*) as total
        FROM advisory_invoices ai
        INNER JOIN users u ON ai.customer_id = u.id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalRecords = intval($row['total']);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener datos paginados
    $dataQuery = "
        SELECT 
            ai.id,
            ai.customer_id,
            ai.filename,
            ai.original_name,
            ai.file_size,
            ai.tag,
            ai.type,
            ai.month,
            ai.year,
            ai.is_processed,
            ai.created_at,
            u.name as customer_name,
            u.lastname as customer_lastname,
            u.nif_cif
        FROM advisory_invoices ai
        INNER JOIN users u ON ai.customer_id = u.id
        WHERE $whereClause
        ORDER BY ai.created_at DESC
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
    $invoices = $stmt->fetchAll();
    
    $formattedInvoices = [];
    foreach ($invoices as $inv) {
        $formattedInvoices[] = [
            'id' => $inv['id'],
            'customer_id' => $inv['customer_id'],
            'customer_name' => trim(($inv['customer_name'] ?? '') . ' ' . ($inv['customer_lastname'] ?? '')),
            'nif_cif' => $inv['nif_cif'] ?? '',
            'filename' => $inv['original_name'] ?? $inv['filename'] ?? '',
            'filesize' => floatval($inv['file_size']),
            'filesize_formatted' => formatFileSize($inv['file_size']),
            'url' => $inv['filename'] ?? '',
            'tag' => $inv['tag'] ?? 'otros',
            'type' => $inv['type'] ?? 'gasto',
            'month' => intval($inv['month']),
            'year' => intval($inv['year']),
            'is_processed' => (bool)$inv['is_processed'],
            'created_at' => $inv['created_at'],
            'created_at_formatted' => date('d M Y', strtotime($inv['created_at']))
        ];
    }
    
    $result = [
        'data' => $formattedInvoices,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ],
        'stats' => [
            'this_month' => intval($stats['this_month'] ?? 0),
            'pending' => intval($stats['pending'] ?? 0),
            'total' => intval($stats['total'] ?? 0),
            'gastos' => intval($stats['gastos'] ?? 0),
            'ingresos' => intval($stats['ingresos'] ?? 0)
        ]
    ];
    
    json_response("ok", "", 200, $result);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-invoices-paginated: " . $e->getMessage() . " línea " . $e->getLine());
    json_response("ko", $e->getMessage(), 500);
}