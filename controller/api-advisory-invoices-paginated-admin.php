<?php
/**
 * API Admin: Facturas de una asesoría
 * GET /api/advisory-invoices-paginated-admin?advisory_id=13
 */
header('Content-Type: application/json');

if (!admin()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$advisory_id = isset($_GET['advisory_id']) ? (int)$_GET['advisory_id'] : 0;
if (!$advisory_id) {
    json_response("ko", "advisory_id requerido", 400);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 25;
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $limit;

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

try {
    $params = [$advisory_id];
    $whereConditions = ["ai.advisory_id = ?"];
    
    if (!empty($type) && in_array($type, ['gasto', 'ingreso'])) {
        $whereConditions[] = "ai.type = ?";
        $params[] = $type;
    }
    
    if ($status === '0') {
        $whereConditions[] = "(ai.is_processed = 0 OR ai.is_processed IS NULL)";
    } elseif ($status === '1') {
        $whereConditions[] = "ai.is_processed = 1";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Contar total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM advisory_invoices ai
        WHERE $whereClause
    ";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = (int)$stmt->fetch()['total'];
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    
    // Obtener facturas
    $dataQuery = "
        SELECT 
            ai.id,
            ai.filename,
            ai.original_name,
            ai.file_size,
            ai.type,
            ai.tag,
            ai.month,
            ai.year,
            ai.is_processed,
            ai.created_at,
            CONCAT(u.name, ' ', u.lastname) as customer_name,
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
            'id' => (int)$inv['id'],
            'filename' => $inv['original_name'] ?? $inv['filename'] ?? '',
            'filesize_formatted' => formatFileSize($inv['file_size']),
            'type' => $inv['type'] ?? 'gasto',
            'tag' => $inv['tag'] ?? 'otros',
            'month' => (int)$inv['month'],
            'year' => (int)$inv['year'],
            'is_processed' => (bool)$inv['is_processed'],
            'customer_name' => $inv['customer_name'],
            'nif_cif' => $inv['nif_cif'] ?? '',
            'created_at' => $inv['created_at'] ? date('d/m/Y', strtotime($inv['created_at'])) : '-'
        ];
    }
    
    json_response("ok", "", 200, [
        'data' => $formattedInvoices,
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
    error_log("Error en api-advisory-invoices-paginated-admin: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 500);
}