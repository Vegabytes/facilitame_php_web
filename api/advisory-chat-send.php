<?php
if (!asesoria()) {
    json_response("err", "No autorizado", 0, []);
    exit;
}

global $pdo;

$customer_id = intval($_POST['customer_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$customer_id || empty($message)) {
    json_response("err", "Datos incompletos", 0, []);
    exit;
}

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("err", "Asesoría no encontrada", 0, []);
    exit;
}

$advisory_id = $advisory['id'];

// Verificar que el cliente pertenece a esta asesoría
$stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE advisory_id = ? AND customer_id = ?");
$stmt->execute([$advisory_id, $customer_id]);
if (!$stmt->fetch()) {
    json_response("err", "Cliente no autorizado", 0, []);
    exit;
}

// Insertar mensaje
$stmt = $pdo->prepare("INSERT INTO advisory_messages (advisory_id, customer_id, sender_type, content, is_read, created_at) VALUES (?, ?, 'advisory', ?, 0, NOW())");
$stmt->execute([$advisory_id, $customer_id, htmlspecialchars($message, ENT_QUOTES, 'UTF-8')]);

json_response("ok", "Mensaje enviado", 0, ['id' => $pdo->lastInsertId()]);
?>