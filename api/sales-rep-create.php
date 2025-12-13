<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();
    
    if (!isset($_POST["code"]) || empty($_POST["code"]) || $_POST["code"] == "")
    {
        json_response("ko", "El código no puede quedar vacío", 3078892940);
    }
    if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL))
    {
        json_response("ko", "El email indicado no es válido", 930765382);
    }
    $query = "SELECT * FROM `users` WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->execute();
    $aux = $stmt->fetchAll();

    if (count($aux) !== 0)
    {
        json_response("ko", "El email indicado está en uso", 787689779);
    }

    if ($_POST["password"] == "")
    {
        json_response("ko", "Escribe una contraseña", 2617374889);
    }

    if ($_POST["password"] != $_POST["password_confirm"])
    {
        json_response("ko", "La contraseña y la confirmación no coinciden", 459830847);
    }

    if ($_POST["code"] == "")
    {
        json_response("ko", "Indica un código de comercial", 2068345602);
    }

    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $query = "INSERT INTO `users` SET name = :name, lastname = :lastname, phone = :phone, email = :email, password = :password, nif_cif = :nif_cif, email_verified_at = CURRENT_TIMESTAMP()";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":name", htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":lastname", htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":phone", htmlspecialchars($_POST["phone"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->bindValue(":password", $password_hash);
    $stmt->bindValue(":nif_cif", htmlspecialchars($_POST["nif_cif"], ENT_QUOTES, 'UTF-8'));
    $stmt->execute();
    $id_insertada = $pdo->lastInsertId();

    $query = "INSERT INTO `model_has_roles` SET model_id = $id_insertada, role_id = 7";
    $stmt = $pdo->prepare($query);    
    $stmt->execute();

    $query = "INSERT INTO `sales_codes` SET user_id = $id_insertada, code = :code";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":code", $_POST["code"]);
    $stmt->execute();

    app_log("customer", $id_insertada, "sales_rep_create");

    $pdo->commit();

    json_response("ok", "Nuevo comercial dado de alta", 2381082975);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", "Error al crear el comercial", 3472890866);
}