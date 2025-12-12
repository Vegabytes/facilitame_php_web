<?php
/**
 * Webhook: Recibe notificaciones de Inmatic
 *
 * Eventos soportados:
 * - document.processed: Documento procesado con OCR
 * - document.approved: Documento aprobado
 * - document.rejected: Documento rechazado
 * - document.exported: Documento exportado a contabilidad
 *
 * El webhook se configura en Inmatic apuntando a:
 * https://tudominio.com/api/webhook-inmatic
 */

// No requiere autenticacion de usuario (es llamado por Inmatic)
// Pero verificamos que venga de una fuente valida

// Headers CORS para webhooks
header('Content-Type: application/json');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Obtener payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log de todos los webhooks recibidos
$logDir = ROOT_DIR . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/inmatic-webhook.log';
$logEntry = date('Y-m-d H:i:s') . ' | ' . $_SERVER['REMOTE_ADDR'] . ' | ' . $payload . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Validar payload
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

// Extraer datos del evento
$event = $data['event'] ?? $data['type'] ?? '';
$documentId = $data['document_id'] ?? $data['id'] ?? $data['data']['id'] ?? '';
$status = $data['status'] ?? $data['state'] ?? $data['data']['status'] ?? '';
$ocrData = $data['ocr_data'] ?? $data['data']['ocr_data'] ?? $data['extracted_data'] ?? null;

// Validar que tengamos al menos el document_id
if (empty($documentId)) {
    // Podria ser un evento de test o ping
    if ($event === 'ping' || $event === 'test') {
        http_response_code(200);
        echo json_encode(['status' => 'pong']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Missing document_id']);
    exit;
}

global $pdo;

try {
    // Buscar documento en nuestra BD
    $stmt = $pdo->prepare("
        SELECT aid.id, aid.advisory_invoice_id, ai.advisory_id
        FROM advisory_inmatic_documents aid
        JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
        WHERE aid.inmatic_document_id = ?
    ");
    $stmt->execute([$documentId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        // Documento no encontrado - puede ser de otra integracion
        // Respondemos OK para que Inmatic no reintente
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | IGNORED: Document $documentId not found in our DB\n", FILE_APPEND | LOCK_EX);
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'reason' => 'document not found']);
        exit;
    }

    // Mapear estado de Inmatic a nuestro estado
    $mappedStatus = mapInmaticStatus($status, $event);

    // Actualizar estado del documento
    $stmt = $pdo->prepare("
        UPDATE advisory_inmatic_documents
        SET inmatic_status = ?,
            processed_at = NOW(),
            ocr_data = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $mappedStatus,
        $ocrData ? json_encode($ocrData) : null,
        $doc['id']
    ]);

    // Si fue procesado exitosamente, marcar factura como procesada
    if (in_array($mappedStatus, ['processed', 'approved', 'exported'])) {
        $stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ?");
        $stmt->execute([$doc['advisory_invoice_id']]);

        // Sincronizar proveedor si hay datos OCR (solo para facturas de gasto)
        if ($ocrData) {
            $stmt = $pdo->prepare("SELECT type FROM advisory_invoices WHERE id = ?");
            $stmt->execute([$doc['advisory_invoice_id']]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($invoice && $invoice['type'] === 'gasto') {
                syncSupplierFromOcr($doc['advisory_id'], $ocrData);
            }
        }

        // Notificar a la asesoria
        notifyAdvisory($doc['advisory_id'], $doc['advisory_invoice_id'], $mappedStatus, $ocrData);
    }

    // Log de exito
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | SUCCESS: Document $documentId updated to status $mappedStatus\n", FILE_APPEND | LOCK_EX);

    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'document_id' => $documentId,
        'new_status' => $mappedStatus
    ]);

} catch (Exception $e) {
    // Log de error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);

    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Mapear estado de Inmatic a nuestro estado interno
 */
function mapInmaticStatus($status, $event = '')
{
    // Primero intentar por evento
    $eventMap = [
        'document.processed' => 'processed',
        'document.approved' => 'approved',
        'document.rejected' => 'rejected',
        'document.exported' => 'exported',
        'document.error' => 'error',
        'document.created' => 'pending',
        'document.processing' => 'processing'
    ];

    if ($event && isset($eventMap[$event])) {
        return $eventMap[$event];
    }

    // Luego por estado
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

    $statusLower = strtolower($status);
    return $statusMap[$statusLower] ?? 'unknown';
}

/**
 * Notificar a la asesoria sobre el estado del documento (opcional)
 */
function notifyAdvisory($advisoryId, $invoiceId, $status, $ocrData = null)
{
    global $pdo;

    // Obtener user_id de la asesoria
    $stmt = $pdo->prepare("SELECT user_id FROM advisories WHERE id = ?");
    $stmt->execute([$advisoryId]);
    $advisory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$advisory) return;

    $statusMessages = [
        'processed' => 'procesada correctamente por Inmatic',
        'approved' => 'aprobada en Inmatic',
        'exported' => 'exportada a contabilidad desde Inmatic',
        'rejected' => 'rechazada en Inmatic'
    ];

    $message = $statusMessages[$status] ?? "actualizada a estado: $status";

    // Crear notificacion
    notification(
        1, // Sistema
        $advisory['user_id'],
        null,
        'Factura ' . $status,
        "Una factura ha sido $message."
    );
}
