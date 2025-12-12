<?php
/**
 * API: Exportar facturas de asesoría a CSV
 *
 * GET:
 * - type: all | gasto | ingreso
 * - status: all | processed | pending
 * - customer_id: ID de cliente específico
 * - month: mes (1-12)
 * - year: año
 * - quarter: trimestre (1-4)
 * - date_from: fecha inicio (Y-m-d)
 * - date_to: fecha fin (Y-m-d)
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, razon_social FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

$advisory_id = $advisory['id'];

// Filtros
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$customerId = intval($_GET['customer_id'] ?? 0);
$month = intval($_GET['month'] ?? 0);
$year = intval($_GET['year'] ?? 0);
$quarter = intval($_GET['quarter'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Construir query
$whereConditions = ["ai.advisory_id = ?"];
$params = [$advisory_id];

if ($typeFilter !== 'all') {
    $whereConditions[] = "ai.type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter === 'processed') {
    $whereConditions[] = "ai.is_processed = 1";
} elseif ($statusFilter === 'pending') {
    $whereConditions[] = "ai.is_processed = 0";
}

if ($customerId > 0) {
    $whereConditions[] = "ai.customer_id = ?";
    $params[] = $customerId;
}

if ($month > 0 && $month <= 12) {
    $whereConditions[] = "ai.month = ?";
    $params[] = $month;
}

if ($year > 0) {
    $whereConditions[] = "ai.year = ?";
    $params[] = $year;
}

if ($quarter > 0 && $quarter <= 4) {
    $whereConditions[] = "ai.quarter = ?";
    $params[] = $quarter;
}

if ($dateFrom) {
    $whereConditions[] = "DATE(ai.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(ai.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $whereConditions);

$stmt = $pdo->prepare("
    SELECT
        ai.id,
        ai.original_name,
        ai.type,
        ai.tag,
        ai.notes,
        ai.month,
        ai.year,
        ai.quarter,
        ai.is_processed,
        ai.created_at,
        u.name as customer_name,
        u.lastname as customer_lastname,
        u.nif_cif as customer_nif,
        aid.inmatic_status,
        aid.ocr_data
    FROM advisory_invoices ai
    LEFT JOIN users u ON ai.customer_id = u.id
    LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
    WHERE $whereClause
    ORDER BY ai.created_at DESC
");
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapeos
$typeLabels = [
    'gasto' => 'Gasto',
    'ingreso' => 'Ingreso'
];

$tagLabels = [
    'restaurante' => 'Restaurante',
    'gasolina' => 'Gasolina',
    'proveedores' => 'Proveedores',
    'material_oficina' => 'Material oficina',
    'viajes' => 'Viajes',
    'servicios' => 'Servicios',
    'otros' => 'Otros'
];

$statusLabels = [
    'pending' => 'Pendiente',
    'processing' => 'Procesando',
    'processed' => 'Procesada',
    'approved' => 'Aprobada',
    'exported' => 'Exportada',
    'error' => 'Error'
];

// Generar nombre de archivo
$filenameParts = ['facturas'];
if ($typeFilter !== 'all') $filenameParts[] = $typeFilter;
if ($year > 0) $filenameParts[] = $year;
if ($quarter > 0) $filenameParts[] = 'T' . $quarter;
if ($month > 0) $filenameParts[] = 'M' . str_pad($month, 2, '0', STR_PAD_LEFT);
$filenameParts[] = date('Ymd_His');
$filename = implode('_', $filenameParts) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras
fputcsv($output, [
    'ID',
    'Archivo',
    'Tipo',
    'Etiqueta',
    'Cliente',
    'NIF Cliente',
    'Mes',
    'Año',
    'Trimestre',
    'Procesada',
    'Estado Inmatic',
    'Emisor OCR',
    'CIF OCR',
    'Total OCR',
    'Fecha OCR',
    'Notas',
    'Fecha Subida'
], ';');

// Datos
foreach ($invoices as $inv) {
    // Extraer datos OCR si existen
    $ocrEmisor = '';
    $ocrCif = '';
    $ocrTotal = '';
    $ocrFecha = '';

    if ($inv['ocr_data']) {
        $ocr = json_decode($inv['ocr_data'], true);
        if ($ocr) {
            $ocrEmisor = $ocr['issuer_name'] ?? $ocr['supplier_name'] ?? $ocr['emisor'] ?? '';
            $ocrCif = $ocr['issuer_tax_id'] ?? $ocr['supplier_vat'] ?? $ocr['cif_emisor'] ?? '';
            $ocrTotal = $ocr['total'] ?? $ocr['total_amount'] ?? '';
            $ocrFecha = $ocr['date'] ?? $ocr['invoice_date'] ?? $ocr['fecha'] ?? '';
        }
    }

    fputcsv($output, [
        $inv['id'],
        $inv['original_name'],
        $typeLabels[$inv['type']] ?? $inv['type'],
        $tagLabels[$inv['tag']] ?? $inv['tag'],
        trim($inv['customer_name'] . ' ' . $inv['customer_lastname']),
        $inv['customer_nif'],
        $inv['month'],
        $inv['year'],
        $inv['quarter'],
        $inv['is_processed'] ? 'Sí' : 'No',
        $statusLabels[$inv['inmatic_status']] ?? $inv['inmatic_status'] ?? 'No enviada',
        $ocrEmisor,
        $ocrCif,
        $ocrTotal ? number_format((float)$ocrTotal, 2, ',', '.') : '',
        $ocrFecha,
        $inv['notes'],
        date('d/m/Y H:i', strtotime($inv['created_at']))
    ], ';');
}

fclose($output);
exit;
