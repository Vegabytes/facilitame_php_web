<?php
global $pdo;
$info = [];

$query = "SELECT * FROM `users` WHERE verification_token = :verification_token";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":verification_token", $_GET["token"]);
$stmt->execute();
$res = $stmt->fetchAll();

if (empty($res))
{
    $info["status"] = "ko";
    $info["message_html"] = "No se localiza el token";
}
else
{
    try
    {
        $pdo->beginTransaction();
        
        $res = $res[0];
    
        $now = new DateTime();
        $token_expires_at = new DateTime($res["token_expires_at"]);
    
        if (!is_null($res["email_verified_at"]))
        {
            $info["status"] = "ko";
            $info["message_html"] = "El enlace de activación ya ha sido utilizado";
        }
        elseif ($now > $token_expires_at)
        {
            $info["status"] = "ko";
            $info["message_html"] = "El enlace de activación ha caducado";
        }
        else
        {
            // NUEVO: Si el usuario NO tiene contraseña, necesita establecerla primero
            if (empty($res["password"]) || is_null($res["password"]))
            {
                // Redirigir a formulario para establecer contraseña
                $info["status"] = "pending_password";
                $info["message_html"] = "Establece tu contraseña para activar tu cuenta";
                $info["requires_password"] = true;
            }
            else
            {
                // Usuario ya tiene contraseña, solo activar
                $info["status"] = "ok";
                $info["message_html"] = "¡Cuenta activada! Inicia sesión para comenzar a disfrutar de Facilítame";
        
                $query = "UPDATE `users` SET email_verified_at = CURRENT_TIME() WHERE verification_token = :verification_token";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":verification_token", $_GET["token"]);
                $stmt->execute();
                
                require_once (ROOT_DIR . "/bold/functions.php");
                // Log en db
                $query = "INSERT INTO `log` SET 
                target_type = :target_type,
                target_id = :target_id,
                event = :event,
                link_type = :link_type,
                link_id = :link_id,
                triggered_by = :triggered_by";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":target_type", "customer");
                $stmt->bindValue(":target_id", $res["id"]);
                $stmt->bindValue(":event", "activate");
                $stmt->bindValue(":link_type", "customer");
                $stmt->bindValue(":link_id", $res["id"]);
                $stmt->bindValue(":triggered_by", $res["id"]);
                $stmt->execute();
            }
        }
        
        $pdo->commit();
    }
    catch (Throwable $e)
    {
        $pdo->rollBack();
        $info["status"] = "ko";
        $info["message_html"] = MSG;
        $info["code"] = 200957052;
    }
}
?>