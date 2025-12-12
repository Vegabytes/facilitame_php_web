<?php
$query = "SELECT * FROM `users`
WHERE 1
AND id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($res) !== 1)
{
    json_response("ko", "No se ha podido actualizar la información", 2184356141);
}

$db_user = $res[0];

try
{
    $pdo->beginTransaction();
    
    $query = "UPDATE `users` SET name = :name, lastname = :lastname WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":name", htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8'));
    $stmt->bindValue(":lastname", htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8'));
    $stmt->execute();
    app_log("customer", USER["id"], "customer_update_info");

    $query = "SELECT * FROM `users` WHERE email = :email AND id != :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->execute();
    $res = $stmt->fetchAll();

    if (count($res) !== 0)
    {
        $pdo->rollBack();
        json_response("ko", "La dirección de email indicada no está disponible", 2839110291);
    }

    $query = "UPDATE `users` SET email = :email WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":email", $_POST["email"]);
    $stmt->execute();

    app_log("customer", USER["id"], "customer_update_email");
    
    $pdo->commit();
    json_response("ok", "Detalles actualizados", 3394209494);
}
catch (Exception $e)
{
    $pdo->rollBack();
    json_response("ko", "No se ha podido actualizar la información", 2246033081);
}

?>