<?php
/**
 * API: Sincronizar estado y datos OCR de factura desde Inmatic
 *
 * POST:
 * - invoice_id: ID de la factura en advisory_invoices
 *
 * Obtiene el estado actual y datos OCR desde Inmatic y actualiza la BD local
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$invoiceId = intval($_POST['invoice_id'] ?? $_GET['invoice_id'] ?? 0);

if (!$invoiceId) {
    json_response("ko", "ID de factura requerido", 400);
}

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

// Verificar plan
$planesConInmatic = ['pro', 'premium', 'enterprise'];
if (!in_array($advisory['plan'], $planesConInmatic)) {
    json_response("ko", "Tu plan no incluye integración con Inmatic", 403);
}

// Obtener el documento de Inmatic
$stmt = $pdo->prepare("
    SELECT aid.*, ai.original_name
    FROM advisory_inmatic_documents aid
    JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
    WHERE aid.advisory_invoice_id = ? AND ai.advisory_id = ?
    ORDER BY aid.id DESC
    LIMIT 1
");
$stmt->execute([$invoiceId, $advisory['id']]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc || empty($doc['inmatic_document_id'])) {
    json_response("ko", "Esta factura no ha sido enviada a Inmatic", 404);
}

try {
    require_once ROOT_DIR . '/bold/classes/InmaticClient.php';
    $client = new InmaticClient($advisory['id']);

    // Obtener datos del documento desde Inmatic
    $inmaticDoc = $client->getDocument($doc['inmatic_document_id']);

    if (!$inmaticDoc) {
        json_response("ko", "No se pudo obtener información de Inmatic", 500);
    }

    // Extraer datos relevantes
    $newStatus = $inmaticDoc['status'] ?? $inmaticDoc['state'] ?? $doc['inmatic_status'];
    $ocrData = $inmaticDoc['ocr_data'] ?? $inmaticDoc['extracted_data'] ?? $inmaticDoc['data'] ?? null;

    // Mapear estado
    $statusMap = [
        'pending' => 'pending',
        'processing' => 'processing',
        'processed' => 'processed',
        'review' => 'review',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'exported' => 'exported',
        'error' => 'error',
        'failed' => 'error'
    ];
    $mappedStatus = $statusMap[strtolower($newStatus)] ?? $newStatus;

    // Actualizar en BD
    $stmt = $pdo->prepare("
        UPDATE advisory_inmatic_documents
        SET inmatic_status = ?,
            ocr_data = ?,
            processed_at = CASE WHEN ? IN ('processed', 'approved', 'exported') THEN NOW() ELSE processed_at END
        WHERE id = ?
    ");
    $stmt->execute([
        $mappedStatus,
        $ocrData ? json_encode($ocrData) : $doc['ocr_data'],
        $mappedStatus,
        $doc['id']
    ]);

    // Si está procesado, marcar factura
    if (in_array($mappedStatus, ['processed', 'approved', 'exported'])) {
        $stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ?");
        $stmt->execute([$invoiceId]);
    }

    // Preparar respuesta con datos OCR formateados
    $ocrFormatted = null;
    if ($ocrData) {
        $ocrFormatted = formatOcrData($ocrData);
    }

    json_response("ok", "Sincronizado correctamente", 200, [
        'invoice_id' => $invoiceId,
        'inmatic_document_id' => $doc['inmatic_document_id'],
        'status' => $mappedStatus,
        'ocr_data' => $ocrData,
        'ocr_formatted' => $ocrFormatted,
        'synced_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    json_response("ko", "Error al sincronizar: " . $e->getMessage(), 500);
}

/**
 * Formatear datos OCR para mostrar en UI
 */
function formatOcrData($ocrData)
{
    if (!is_array($ocrData)) {
        return null;
    }

    // Intentar extraer campos comunes de diferentes formatos de respuesta
    $formatted = [
        'emisor' => null,
        'emisor_cif' => null,
        'receptor' => null,
        'receptor_cif' => null,
        'numero_factura' => null,
        'fecha' => null,
        'base_imponible' => null,
        'iva' => null,
        'total' => null,
        'concepto' => null
    ];

    // Mapeo de posibles nombres de campos
    $fieldMaps = [
        'emisor' => ['issuer_name', 'supplier_name', 'vendor_name', 'emisor', 'proveedor'],
        'emisor_cif' => ['issuer_tax_id', 'supplier_vat', 'vendor_nif', 'cif_emisor', 'nif_proveedor'],
        'receptor' => ['receiver_name', 'customer_name', 'client_name', 'receptor', 'cliente'],
        'receptor_cif' => ['receiver_tax_id', 'customer_vat', 'client_nif', 'cif_receptor', 'nif_cliente'],
        'numero_factura' => ['invoice_number', 'number', 'numero', 'num_factura'],
        'fecha' => ['date', 'invoice_date', 'fecha', 'fecha_factura'],
        'base_imponible' => ['subtotal', 'net_amount', 'base', 'base_imponible'],
        'iva' => ['tax', 'vat', 'tax_amount', 'iva', 'impuesto'],
        'total' => ['total', 'total_amount', 'amount', 'importe'],
        'concepto' => ['description', 'concept', 'concepto', 'detalle']
    ];

    foreach ($fieldMaps as $targetField => $sourceFields) {
        foreach ($sourceFields as $sourceField) {
            if (isset($ocrData[$sourceField]) && !empty($ocrData[$sourceField])) {
                $formatted[$targetField] = $ocrData[$sourceField];
                break;
            }
            // Buscar en nested data
            if (isset($ocrData['data'][$sourceField]) && !empty($ocrData['data'][$sourceField])) {
                $formatted[$targetField] = $ocrData['data'][$sourceField];
                break;
            }
        }
    }

    // Limpiar nulls
    return array_filter($formatted, function ($v) {
        return $v !== null;
    });
}
