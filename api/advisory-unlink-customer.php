<?php
/**
 * API: Desvincular cliente de asesoría
 * POST /api/advisory-unlink-customer
 *
 * Parámetros POST:
 * - customer_id (required): ID del cliente a desvincular
 *
 * Solo puede ser usado por asesorías
 * Notifica al admin cuando se desvincula un cliente
 */

header('Content-Type: application/json; charset=utf-8');

if (!asesoria()) {
    json_response('error', 'No autorizado', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response('error', 'Método no permitido', 405);
}

global $pdo;

// Obtener advisory_id de la asesoría actual
$stmt = $pdo->prepare("SELECT id, razon_social, cif FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('error', 'Asesoría no encontrada', 404);
}

$advisory_id = $advisory['id'];

// Validar customer_id
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

if ($customer_id <= 0) {
    json_response('error', 'ID de cliente no válido', 400);
}

try {
    // Verificar que el cliente está vinculado a esta asesoría
    $stmt = $pdo->prepare("
        SELECT ca.id, u.name, u.lastname, u.email
        FROM customers_advisories ca
        INNER JOIN users u ON u.id = ca.customer_id
        WHERE ca.customer_id = ? AND ca.advisory_id = ?
    ");
    $stmt->execute([$customer_id, $advisory_id]);
    $link = $stmt->fetch();

    if (!$link) {
        json_response('error', 'Este cliente no está vinculado a tu asesoría', 404);
    }

    $customer_name = trim($link['name'] . ' ' . $link['lastname']);
    $customer_email = $link['email'];

    // Eliminar vínculo
    $stmt = $pdo->prepare("DELETE FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
    $stmt->execute([$customer_id, $advisory_id]);

    // Log
    app_log('advisory', $advisory_id, 'customer_unlink', 'customer', $customer_id, USER['id'], [
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'advisory_name' => $advisory['razon_social']
    ]);

    // Notificar al admin
    notification_v2(
        USER['id'],
        ADMIN_ID,
        null,
        'Cliente desvinculado',
        "La asesoría {$advisory['razon_social']} ha desvinculado al cliente {$customer_name} ({$customer_email})",
        'Cliente desvinculado de asesoría',
        'notification-admin-advisory-customer-unlinked',
        [
            'advisory_name' => $advisory['razon_social'],
            'advisory_cif' => $advisory['cif'],
            'customer_name' => $customer_name,
            'customer_email' => $customer_email
        ]
    );

    json_response('ok', 'Cliente desvinculado correctamente', 200);

} catch (Exception $e) {
    error_log("Error en advisory-unlink-customer: " . $e->getMessage());
    json_response('error', 'Error al desvincular el cliente', 500);
}
