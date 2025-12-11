<?php
/**
 * API: Eliminar usuario (comercial o proveedor) - soft delete
 * POST /api/user-delete
 *
 * Parámetros:
 * - user_id: ID del usuario
 * - role_id: ID del rol (2=proveedor, 7=comercial)
 *
 * Se usa soft delete para mantener la integridad de los datos históricos
 */
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $user_id = intval($_POST["user_id"] ?? 0);
    $role_id = intval($_POST["role_id"] ?? 0);

    if ($user_id <= 0)
    {
        json_response("ko", "ID de usuario no válido", 3527189104);
    }

    if (!in_array($role_id, [2, 7]))
    {
        json_response("ko", "Rol no válido", 3527189106);
    }

    // Verificar que el usuario existe y tiene el rol indicado
    $query = "SELECT u.id, u.email, u.name, mhr.role_id
              FROM users u
              INNER JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
              WHERE u.id = :user_id
              AND u.deleted_at IS NULL
              AND mhr.role_id = :role_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user_id);
    $stmt->bindValue(":role_id", $role_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user)
    {
        $role_label = ($role_id == 7) ? "Comercial" : "Proveedor";
        json_response("ko", "{$role_label} no encontrado", 3527189105);
    }

    $is_comercial = ($role_id == 7);

    $pdo->beginTransaction();

    // Soft delete del usuario
    $query = "UPDATE `users` SET deleted_at = NOW(), updated_at = NOW() WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user_id);
    $stmt->execute();

    // Si es comercial, también hacer soft delete de los códigos de venta
    if ($is_comercial)
    {
        $query = "UPDATE `sales_codes` SET deleted_at = NOW(), updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();
    }

    $action = $is_comercial ? "sales_rep_delete" : "proveedor_delete";
    app_log("user", $user_id, $action, "user", $user_id, USER["id"], [
        "email" => $user["email"],
        "name" => $user["name"]
    ]);

    $pdo->commit();

    $role_label = $is_comercial ? "Comercial" : "Proveedor";
    json_response("ok", "{$role_label} eliminado correctamente", 678739611);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    error_log("Error en api/user-delete: " . $e->getMessage());
    json_response("ko", "Error al eliminar el usuario", 408166843);
}
