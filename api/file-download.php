<?php
/**
 * API: Descarga de archivos genérica
 * GET /api/file-download?type=X&id=Y
 *
 * Tipos soportados:
 * - advisory_invoice: Facturas de asesoría (advisory_invoices)
 * - request_file: Documentos de solicitudes (request_files)
 * - offer: Ofertas (offers)
 *
 * Verifica permisos antes de servir el archivo.
 */

global $pdo;

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id || empty($type)) {
    http_response_code(400);
    die('Parámetros requeridos: type e id');
}

try {
    $file_path = null;
    $filename = null;
    $mime_type = null;
    $original_name = null;

    switch ($type) {
        case 'advisory_invoice':
            // Facturas de asesoría - accesible por la asesoría dueña o el cliente que la subió
            if (asesoria()) {
                $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
                $stmt->execute([USER['id']]);
                $advisory = $stmt->fetch();

                if (!$advisory) {
                    http_response_code(403);
                    die('No autorizado');
                }

                $stmt = $pdo->prepare("
                    SELECT filename, original_name, mime_type
                    FROM advisory_invoices
                    WHERE id = ? AND advisory_id = ?
                ");
                $stmt->execute([$id, $advisory['id']]);
            } elseif (cliente()) {
                // El cliente puede ver sus propias facturas enviadas
                $stmt = $pdo->prepare("
                    SELECT filename, original_name, mime_type
                    FROM advisory_invoices
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([$id, USER['id']]);
            } else {
                http_response_code(403);
                die('No autorizado');
            }

            $file = $stmt->fetch();
            if (!$file) {
                http_response_code(404);
                die('Archivo no encontrado');
            }

            $file_path = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $file['filename'];
            $filename = $file['filename'];
            $original_name = $file['original_name'] ?? $file['filename'];
            $mime_type = $file['mime_type'];
            break;

        case 'request_file':
            // Documentos de solicitudes
            $stmt = $pdo->prepare("SELECT request_id, filename, original_filename, mime_type FROM request_files WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch();

            if (!$file) {
                http_response_code(404);
                die('Archivo no encontrado');
            }

            if (!user_can_access_request($file['request_id'])) {
                http_response_code(403);
                die('No autorizado');
            }

            $file_path = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $file['filename'];
            $filename = $file['filename'];
            $original_name = $file['original_filename'] ?? $file['filename'];
            $mime_type = $file['mime_type'];
            break;

        case 'offer':
            // Ofertas
            $stmt = $pdo->prepare("SELECT request_id, offer_file FROM offers WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch();

            if (!$file) {
                http_response_code(404);
                die('Archivo no encontrado');
            }

            if (!user_can_access_request($file['request_id'])) {
                http_response_code(403);
                die('No autorizado');
            }

            $file_path = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $file['offer_file'];
            $filename = $file['offer_file'];
            $original_name = $file['offer_file'];
            break;

        case 'communication_file':
            // Archivos adjuntos de comunicados
            $stmt = $pdo->prepare("
                SELECT cf.*, c.advisory_id
                FROM advisory_communication_files cf
                INNER JOIN advisory_communications c ON cf.communication_id = c.id
                WHERE cf.id = ?
            ");
            $stmt->execute([$id]);
            $file = $stmt->fetch();

            if (!$file) {
                http_response_code(404);
                die('Archivo no encontrado');
            }

            // Verificar acceso: debe ser la asesoría dueña o un cliente destinatario
            $has_access = false;
            if (asesoria()) {
                $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
                $stmt->execute([USER['id']]);
                $advisory = $stmt->fetch();
                $has_access = $advisory && $advisory['id'] == $file['advisory_id'];
            } elseif (cliente()) {
                // Verificar que el cliente es destinatario
                $stmt = $pdo->prepare("
                    SELECT 1 FROM advisory_communication_recipients
                    WHERE communication_id = ? AND customer_id = ?
                ");
                $stmt->execute([$file['communication_id'], USER['id']]);
                $has_access = $stmt->fetch() !== false;
            }

            if (!$has_access) {
                http_response_code(403);
                die('No autorizado');
            }

            $file_path = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $file['url'];
            $filename = $file['url'];
            $original_name = $file['filename'] ?? $file['url'];
            $mime_type = $file['mime_type'];
            break;

        default:
            http_response_code(400);
            die('Tipo de archivo no soportado');
    }

    // Verificar que el archivo existe
    if (!file_exists($file_path)) {
        error_log("file-download: Archivo no encontrado en disco: " . $file_path);
        http_response_code(404);
        die('Archivo no encontrado en el servidor');
    }

    // Determinar mime type si no está definido
    if (empty($mime_type)) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime_types = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'txt' => 'text/plain'
        ];
        $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
    }

    // Headers para mostrar en navegador (inline)
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . basename($original_name) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');

    // Servir el archivo
    readfile($file_path);
    exit;

} catch (Throwable $e) {
    error_log("Error en file-download: " . $e->getMessage() . " - " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    die('Error interno del servidor');
}
