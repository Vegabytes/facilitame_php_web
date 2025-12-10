<?php
/**
 * API: Marcar factura como procesada
 * POST /api-advisory-mark-invoice-processed
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener asesoría
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

// Leer datos - soportar JSON body y POST tradicional
$input = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $input = json_decode($rawInput, true) ?? [];
}

// Aceptar tanto 'id' como 'invoice_id'
$invoice_id = intval($input['id'] ?? $input['invoice_id'] ?? $_POST['id'] ?? $_POST['invoice_id'] ?? 0);

if (!$invoice_id) {
    json_response("ko", "ID de factura inválido", 400);
}

$stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ? AND advisory_id = ?");
$stmt->execute([$invoice_id, $advisory['id']]);

if ($stmt->rowCount() > 0) {
    json_response("ok", "Factura marcada como procesada", 200);
} else {
    // Verificar si existe pero ya está procesada
    $check = $pdo->prepare("SELECT is_processed FROM advisory_invoices WHERE id = ? AND advisory_id = ?");
    $check->execute([$invoice_id, $advisory['id']]);
    $existing = $check->fetch();
    
    if ($existing && $existing['is_processed']) {
        json_response("ok", "La factura ya estaba procesada", 200);
    } else {
        json_response("ko", "Factura no encontrada", 404);
    }
}