<?php
/**
 * API: Crear usuario staff (comercial o colaborador)
 * POST /api/users-add
 */

use Ramsey\Uuid\Uuid;

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4001360000);
}

// Validar autenticación admin
if (!admin()) {
    json_response("ko", "No autorizado", 4011360000);
}

// Obtener datos
$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$code = trim($_POST['code'] ?? '');

// Validar tipo
if (!in_array($type, ['sales-rep', 'provider'])) {
    json_response("ko", "Tipo de usuario no válido", 4001360001);
}

// Validar campos requeridos
if (empty($name)) {
    json_response("ko", "El nombre es obligatorio", 4001360002);
}

if (empty($lastname)) {
    json_response("ko", "Los apellidos son obligatorios", 4001360003);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response("ko", "Email no válido", 4001360004);
}

// Validar código para comerciales
if ($type === 'sales-rep' && empty($code)) {
    json_response("ko", "El código de comercial es obligatorio", 4001360006);
}

$roleId = ($type === 'sales-rep') ? 7 : 2;
$roleName = ($type === 'sales-rep') ? 'comercial' : 'colaborador';

try {
    global $pdo;
    $db = $pdo;
    
    // Verificar email único
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL");
    $stmt->bindValue(":email", $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        json_response("ko", "Ya existe un usuario con ese email", 4001360010);
    }
    
    // Verificar código único (solo comerciales)
    if ($type === 'sales-rep') {
        $stmt = $db->prepare("SELECT id FROM sales_codes WHERE code = :code AND deleted_at IS NULL");
        $stmt->bindValue(":code", $code);
        $stmt->execute();
        if ($stmt->fetch()) {
            json_response("ko", "Ya existe un comercial con ese código", 4001360011);
        }
    }
    
    // Generar token de verificación
    $verificationToken = Uuid::uuid4()->toString();
    $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
    
    $db->beginTransaction();
    
    // Insertar usuario (sin contraseña)
    $stmt = $db->prepare("
        INSERT INTO users (name, lastname, email, phone, password, verification_token, token_expires_at, created_at, updated_at)
        VALUES (:name, :lastname, :email, :phone, '', :verification_token, :token_expires_at, NOW(), NOW())
    ");
    $stmt->bindValue(":name", $name);
    $stmt->bindValue(":lastname", $lastname);
    $stmt->bindValue(":email", $email);
    $stmt->bindValue(":phone", $phone);
    $stmt->bindValue(":verification_token", $verificationToken);
    $stmt->bindValue(":token_expires_at", $tokenExpiresAt);
    $stmt->execute();
    
    $userId = $db->lastInsertId();
    
    // Asignar rol
    $stmt = $db->prepare("
        INSERT INTO model_has_roles (role_id, model_type, model_id)
        VALUES (:role_id, 'App\\\\Models\\\\User', :model_id)
    ");
    $stmt->bindValue(":role_id", $roleId);
    $stmt->bindValue(":model_id", $userId);
    $stmt->execute();
    
    // Crear código de comercial si aplica
    if ($type === 'sales-rep') {
        $stmt = $db->prepare("
            INSERT INTO sales_codes (user_id, code, created_at, updated_at)
            VALUES (:user_id, :code, NOW(), NOW())
        ");
        $stmt->bindValue(":user_id", $userId);
        $stmt->bindValue(":code", $code);
        $stmt->execute();
    }
    
    $db->commit();
    
    // Registrar en log
    $logAction = ($type === 'sales-rep') ? 'sales_rep_create' : 'provider_create';
    app_log('user', $userId, $logAction, 'user', $userId, USER['id'], [
        'email' => $email,
        'type' => $type
    ]);
    
    // Enviar email de activación
    $activationLink = ROOT_URL . '/activate?token=' . $verificationToken;
    
    $emailSubject = "Activa tu cuenta de {$roleName} en Facilítame";
    $emailBody = "
        <p>Hola {$name},</p>
        <p>Se ha creado tu cuenta de <strong>{$roleName}</strong> en Facilítame.</p>
        <p>Para activar tu cuenta y establecer tu contraseña, haz clic en el siguiente enlace:</p>
        <p style='text-align: center; margin: 30px 0;'>
            <a href='{$activationLink}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                Activar mi cuenta
            </a>
        </p>
        <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
        <p><a href='{$activationLink}'>{$activationLink}</a></p>
        <p><strong>Este enlace expira en 48 horas.</strong></p>
        <p>Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>
        <p>Saludos,<br>El equipo de Facilítame</p>
    ";
    
    $emailSent = send_mail($email, $name, $emailSubject, $emailBody, 'user-add-' . $userId);
    
    $message = $emailSent 
        ? "Usuario creado. Se ha enviado un email de activación a {$email}" 
        : "Usuario creado, pero hubo un problema enviando el email de activación";
    
    json_response("ok", $message, 2001360001, [
        'user_id' => $userId,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error en users-add: " . $e->getMessage());
    json_response("ko", "Error al crear el usuario", 5001360001);
}