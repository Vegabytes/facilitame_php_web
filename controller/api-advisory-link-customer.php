<?php
if (!asesoria()) {
    json_response('error', 'No autorizado', 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, razon_social FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('error', 'Asesoría no encontrada', 404);
}

$advisory_id = $advisory['id'];
$advisory_name = $advisory['razon_social'];

$customer_id = intval($_POST['customer_id'] ?? 0);
$client_type = $_POST['client_type'] ?? '';
$subtype = $_POST['subtype'] ?? '';
$email_verification = trim($_POST['email'] ?? '');

if (!$customer_id) {
    json_response('error', 'ID de cliente no válido', 400);
}

if (empty($email_verification)) {
    json_response('error', 'Email de verificación requerido', 400);
}

// Verificar que el usuario existe, es cliente Y el email coincide
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.lastname, u.email 
    FROM users u 
    INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
    WHERE u.id = ? AND mhr.role_id IN (4, 5, 6)
");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    json_response('error', 'Usuario no encontrado o no es un cliente válido', 404);
}

// Verificar que el email coincide
if (strtolower($customer['email']) !== strtolower($email_verification)) {
    error_log("Intento de vincular cliente con email no coincidente. Customer ID: $customer_id, Email enviado: $email_verification, Email real: {$customer['email']}");
    json_response('error', 'No autorizado para vincular este cliente', 403);
}

// Verificar que no esté ya vinculado
$stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
$stmt->execute([$customer_id, $advisory_id]);

if ($stmt->fetch()) {
    json_response('error', 'Este cliente ya está vinculado a tu asesoría', 409);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO customers_advisories (customer_id, advisory_id, client_type, client_subtype, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$customer_id, $advisory_id, $client_type, $subtype]);
    
    // Enviar notificación al cliente
    $data = [
        'name' => $customer['name'],
        'advisory_name' => $advisory_name
    ];
    
    ob_start();
    require(ROOT_DIR . "/email-templates/customer-linked-advisory.php");
    $body = ob_get_clean();
    
    $subject = "Has sido vinculado a " . $advisory_name;
    send_mail($customer['email'], $customer['name'], $subject, $body, 8844552212);
    
    json_response('ok', 'Cliente vinculado correctamente', 200, [
        'customer_id' => $customer_id,
        'customer_name' => $customer['name'] . ' ' . $customer['lastname']
    ]);
    
} catch (Exception $e) {
    error_log("Error linking customer: " . $e->getMessage());
    json_response('error', 'Error al vincular el cliente', 500);
}