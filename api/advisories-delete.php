<?php
/**
 * API: Eliminar asesoría (soft delete) (Admin)
 * POST /api/advisories-delete
 *
 * Parámetros POST:
 * - id (required): ID de la asesoría
 *
 * IMPORTANTE: Se usa soft delete para mantener integridad histórica.
 * La eliminación notifica a admin y desvincula clientes del módulo asesoría.
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031359300);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051359300);
}

global $pdo;

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    json_response("ko", "ID de asesoría no válido", 4001359300);
}

try {
    // Verificar que existe
    $stmt = $pdo->prepare("
        SELECT a.id, a.razon_social, a.cif, a.user_id, COUNT(ca.id) as total_customers
        FROM advisories a
        LEFT JOIN customers_advisories ca ON ca.advisory_id = a.id
        WHERE a.id = :id AND a.deleted_at IS NULL
        GROUP BY a.id
    ");
    $stmt->execute([':id' => $id]);
    $advisory = $stmt->fetch();

    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 4041359300);
    }

    $pdo->beginTransaction();

    // 1. Soft delete de la asesoría
    $stmt = $pdo->prepare("UPDATE advisories SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // 2. Desvincular clientes (no se eliminan, solo se desvinculan del módulo asesoría)
    // Esto permite que los clientes sigan usando la app de servicios
    $stmt = $pdo->prepare("DELETE FROM customers_advisories WHERE advisory_id = :id");
    $stmt->execute([':id' => $id]);

    // 3. Si hay usuario asociado, no lo eliminamos pero removemos el rol de asesoría
    if ($advisory['user_id']) {
        // Verificar si el usuario tiene otros roles antes de eliminar
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM model_has_roles
            WHERE model_id = :user_id AND model_type = 'App\\\\Models\\\\User' AND role_id != 5
        ");
        $stmt->execute([':user_id' => $advisory['user_id']]);
        $hasOtherRoles = $stmt->fetchColumn() > 0;

        // Eliminar rol de asesoría
        $stmt = $pdo->prepare("
            DELETE FROM model_has_roles
            WHERE model_id = :user_id AND model_type = 'App\\\\Models\\\\User' AND role_id = 5
        ");
        $stmt->execute([':user_id' => $advisory['user_id']]);

        // Si no tiene otros roles, soft delete del usuario
        if (!$hasOtherRoles) {
            $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :user_id");
            $stmt->execute([':user_id' => $advisory['user_id']]);
        }
    }

    $pdo->commit();

    // Log
    app_log('advisory', $id, 'advisory_delete', 'advisory', $id, USER['id'], [
        'razon_social' => $advisory['razon_social'],
        'cif' => $advisory['cif'],
        'total_customers_affected' => $advisory['total_customers']
    ]);

    // Crear notificación para administración
    notification_v2(
        USER['id'],
        1, // Admin user ID (ajustar según necesidad)
        null,
        'Asesoría eliminada',
        "Se ha eliminado la asesoría \"{$advisory['razon_social']}\" (CIF: {$advisory['cif']}). {$advisory['total_customers']} cliente(s) han sido desvinculados.",
        'Asesoría eliminada en Facilítame',
        'notification-admin-advisory-deleted',
        ['advisory_name' => $advisory['razon_social'], 'cif' => $advisory['cif']]
    );

    $message = "Asesoría eliminada correctamente";
    if ($advisory['total_customers'] > 0) {
        $message .= ". {$advisory['total_customers']} cliente(s) han sido desvinculados del módulo de asesoría.";
    }

    json_response("ok", $message, 2001359300);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en advisories-delete: " . $e->getMessage());
    json_response("ko", "Error al eliminar la asesoría", 5001359300);
}
