<?php
/**
 * API: Eliminar comercial (soft delete)
 * POST /api/sales-rep-delete
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
    if (!isset($_POST["sales_rep_id"]) || empty($_POST["sales_rep_id"]) || $_POST["sales_rep_id"] == "")
    {
        json_response("ko", "ID de comercial no válido", 3527189104);
    }

    $sales_rep_id = intval($_POST["sales_rep_id"]);

    // Verificar que el usuario existe y es comercial
    $query = "SELECT u.id, u.email, u.name
              FROM users u
              INNER JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
              WHERE u.id = :sales_rep_id
              AND u.deleted_at IS NULL
              AND mhr.role_id = 7";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user)
    {
        json_response("ko", "Comercial no encontrado", 3527189105);
    }

    $pdo->beginTransaction();

    // Soft delete del usuario
    $query = "UPDATE `users` SET deleted_at = NOW(), updated_at = NOW() WHERE id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();

    // Soft delete de los códigos de venta
    $query = "UPDATE `sales_codes` SET deleted_at = NOW(), updated_at = NOW() WHERE user_id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();

    app_log("user", $sales_rep_id, "sales_rep_delete", "user", $sales_rep_id, USER["id"], [
        "email" => $user["email"],
        "name" => $user["name"]
    ]);

    $pdo->commit();

    json_response("ok", "Comercial eliminado correctamente", 678739611);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "Error al eliminar el comercial", 408166843);
}