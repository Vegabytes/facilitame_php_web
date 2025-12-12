<?php
/**
 * API: Actualizar usuario (comercial o proveedor)
 * POST /api/user-update
 *
 * Parámetros:
 * - user_id: ID del usuario
 * - role_id: ID del rol (2=proveedor, 7=comercial)
 * - name, lastname, email, phone, nif_cif
 * - code (solo para comerciales)
 * - new_password (opcional)
 */
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    $user_id = intval($_POST["user_id"] ?? 0);
    $role_id = intval($_POST["role_id"] ?? 0);

    if ($user_id <= 0) {
        json_response("ko", "ID de usuario no válido", 516269268);
    }

    // Verificar que el usuario existe y tiene el rol indicado
    $query = "SELECT u.*, mhr.role_id
              FROM users u
              JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
              WHERE u.id = :user_id AND mhr.role_id = :role_id AND u.deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user_id);
    $stmt->bindValue(":role_id", $role_id);
    $stmt->execute();
    $db = $stmt->fetch();

    if ($db === false)
    {
        json_response("ko", "Usuario no encontrado", 516269269);
    }

    $is_comercial = ($role_id == 7);

    // Validar código solo para comerciales
    if ($is_comercial) {
        if (!isset($_POST["code"]) || empty($_POST["code"]) || $_POST["code"] == "")
        {
            json_response("ko", "El código no puede quedar vacío", 4180209335);
        }
    }

    // Validar y actualizar email si cambió
    if ($_POST["email"] != $db["email"])
    {
        $query = "SELECT * FROM `users` WHERE email = :email AND id != :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":email", $_POST["email"]);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();
        $aux = $stmt->fetchAll();

        if (count($aux) !== 0)
        {
            json_response("ko", "El email indicado está en uso", 411642136);
        }

        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL))
        {
            json_response("ko", "El email indicado no es válido", 2403227275);
        }

        $query = "UPDATE `users` SET email = :email, updated_at = NOW() WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->bindValue(":email", $_POST["email"]);
        $stmt->execute();

        app_log("user", $user_id, "user_update_email");
    }

    // Actualizar contraseña si se indicó
    if (!empty($_POST["new_password"]))
    {
        $password_hash = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

        $query = "UPDATE `users` SET password = :password, updated_at = NOW() WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->bindValue(":password", $password_hash);
        $stmt->execute();

        app_log("user", $user_id, "user_update_password");
    }

    // Actualizar datos básicos
    $query = "UPDATE `users` SET name = :name, lastname = :lastname, phone = :phone, nif_cif = :nif_cif, updated_at = NOW() WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":name", htmlspecialchars($_POST["name"] ?? '', ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":lastname", htmlspecialchars($_POST["lastname"] ?? '', ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":phone", htmlspecialchars($_POST["phone"] ?? '', ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":nif_cif", htmlspecialchars($_POST["nif_cif"] ?? '', ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":user_id", $user_id);
    $stmt->execute();

    app_log("user", $user_id, "user_update_info");

    // Actualizar código (solo para comerciales)
    if ($is_comercial && !empty($_POST["code"])) {
        // Verificar que el código no esté en uso por otro comercial
        $query = "SELECT * FROM `sales_codes` WHERE code = :code AND user_id != :user_id AND deleted_at IS NULL";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":code", $_POST["code"]);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();
        $aux = $stmt->fetchAll();

        if (count($aux) !== 0)
        {
            json_response("ko", "El código indicado está en uso", 2136490318);
        }

        $query = "UPDATE `sales_codes` SET code = :code, updated_at = NOW() WHERE user_id = :user_id AND deleted_at IS NULL";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":code", $_POST["code"]);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();

        app_log("user", $user_id, "user_update_code");
    }

    $pdo->commit();

    $role_label = $is_comercial ? "Comercial" : "Proveedor";
    json_response("ok", "{$role_label} actualizado correctamente", 534989561);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    error_log("Error en api/user-update: " . $e->getMessage());
    json_response("ko", "Error al actualizar el usuario", 3884004512);
}
