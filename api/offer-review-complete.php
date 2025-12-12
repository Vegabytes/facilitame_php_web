<?php
/**
 * API: Completar revisión de solicitud
 *
 * Permite al proveedor marcar como completada una revisión
 * y devolver la solicitud a estado 7 (Activada)
 *
 * POST:
 * - request_id: ID de la solicitud
 * - notes: (opcional) Notas sobre la revisión realizada
 */

if (!proveedor() && !admin()) {
    json_response("ko", "No autorizado", 403);
}

$request_id = intval($_POST["request_id"] ?? 0);

if (!$request_id) {
    json_response("ko", "ID de solicitud requerido", 400);
}

if (!user_can_access_request($request_id)) {
    json_response("ko", "No tienes acceso a esta solicitud", 403);
}

$request = get_request($request_id);

if (!$request) {
    json_response("ko", "Solicitud no encontrada", 404);
}

// Solo se puede completar revisión si está en estado 8
if ((int)$request["status_id"] !== 8) {
    json_response("ko", "Esta solicitud no está en revisión. Estado actual: " . $request["status_id"], 400);
}

global $pdo;

try {
    $pdo->beginTransaction();

    // Volver a estado 7 (Activada)
    $stmt = $pdo->prepare("UPDATE requests SET status_id = 7 WHERE id = :id");
    $stmt->execute([":id" => $request_id]);

    // Registrar en log de estados
    $stmt = $pdo->prepare("
        INSERT INTO request_status_log (request_id, previous_status_id, new_status_id)
        VALUES (:request_id, 8, 7)
    ");
    $stmt->execute([":request_id" => $request_id]);

    // Log de la aplicación
    app_log("request", $request_id, "review_completed");

    $pdo->commit();

    // Notificar al cliente
    notification(
        USER["id"],
        $request["user_id"],
        $request_id,
        "Revisión completada",
        "El proveedor ha completado la revisión de tu solicitud. El servicio vuelve a estar activo."
    );

    json_response("ok", "Revisión completada. La solicitud vuelve a estar activa.", 200);

} catch (Exception $e) {
    $pdo->rollBack();
    json_response("ko", "Error al completar revisión: " . $e->getMessage(), 500);
}
