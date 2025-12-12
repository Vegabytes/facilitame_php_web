<?php
/**
 * API: Exportar clientes de asesoría a CSV/Excel
 *
 * GET:
 * - format: csv (default) | excel
 * - type: all | autonomo | empresa | comunidad | asociacion
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
$format = $_GET['format'] ?? 'csv';
$typeFilter = $_GET['type'] ?? 'all';

// Construir query
$whereConditions = ["ca.advisory_id = ?"];
$params = [$advisory_id];

if ($typeFilter !== 'all') {
    $whereConditions[] = "ca.client_type = ?";
    $params[] = $typeFilter;
}

$whereClause = implode(' AND ', $whereConditions);

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.lastname,
        u.email,
        u.phone,
        u.nif_cif,
        u.address,
        u.city,
        u.postal_code,
        u.country,
        ca.client_type,
        ca.client_subtype,
        ca.created_at as fecha_vinculacion,
        (SELECT COUNT(*) FROM advisory_invoices ai WHERE ai.customer_id = u.id AND ai.advisory_id = ca.advisory_id) as total_facturas
    FROM customers_advisories ca
    JOIN users u ON ca.customer_id = u.id
    WHERE $whereClause
    ORDER BY u.name ASC, u.lastname ASC
");
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapeo de tipos
$typeLabels = [
    'autonomo' => 'Autónomo',
    'empresa' => 'Empresa',
    'comunidad' => 'Comunidad',
    'asociacion' => 'Asociación'
];

// Generar CSV
$filename = 'clientes_' . date('Y-m-d_His') . '.csv';

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
    'Nombre',
    'Apellidos',
    'Email',
    'Teléfono',
    'NIF/CIF',
    'Dirección',
    'Ciudad',
    'Código Postal',
    'País',
    'Tipo Cliente',
    'Subtipo',
    'Fecha Vinculación',
    'Total Facturas'
], ';');

// Datos
foreach ($customers as $customer) {
    fputcsv($output, [
        $customer['id'],
        $customer['name'],
        $customer['lastname'],
        $customer['email'],
        $customer['phone'],
        $customer['nif_cif'],
        $customer['address'],
        $customer['city'],
        $customer['postal_code'],
        $customer['country'],
        $typeLabels[$customer['client_type']] ?? $customer['client_type'],
        $customer['client_subtype'],
        $customer['fecha_vinculacion'] ? date('d/m/Y', strtotime($customer['fecha_vinculacion'])) : '',
        $customer['total_facturas']
    ], ';');
}

fclose($output);
exit;
