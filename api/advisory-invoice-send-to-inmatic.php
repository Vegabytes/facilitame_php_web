<?php
/**
 * API: Enviar factura a Inmatic
 *
 * POST:
 * - invoice_id: ID de la factura en advisory_invoices
 *
 * Respuesta:
 * - inmatic_document_id: ID del documento en Inmatic
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$invoiceId = intval($_POST['invoice_id'] ?? 0);

if (!$invoiceId) {
    json_response("ko", "ID de factura requerido", 400);
}

// Obtener advisory_id y verificar plan
$stmt = $pdo->prepare("SELECT id, plan, inmatic_trial FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

// Verificar plan o modo prueba
$planesConInmatic = ['pro', 'premium', 'enterprise'];
$hasAccess = in_array($advisory['plan'], $planesConInmatic) || $advisory['inmatic_trial'];
if (!$hasAccess) {
    json_response("ko", "Tu plan no incluye integración con Inmatic", 403);
}

// Obtener factura
$stmt = $pdo->prepare("
    SELECT ai.*, u.name as customer_name, u.nif_cif as customer_nif
    FROM advisory_invoices ai
    LEFT JOIN users u ON ai.customer_id = u.id
    WHERE ai.id = ? AND ai.advisory_id = ?
");
$stmt->execute([$invoiceId, $advisory['id']]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    json_response("ko", "Factura no encontrada", 404);
}

// Verificar que no se haya enviado ya exitosamente
$stmt = $pdo->prepare("
    SELECT id, inmatic_document_id, inmatic_status
    FROM advisory_inmatic_documents
    WHERE advisory_invoice_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$invoiceId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing && !in_array($existing['inmatic_status'], ['error', 'rejected'])) {
    json_response("ko", "Esta factura ya fue enviada a Inmatic (Estado: " . $existing['inmatic_status'] . ")", 400, [
        'inmatic_document_id' => $existing['inmatic_document_id'],
        'status' => $existing['inmatic_status']
    ]);
}

// Construir ruta del archivo
$filePath = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $invoice['filename'];

if (!file_exists($filePath)) {
    json_response("ko", "Archivo de factura no encontrado en el servidor", 404);
}

try {
    require_once ROOT_DIR . '/bold/classes/InmaticClient.php';
    $client = new InmaticClient($advisory['id']);

    // Determinar tipo de documento segun el tipo de factura
    $documentType = ($invoice['type'] === 'ingreso') ? 'invoice' : 'receipt';

    // Metadata adicional para Inmatic
    $metadata = [
        'external_id' => 'facilitame_invoice_' . $invoiceId,
        'description' => $invoice['notes'] ?? '',
        'tags' => $invoice['tag'] ?? ''
    ];

    // Agregar info del cliente si existe
    if ($invoice['customer_name']) {
        $metadata['customer_name'] = $invoice['customer_name'];
    }
    if ($invoice['customer_nif']) {
        $metadata['customer_nif'] = $invoice['customer_nif'];
    }

    // Agregar fecha si existe
    if ($invoice['month'] && $invoice['year']) {
        $metadata['period'] = $invoice['year'] . '-' . str_pad($invoice['month'], 2, '0', STR_PAD_LEFT);
    }

    // Enviar a Inmatic
    $result = $client->uploadDocument(
        $filePath,
        $invoice['original_name'],
        $documentType,
        $metadata
    );

    // Verificar respuesta
    if (!isset($result['id']) && !isset($result['data']['id'])) {
        throw new Exception("Respuesta inesperada de Inmatic: no se recibió ID de documento");
    }

    $inmaticDocId = $result['id'] ?? $result['data']['id'];

    // Guardar referencia en BD
    if ($existing) {
        // Actualizar registro existente (reintento)
        $stmt = $pdo->prepare("
            UPDATE advisory_inmatic_documents
            SET inmatic_document_id = ?, inmatic_status = 'pending',
                sent_at = NOW(), error_message = NULL, processed_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$inmaticDocId, $existing['id']]);
    } else {
        // Crear nuevo registro
        $stmt = $pdo->prepare("
            INSERT INTO advisory_inmatic_documents
            (advisory_invoice_id, inmatic_document_id, inmatic_status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$invoiceId, $inmaticDocId]);
    }

    json_response("ok", "Factura enviada a Inmatic correctamente", 200, [
        'inmatic_document_id' => $inmaticDocId,
        'status' => 'pending'
    ]);

} catch (Exception $e) {
    // Guardar error en BD
    $errorMsg = $e->getMessage();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE advisory_inmatic_documents
            SET inmatic_status = 'error', error_message = ?, sent_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$errorMsg, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO advisory_inmatic_documents
            (advisory_invoice_id, inmatic_document_id, inmatic_status, error_message)
            VALUES (?, '', 'error', ?)
        ");
        $stmt->execute([$invoiceId, $errorMsg]);
    }

    json_response("ko", "Error al enviar a Inmatic: " . $errorMsg, 500);
}
