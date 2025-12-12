<?php
/**
 * API: Enviar multiples facturas a Inmatic
 *
 * POST:
 * - invoice_ids: Array de IDs de facturas
 * - O sin parametros para enviar todas las pendientes
 *
 * Respuesta:
 * - sent: Cantidad enviadas
 * - errors: Array de errores
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id y verificar plan
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

// Obtener IDs de facturas
$invoiceIds = $_POST['invoice_ids'] ?? [];
if (is_string($invoiceIds)) {
    $invoiceIds = json_decode($invoiceIds, true) ?: [];
}

// Si no se especifican IDs, obtener todas las pendientes
if (empty($invoiceIds)) {
    $stmt = $pdo->prepare("
        SELECT ai.id
        FROM advisory_invoices ai
        LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
        WHERE ai.advisory_id = ?
          AND (aid.id IS NULL OR aid.inmatic_status IN ('error', 'rejected'))
        ORDER BY ai.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$advisory['id']]);
    $invoiceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($invoiceIds)) {
    json_response("ok", "No hay facturas pendientes de enviar", 200, [
        'sent' => 0,
        'errors' => []
    ]);
}

// Limitar a 50 por ejecucion
$invoiceIds = array_slice($invoiceIds, 0, 50);

require_once ROOT_DIR . '/bold/classes/InmaticClient.php';

$sent = 0;
$errors = [];

try {
    $client = new InmaticClient($advisory['id']);
} catch (Exception $e) {
    json_response("ko", "Error de configuración Inmatic: " . $e->getMessage(), 500);
}

foreach ($invoiceIds as $invoiceId) {
    $invoiceId = intval($invoiceId);

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
        $errors[] = ['invoice_id' => $invoiceId, 'error' => 'Factura no encontrada'];
        continue;
    }

    // Verificar archivo
    $filePath = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $invoice['filename'];
    if (!file_exists($filePath)) {
        $errors[] = ['invoice_id' => $invoiceId, 'error' => 'Archivo no encontrado'];
        continue;
    }

    // Verificar si ya fue enviada exitosamente
    $stmt = $pdo->prepare("
        SELECT inmatic_status FROM advisory_inmatic_documents
        WHERE advisory_invoice_id = ? AND inmatic_status NOT IN ('error', 'rejected')
        LIMIT 1
    ");
    $stmt->execute([$invoiceId]);
    if ($stmt->fetch()) {
        // Ya enviada, saltar
        continue;
    }

    try {
        $documentType = ($invoice['type'] === 'ingreso') ? 'invoice' : 'receipt';

        $metadata = [
            'external_id' => 'facilitame_invoice_' . $invoiceId,
            'description' => $invoice['notes'] ?? ''
        ];

        if ($invoice['customer_name']) {
            $metadata['customer_name'] = $invoice['customer_name'];
        }

        $result = $client->uploadDocument(
            $filePath,
            $invoice['original_name'],
            $documentType,
            $metadata
        );

        $inmaticDocId = $result['id'] ?? $result['data']['id'] ?? null;

        if (!$inmaticDocId) {
            throw new Exception("No se recibió ID de Inmatic");
        }

        // Guardar en BD
        $stmt = $pdo->prepare("
            INSERT INTO advisory_inmatic_documents
            (advisory_invoice_id, inmatic_document_id, inmatic_status)
            VALUES (?, ?, 'pending')
            ON DUPLICATE KEY UPDATE
            inmatic_document_id = VALUES(inmatic_document_id),
            inmatic_status = 'pending',
            sent_at = NOW(),
            error_message = NULL
        ");
        $stmt->execute([$invoiceId, $inmaticDocId]);

        $sent++;

        // Pequeña pausa para no saturar
        usleep(300000); // 300ms

    } catch (Exception $e) {
        $errors[] = [
            'invoice_id' => $invoiceId,
            'filename' => $invoice['original_name'],
            'error' => $e->getMessage()
        ];

        // Guardar error
        $stmt = $pdo->prepare("
            INSERT INTO advisory_inmatic_documents
            (advisory_invoice_id, inmatic_document_id, inmatic_status, error_message)
            VALUES (?, '', 'error', ?)
            ON DUPLICATE KEY UPDATE
            inmatic_status = 'error',
            error_message = VALUES(error_message),
            sent_at = NOW()
        ");
        $stmt->execute([$invoiceId, $e->getMessage()]);
    }
}

$message = "Enviadas $sent facturas a Inmatic";
if (count($errors) > 0) {
    $message .= ". " . count($errors) . " errores.";
}

json_response("ok", $message, 200, [
    'sent' => $sent,
    'total_requested' => count($invoiceIds),
    'errors' => $errors
]);
