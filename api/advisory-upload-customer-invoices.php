<?php
// api/advisory-upload-customer-invoices.php
use Ramsey\Uuid\Uuid;

global $pdo;
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json');

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

// Obtener el ID real de la asesoría
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

$advisory_id = (int)$advisory['id'];

// Obtener customer_id del formulario
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

if (!$customer_id) {
    json_response("ko", "Debes seleccionar un cliente", 4001);
}

// Verificar que el cliente pertenece a esta asesoría
$stmt = $pdo->prepare("
    SELECT ca.customer_id, u.name, u.lastname 
    FROM customers_advisories ca
    INNER JOIN users u ON u.id = ca.customer_id
    WHERE ca.customer_id = ? AND ca.advisory_id = ?
");
$stmt->execute([$customer_id, $advisory_id]);
$customer = $stmt->fetch();

if (!$customer) {
    json_response("ko", "El cliente no pertenece a tu asesoría", 4002);
}

$tag = $_POST['tag'] ?? '';
$invoice_type = $_POST['type'] ?? 'gasto';

// Validar tipo
if (!in_array($invoice_type, ['gasto', 'ingreso'])) {
    $invoice_type = 'gasto';
}

$notes = trim($_POST['notes'] ?? '');

if (empty($tag)) {
    json_response("ko", "Debes seleccionar una etiqueta", 4003);
}

// Buscar archivos (puede venir como invoice_file o invoice_files)
$files = null;
if (isset($_FILES['invoice_files']) && !empty($_FILES['invoice_files']['name'][0])) {
    $files = $_FILES['invoice_files'];
} elseif (isset($_FILES['invoice_file']) && !empty($_FILES['invoice_file']['name'][0])) {
    $files = $_FILES['invoice_file'];
}

if (!$files) {
    json_response("ko", "Debes seleccionar al menos un archivo", 4004);
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

$file_count = is_array($files['name']) ? count($files['name']) : 1;

try {
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $file_count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $mime_type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if (empty($name)) continue;
        
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "Error al subir $name";
            continue;
        }
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "$name: Tipo no permitido";
            continue;
        }
        
        if ($size > $max_size) {
            $errors[] = "$name: Máximo 10MB";
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
                $advisory_id,
                $customer_id,
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
        json_response("ko", "No se pudo subir ningún archivo. " . implode(', ', $errors), 4005);
    }
    
    $pdo->commit();
    
    // Notificar al cliente
    $customer_name = trim($customer['name'] . ' ' . $customer['lastname']);
    $notification_subject = "Nueva factura de tu asesoría";
    $notification_message = "Tu asesoría ha subido " . $uploaded_count . " factura(s) a tu cuenta. <a href='" . ROOT_URL . "/invoices'>Ver facturas</a>";
    notification(USER["id"], $customer_id, null, $notification_subject, $notification_message);
    
    $msg = "$uploaded_count factura(s) subida(s) para $customer_name";
    if (!empty($errors)) {
        $msg .= ". Errores: " . implode(', ', $errors);
    }
    json_response("ok", $msg, 200);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("===== ERROR ADVISORY UPLOAD INVOICE =====");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("==========================================");
    
    json_response("ko", "Error: " . $e->getMessage(), 500);
}