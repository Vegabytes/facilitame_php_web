<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $pdo->beginTransaction();

    $file_name_dir = __DIR__ . "/app-user-profile.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Inicio\n", FILE_APPEND | LOCK_EX);

    $query = "SELECT
    u.name,
    u.lastname,
    u.email,
    u.allow_invoice_access,
    u.allow_invoice_access_granted_at
    FROM `users` u
    WHERE u.id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $user_info = $stmt->fetch();
    
    $pdo->commit();

    if (!$user_info)
    {
        json_response("ko", "Ha ocurrido un error", 3728732698);
    }

    if ($user_info["allow_invoice_access"] == "1")
    {
        $user_info["allow_invoice_access_granted_at_display"] = date("d/m/Y H:i:s", strtotime($user_info["allow_invoice_access_granted_at"]));
    }

    $user_info["role_display"] = display_role();

    json_response("ok", "", 1847033324, $user_info);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    json_response("ko", "", 2163538512);
}

?>