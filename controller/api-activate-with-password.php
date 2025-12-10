<?php
global $pdo;

// Validar token
if (empty($_POST['token'])) {
    json_response('ko', 'Token no válido', 400);
}

// Validar contraseñas
if (empty($_POST['password']) || empty($_POST['password_confirm'])) {
    json_response('ko', 'Debes establecer una contraseña', 400);
}

if ($_POST['password'] !== $_POST['password_confirm']) {
    json_response('ko', 'Las contraseñas no coinciden', 400);
}

if (strlen($_POST['password']) < 8) {
    json_response('ko', 'La contraseña debe tener mínimo 8 caracteres', 400);
}

if (!preg_match('/[a-zA-Z]/', $_POST['password']) || !preg_match('/\d/', $_POST['password'])) {
    json_response('ko', 'La contraseña debe contener letras y números', 400);
}

try {
    $pdo->beginTransaction();
    
    // Buscar usuario por token
    $query = "SELECT * FROM `users` WHERE verification_token = :token";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":token", $_POST['token']);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response('ko', 'Token no válido o expirado', 404);
    }
    
    // Verificar si ya fue activado
    if (!is_null($user["email_verified_at"])) {
        json_response('ko', 'Esta cuenta ya ha sido activada', 400);
    }
    
    // Verificar expiración
    $now = new DateTime();
    $token_expires_at = new DateTime($user["token_expires_at"]);
    
    if ($now > $token_expires_at) {
        json_response('ko', 'El enlace de activación ha caducado', 400);
    }
    
    // Hash de la contraseña
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Actualizar usuario: establecer contraseña y activar cuenta
    $query = "UPDATE `users` SET 
              password = :password,
              email_verified_at = NOW(),
              updated_at = NOW()
              WHERE verification_token = :token";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":password", $password_hash);
    $stmt->bindValue(":token", $_POST['token']);
    $stmt->execute();
    
    // Log
    $query = "INSERT INTO `log` SET 
              target_type = :target_type,
              target_id = :target_id,
              event = :event,
              link_type = :link_type,
              link_id = :link_id,
              triggered_by = :triggered_by";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":target_type", "customer");
    $stmt->bindValue(":target_id", $user["id"]);
    $stmt->bindValue(":event", "activate");
    $stmt->bindValue(":link_type", "customer");
    $stmt->bindValue(":link_id", $user["id"]);
    $stmt->bindValue(":triggered_by", $user["id"]);
    $stmt->execute();
    
    $pdo->commit();
    
    json_response('ok', '¡Cuenta activada! Ya puedes iniciar sesión', 200);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error activating account: " . $e->getMessage());
    json_response('ko', 'Error al activar la cuenta', 500);
}