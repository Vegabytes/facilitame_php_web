<?php
/**
 * Cron: Sincronizar estado de documentos con Inmatic
 *
 * Este cron verifica el estado de los documentos pendientes en Inmatic
 * y actualiza nuestra base de datos.
 *
 * Ejecutar cada 15 minutos:
 * */15 * * * * php /path/to/Facilitame/crons/sync-inmatic-status.php
 *
 * O cada 5 minutos si hay mucho volumen:
 * */5 * * * * php /path/to/Facilitame/crons/sync-inmatic-status.php
 */

// Detectar si se ejecuta desde CLI o web
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Si se llama desde web, verificar que sea admin
    require_once __DIR__ . '/../bold/bold.php';
    if (!admin()) {
        http_response_code(403);
        die('No autorizado');
    }
} else {
    // Desde CLI, cargar configuracion manualmente
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../bold/bootstrap.php';
}

require_once ROOT_DIR . '/bold/classes/InmaticClient.php';

global $pdo;

// Log
$logFile = ROOT_DIR . '/logs/inmatic-sync.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMsg($message) {
    global $logFile;
    $entry = date('Y-m-d H:i:s') . ' | ' . $message . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    if (php_sapi_name() === 'cli') {
        echo $entry;
    }
}

logMsg("=== Iniciando sincronización Inmatic ===");

// Obtener documentos pendientes de los ultimos 7 dias
$stmt = $pdo->query("
    SELECT
        aid.id,
        aid.inmatic_document_id,
        aid.advisory_invoice_id,
        aid.inmatic_status,
        aid.sent_at,
        ai.advisory_id
    FROM advisory_inmatic_documents aid
    JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
    WHERE aid.inmatic_status IN ('pending', 'processing', 'review')
      AND aid.sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND aid.inmatic_document_id IS NOT NULL
      AND aid.inmatic_document_id != ''
    ORDER BY aid.sent_at ASC
    LIMIT 100
");

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalDocs = count($documents);

logMsg("Documentos pendientes encontrados: $totalDocs");

if ($totalDocs === 0) {
    logMsg("No hay documentos que sincronizar");
    exit(0);
}

// Agrupar por advisory_id para optimizar conexiones
$byAdvisory = [];
foreach ($documents as $doc) {
    $advisoryId = $doc['advisory_id'];
    if (!isset($byAdvisory[$advisoryId])) {
        $byAdvisory[$advisoryId] = [];
    }
    $byAdvisory[$advisoryId][] = $doc;
}

$updated = 0;
$errors = 0;
$unchanged = 0;

foreach ($byAdvisory as $advisoryId => $docs) {
    logMsg("Procesando asesoría ID: $advisoryId (" . count($docs) . " documentos)");

    try {
        $client = new InmaticClient($advisoryId);

        foreach ($docs as $doc) {
            try {
                $result = $client->getDocument($doc['inmatic_document_id']);

                if (!$result) {
                    logMsg("  - Doc {$doc['inmatic_document_id']}: Sin respuesta");
                    continue;
                }

                // Obtener nuevo estado
                $newStatus = $result['status'] ?? $result['state'] ?? $result['data']['status'] ?? null;
                $ocrData = $result['ocr_data'] ?? $result['extracted_data'] ?? $result['data']['ocr_data'] ?? null;

                if (!$newStatus) {
                    logMsg("  - Doc {$doc['inmatic_document_id']}: Sin estado en respuesta");
                    continue;
                }

                // Mapear estado
                $mappedStatus = mapStatus($newStatus);

                // Verificar si cambio
                if ($mappedStatus === $doc['inmatic_status']) {
                    $unchanged++;
                    continue;
                }

                // Actualizar en BD
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

                // Si fue procesado, marcar factura
                if (in_array($mappedStatus, ['processed', 'approved', 'exported'])) {
                    $stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ?");
                    $stmt->execute([$doc['advisory_invoice_id']]);
                }

                logMsg("  - Doc {$doc['inmatic_document_id']}: {$doc['inmatic_status']} -> $mappedStatus");
                $updated++;

            } catch (Exception $e) {
                logMsg("  - Doc {$doc['inmatic_document_id']}: ERROR - " . $e->getMessage());
                $errors++;
            }

            // Pequeña pausa para no saturar la API
            usleep(200000); // 200ms
        }

    } catch (Exception $e) {
        logMsg("  ERROR conectando con asesoría $advisoryId: " . $e->getMessage());
        $errors += count($docs);
    }
}

logMsg("=== Sincronización completada ===");
logMsg("Actualizados: $updated | Sin cambios: $unchanged | Errores: $errors");

// Salir con codigo apropiado
exit($errors > 0 ? 1 : 0);

/**
 * Mapear estado de Inmatic
 */
function mapStatus($status) {
    $map = [
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

    return $map[strtolower($status)] ?? 'unknown';
}
