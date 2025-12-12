<?php
/**
 * API: Obtener estado Inmatic de facturas
 *
 * GET/POST:
 * - invoice_id (opcional): ID de factura especifica
 * - page, limit: Para paginacion
 * - status: Filtrar por estado inmatic (pending, processed, error, etc)
 *
 * Respuesta:
 * - Lista de facturas con su estado en Inmatic
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

// Parametros
$invoiceId = intval($_REQUEST['invoice_id'] ?? 0);
$page = max(1, intval($_REQUEST['page'] ?? 1));
$limit = min(100, max(1, intval($_REQUEST['limit'] ?? 20)));
$statusFilter = trim($_REQUEST['status'] ?? '');
$offset = ($page - 1) * $limit;

// Si se pide una factura especifica
if ($invoiceId) {
    $stmt = $pdo->prepare("
        SELECT
            ai.id as invoice_id,
            ai.original_name,
            ai.type,
            ai.tag,
            ai.month,
            ai.year,
            ai.is_processed,
            ai.created_at as invoice_created_at,
            aid.id as inmatic_record_id,
            aid.inmatic_document_id,
            aid.inmatic_status,
            aid.sent_at,
            aid.processed_at,
            aid.ocr_data,
            aid.error_message,
            u.name as customer_name
        FROM advisory_invoices ai
        LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
        LEFT JOIN users u ON ai.customer_id = u.id
        WHERE ai.id = ? AND ai.advisory_id = ?
    ");
    $stmt->execute([$invoiceId, $advisory['id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        json_response("ko", "Factura no encontrada", 404);
    }

    // Decodificar OCR data si existe
    if ($invoice['ocr_data']) {
        $invoice['ocr_data'] = json_decode($invoice['ocr_data'], true);
    }

    json_response("ok", "", 200, $invoice);
}

// Listado paginado
$whereConditions = ["ai.advisory_id = ?"];
$params = [$advisory['id']];

// Filtro por estado
if ($statusFilter) {
    if ($statusFilter === 'not_sent') {
        // Facturas no enviadas a Inmatic
        $whereConditions[] = "aid.id IS NULL";
    } else {
        $whereConditions[] = "aid.inmatic_status = ?";
        $params[] = $statusFilter;
    }
}

$whereClause = implode(' AND ', $whereConditions);

// Contar total
$countSql = "
    SELECT COUNT(DISTINCT ai.id)
    FROM advisory_invoices ai
    LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
    WHERE $whereClause
";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// Obtener registros
$sql = "
    SELECT
        ai.id as invoice_id,
        ai.original_name,
        ai.type,
        ai.tag,
        ai.month,
        ai.year,
        ai.is_processed,
        ai.created_at as invoice_created_at,
        aid.inmatic_document_id,
        aid.inmatic_status,
        aid.sent_at,
        aid.processed_at,
        aid.error_message,
        u.name as customer_name
    FROM advisory_invoices ai
    LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
    LEFT JOIN users u ON ai.customer_id = u.id
    WHERE $whereClause
    ORDER BY ai.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadisticas
$statsSql = "
    SELECT
        COUNT(DISTINCT ai.id) as total_invoices,
        SUM(CASE WHEN aid.id IS NULL THEN 1 ELSE 0 END) as not_sent,
        SUM(CASE WHEN aid.inmatic_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN aid.inmatic_status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN aid.inmatic_status IN ('processed', 'approved', 'exported') THEN 1 ELSE 0 END) as processed,
        SUM(CASE WHEN aid.inmatic_status = 'error' THEN 1 ELSE 0 END) as error,
        SUM(CASE WHEN aid.inmatic_status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM advisory_invoices ai
    LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
    WHERE ai.advisory_id = ?
";
$stmt = $pdo->prepare($statsSql);
$stmt->execute([$advisory['id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

json_response("ok", "", 200, [
    'invoices' => $invoices,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'pages' => ceil($total / $limit)
    ],
    'stats' => $stats
]);
