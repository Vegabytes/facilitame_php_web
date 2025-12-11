<?php
/**
 * API: Marcar todas las notificaciones como leídas (Cliente)
 * Endpoint: /api/notifications-mark-all-read-customer
 * Method: POST
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 405);
}

if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;
$customer_id = (int) USER['id'];

try {
    // Marcar todas las notificaciones del cliente como leídas
    $stmt = $pdo->prepare("
        UPDATE notifications n
        INNER JOIN requests req ON req.id = n.request_id
        SET n.status = 1
        WHERE req.user_id = ?
        AND n.status = 0
    ");
    $stmt->execute([$customer_id]);
    $updated = $stmt->rowCount();

    json_response("ok", "Se marcaron $updated notificaciones como leídas", 200, ['updated' => $updated]);

} catch (Throwable $e) {
    error_log("Error en api-notifications-mark-all-read-customer: " . $e->getMessage());
    json_response("ko", "Error interno", 500);
}
