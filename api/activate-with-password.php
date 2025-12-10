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
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->execute([$_POST['token']]);
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
    $stmt = $pdo->prepare("UPDATE users SET 
        password = ?,
        email_verified_at = NOW(),
        updated_at = NOW()
        WHERE verification_token = ?");
    $stmt->execute([$password_hash, $_POST['token']]);
    
    // Log
    $stmt = $pdo->prepare("INSERT INTO log SET 
        target_type = 'customer',
        target_id = ?,
        event = 'activate',
        link_type = 'customer',
        link_id = ?,
        triggered_by = ?");
    $stmt->execute([$user["id"], $user["id"], $user["id"]]);
    
    $pdo->commit();
    
    json_response('ok', '¡Cuenta activada! Ya puedes iniciar sesión', 200);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error activating account: " . $e->getMessage());
    json_response('ko', 'Error al activar la cuenta', 500);
}