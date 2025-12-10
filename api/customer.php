<?php
/**
 * Customer API endpoint
 * Handles: DELETE /api/customer/{id} - Soft delete (sets deleted_at)
 */

header('Content-Type: application/json');

// Check admin permission
if (!admin()) {
    json_response("ko", "No autorizado", 403);
}

// Parse customer ID from URL
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', parse_url($request_uri, PHP_URL_PATH));
$customer_id = end($path_parts);

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!is_numeric($customer_id) || $customer_id <= 0) {
        json_response("ko", "ID de cliente inválido", 400);
    }

    $customer_id = (int)$customer_id;

    global $pdo;

    // Verify user exists and is a cliente (autonomo, empresa, particular)
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.deleted_at, r.name as role
        FROM users u
        JOIN model_has_roles mhr ON u.id = mhr.model_id
        JOIN roles r ON mhr.role_id = r.id
        WHERE u.id = ? AND r.name IN ('autonomo', 'empresa', 'particular')
    ");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response("ko", "Cliente no encontrado", 404);
    }

    if ($user['deleted_at'] !== null) {
        json_response("ko", "El cliente ya está eliminado", 400);
    }

    // Soft delete: set deleted_at timestamp
    $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$customer_id]);

    json_response("ok", "Cliente eliminado correctamente", 200);
}

// Method not allowed
json_response("ko", "Método no permitido", 405);
