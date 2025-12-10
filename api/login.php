<?php
use \Firebase\JWT\JWT;

sleep(1);

$query = "SELECT users.*, roles.name AS role_name
FROM `users`, `roles`, `model_has_roles`
WHERE 1
AND users.email = :email
AND users.id = model_has_roles.model_id
AND model_has_roles.role_id = roles.id
AND users.deleted_at IS NULL";

$stmt = $pdo->prepare($query);
$stmt->bindValue(":email", $_POST["email"]);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($res) !== 1)
{    
    json_response("ko", "Las credenciales no son válidas", 79080607);
}
else
{    
    $user = $res[0];
    

    if (!password_verify($_POST["password"], $user["password"]))
    {
        json_response("ko", "Las credenciales no son válidas", 4090484573);
    }
    elseif (is_null($user["email_verified_at"]))
    {        
        json_response("ko", "<b>Tu cuenta debe ser activada primero.</b><br><br>Revisa tu bandeja de entrada. Deberías haber recibido un mensaje con un enlace de activación poco después de completar tu registro", 1380009503);
    }
    else
    {
        $issued_at = time();
        $expiration_time = $issued_at + SESSION_LENGTH;
        $payload = array(
            'role' => $user["role_name"],
            'user_id' => $user["id"],
            'iat' => $issued_at,
            'exp' => $expiration_time
        );
    
        $jwt = JWT::encode($payload, JWT_SECRET, "HS256");
        $response["status"] = "ok";
        $response["code"] = 3232292490;
        $response["auth_token"] = $jwt;
        setcookie("auth_token", $jwt, time() + SESSION_LENGTH, "/", "", false, true);
    }
}

echo json_encode($response);
?>