<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();
    
    $query = "SELECT * FROM `users` WHERE id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();
    $db = $stmt->fetch();

    if ($db === false)
    {
        json_response("ko", MSG, 516269268);
    }

    if (!isset($_POST["code"]) || empty($_POST["code"]) || $_POST["code"] == "")
    {
        json_response("ko", "El código no puede quedar vacío", 4180209335);
    }

    if ($_POST["email"] != $db["email"])
    {
        $query = "SELECT * FROM `users` WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":email", $_POST["email"]);
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

        $query = "UPDATE `users` SET email = :email WHERE id = :sales_rep_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
        $stmt->bindValue(":email", $_POST["email"]);
        $stmt->execute();

        app_log("customer", $_POST["sales_rep_id"], "sales_rep_update_email");
    }

    if ($_POST["new_password"] != "")
    {
        $password_hash = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

        $query = "UPDATE `users` SET password = :password WHERE id = :sales_rep_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
        $stmt->bindValue(":password", $password_hash);
        $stmt->execute();

        app_log("customer", $_POST["sales_rep_id"], "sales_rep_update_password");
    }

    $query = "UPDATE `users` SET name = :name, lastname = :lastname, phone = :phone, nif_cif = :nif_cif WHERE id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":name", htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":lastname", htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":phone", htmlspecialchars($_POST["phone"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":nif_cif", htmlspecialchars($_POST["nif_cif"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();

    app_log("customer", $_POST["sales_rep_id"], "sales_rep_update_info");

    $query = "SELECT * FROM `sales_codes` WHERE code = :code AND user_id != :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":code", $_POST["code"]);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();
    $aux = $stmt->fetchAll();

    if (count($aux) !== 0)
    {
        json_response("ko", "El código indicado está en uso o ya ha sido utilizado", 2136490318);
    }

    $query = "UPDATE `sales_codes` SET code = :code WHERE user_id = :sales_rep_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":code", $_POST["code"]);
    $stmt->bindValue(":sales_rep_id", $_POST["sales_rep_id"]);
    $stmt->execute();

    app_log("customer", $_POST["sales_rep_id"], "sales_rep_update_code");
    $pdo->commit();

    json_response("ok", "Actualizado", 534989561);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 3884004512);
}