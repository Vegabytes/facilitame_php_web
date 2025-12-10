<?php
use Ramsey\Uuid\Uuid;

global $pdo;
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json');

if (!cliente()) {
    json_response("ko", "No autorizado", 4001);
}

$stmt = $pdo->prepare("
    SELECT a.id, a.plan, a.user_id as advisory_user_id
    FROM customers_advisories ca
    INNER JOIN advisories a ON ca.advisory_id = a.id
    WHERE ca.customer_id = ?
");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ko", "No estás vinculado a ninguna asesoría", 4002);
}

if ($advisory['plan'] === 'gratuito') {
    json_response("ko", "Tu asesoría tiene el plan gratuito sin envío de facturas", 4003);
}

$tag = $_POST['tag'] ?? '';
$invoice_type = $_POST['type'] ?? 'gasto';

// Validar tipo
if (!in_array($invoice_type, ['gasto', 'ingreso'])) {
    $invoice_type = 'gasto';
}

$notes = trim($_POST['notes'] ?? '');

if (empty($tag)) {
    json_response("ko", "Debes seleccionar una etiqueta", 4004);
}

if (!isset($_FILES['invoice_file']) || empty($_FILES['invoice_file']['name'][0])) {
    json_response("ko", "Debes seleccionar al menos un archivo", 4005);
}

$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
$max_size = 10 * 1024 * 1024;

$upload_dir = ROOT_DIR . '/' . DOCUMENTS_DIR . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$current_month = date('n');
$current_year = date('Y');
$current_quarter = ceil($current_month / 3);

$uploaded_count = 0;
$errors = [];

$files = $_FILES['invoice_file'];
$file_count = is_array($files['name']) ? count($files['name']) : 1;

try {
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $file_count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $mime_type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "Error al subir $name";
            continue;
        }
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "$name: Tipo de archivo no permitido";
            continue;
        }
        
        if ($size > $max_size) {
            $errors[] = "$name: Archivo demasiado grande (máx 10MB)";
            continue;
        }
        
        $uuid = Uuid::uuid4();
        $new_name = $uuid . '-' . $name;
        $file_size_mb = $size / (1024 * 1024);
        
        if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
            $stmt = $pdo->prepare("
                INSERT INTO advisory_invoices 
                (advisory_id, customer_id, filename, original_name, mime_type, file_size, type, tag, notes, month, year, quarter)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $advisory['id'],
                USER['id'],
                $new_name,
                $name,
                $mime_type,
                $file_size_mb,
                $invoice_type,
                $tag,
                $notes,
                $current_month,
                $current_year,
                $current_quarter
            ]);
            $uploaded_count++;
        } else {
            $errors[] = "Error al guardar $name";
        }
    }
    
    if ($uploaded_count === 0) {
        $pdo->rollBack();
        json_response("ko", "No se pudo subir ningún archivo. " . implode(', ', $errors), 4006);
    }
    
    $pdo->commit();
    
    $notification_subject = "Nueva factura recibida";
    $notification_message = USER["name"] . " ha enviado " . $uploaded_count . " factura(s) <a href='" . ROOT_URL . "/invoices'>Ver facturas</a>";
    notification(USER["id"], $advisory['advisory_user_id'], null, $notification_subject, $notification_message);
    
    $msg = "$uploaded_count factura(s) enviada(s) correctamente";
    if (!empty($errors)) {
        $msg .= ". Errores: " . implode(', ', $errors);
    }
    json_response("ok", $msg, 2001);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("===== ERROR UPLOADING INVOICE =====");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    error_log("===================================");
    
    json_response("ko", "Error: " . $e->getMessage() . " en línea " . $e->getLine(), 5001);
}