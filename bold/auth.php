<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

if (!isset($_COOKIE['auth_token']) && !isset($_POST["auth_token"]))
{
    if (IS_MOBILE_APP)
    {
        json_response("logout", "", 2195003511);
    }
    set_toastr("ko", "Debes iniciar sesión");
    header('Location: login');
    exit;
}

$jwt = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : $_POST["auth_token"];
try
{
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));

    // Calcula el tiempo restante antes de la expiración
    $timeLeft = $decoded->exp - time();

    // Si quedan menos de 10 minutos para la expiración, renovar el token
    if ($timeLeft < 600) 
    {
        $issued_at = time();
        $expiration_time = $issued_at + 3600;  // Renovar por 1 hora más
        $payload = array(
            'role' => $decoded->role,
            'user_id' => $decoded->user_id,
            'iat' => $issued_at,
            'exp' => $expiration_time
        );
        $new_jwt = JWT::encode($payload, JWT_SECRET, "HS256");
        setcookie("auth_token", $new_jwt, time() + 3600, "/", "", false, true); // Configurada como HTTP only
    }    

    $query = "SELECT users.*, pictures.filename AS profile_picture
    FROM `users`
    LEFT JOIN user_pictures pictures ON pictures.user_id = users.id
    WHERE 1
    AND users.id = :user_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $decoded->user_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user["profile_picture"] == "")
    {
        $user["profile_picture"] = "profile-default.jpg";
    }

    if ($decoded->role === "proveedor")
    {
        define("USER", [
            "role" => $decoded->role,
            "view" => (in_array($decoded->role, ["autonomo", "empresa", "particular"])) ? "cliente" : $decoded->role,
            "id" => $decoded->user_id,
            "name" => $user["name"],
            "lastname" => $user["lastname"],
            "email" => $user["email"],
            "profile_picture" => $user["profile_picture"],
            "phone" => $user["phone"],
            "categories" => get_provider_categories($decoded->user_id, $decoded->role)
        ]);
    }
    else
    {                
        define("USER", [
            "role" => $decoded->role,
            "view" => (in_array($decoded->role, ["autonomo", "empresa", "particular"])) ? "cliente" : $decoded->role,
            "id" => $decoded->user_id,
            "name" => $user["name"],
            "lastname" => $user["lastname"],
            "email" => guest($user) ? "" : $user["email"],
            "profile_picture" => $user["profile_picture"],
            "phone" => $user["phone"]
        ]);
    }

    define ("NOTIFICATIONS", get_notifications());

    unset($user);
}
catch (Exception $e)
{
    if (defined('DEBUG') && DEBUG)
    {
        echo (1690695852 . "<br>"); // enl
        exit($e->getFile() . " - " . $e->getLine() . " - " . $e->getMessage());
    }
    header('Location: login?4033197217');
    exit;
}