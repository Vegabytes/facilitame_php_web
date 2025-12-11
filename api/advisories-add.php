<?php
/**
 * API: Crear nueva asesoría (Admin)
 * POST /api/advisories-add
 *
 * Parámetros POST:
 * - razon_social (required): Nombre de la asesoría
 * - cif (required): CIF de la empresa
 * - email_empresa (required): Email de contacto
 * - direccion (optional): Dirección física
 * - telefono (optional): Teléfono de contacto
 * - plan (optional): Plan de suscripción (gratuito/basic/estandar/pro/premium)
 * - estado (optional): Estado inicial (pendiente/activo)
 * - user_name (optional): Nombre del usuario asociado
 * - user_email (optional): Email del usuario (si no existe, se crea)
 * - user_phone (optional): Teléfono del usuario
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031359100);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051359100);
}

global $pdo;

// Validar campos requeridos
$required = ['razon_social', 'cif', 'email_empresa'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        json_response("ko", "El campo $field es obligatorio", 4001359100);
    }
}

$razon_social = trim($_POST['razon_social']);
$cif = strtoupper(trim($_POST['cif']));
$email_empresa = strtolower(trim($_POST['email_empresa']));
$direccion = trim($_POST['direccion'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$plan = $_POST['plan'] ?? 'gratuito';
$estado = $_POST['estado'] ?? 'pendiente';

// Usuario opcional
$user_name = trim($_POST['user_name'] ?? '');
$user_email = strtolower(trim($_POST['user_email'] ?? ''));
$user_phone = trim($_POST['user_phone'] ?? '');

// Validar CIF formato
if (!preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $cif)) {
    json_response("ko", "Formato de CIF no válido", 4001359101);
}

// Validar email
if (!filter_var($email_empresa, FILTER_VALIDATE_EMAIL)) {
    json_response("ko", "Email de empresa no válido", 4001359102);
}

// Validar plan
$allowed_plans = ['gratuito', 'basic', 'estandar', 'pro', 'premium'];
if (!in_array($plan, $allowed_plans)) {
    $plan = 'gratuito';
}

// Validar estado
$allowed_status = ['pendiente', 'activo', 'suspendido'];
if (!in_array($estado, $allowed_status)) {
    $estado = 'pendiente';
}

try {
    // Verificar CIF único
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE cif = :cif AND deleted_at IS NULL");
    $stmt->execute([':cif' => $cif]);
    if ($stmt->fetch()) {
        json_response("ko", "Ya existe una asesoría con ese CIF", 4091359100);
    }

    $pdo->beginTransaction();

    $user_id = null;

    // Si se proporcionaron datos de usuario, crear o vincular
    if (!empty($user_email)) {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL");
        $stmt->execute([':email' => $user_email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Usuario existe - verificar que tiene rol de asesoría o asignárselo
            $user_id = $existingUser['id'];

            $stmt = $pdo->prepare("SELECT role_id FROM model_has_roles WHERE model_id = :user_id AND model_type = 'App\\\\Models\\\\User'");
            $stmt->execute([':user_id' => $user_id]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array(5, $roles)) { // 5 = rol asesoria
                $stmt = $pdo->prepare("INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (5, 'App\\\\Models\\\\User', :user_id)");
                $stmt->execute([':user_id' => $user_id]);
            }
        } else {
            // Crear nuevo usuario
            $verification_token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+48 hours'));

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, verification_token, token_expires_at, created_at, updated_at)
                VALUES (:name, :email, :phone, :token, :token_expires, NOW(), NOW())
            ");
            $stmt->execute([
                ':name' => $user_name ?: 'Usuario Asesoría',
                ':email' => $user_email,
                ':phone' => $user_phone,
                ':token' => $verification_token,
                ':token_expires' => $token_expires
            ]);
            $user_id = $pdo->lastInsertId();

            // Asignar rol de asesoría
            $stmt = $pdo->prepare("INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (5, 'App\\\\Models\\\\User', :user_id)");
            $stmt->execute([':user_id' => $user_id]);

            // Enviar email de activación
            $activationLink = ROOT_URL . '/activate?token=' . $verification_token;
            $emailSubject = "Activa tu cuenta de asesoría en Facilítame";
            $emailBody = "
                <p>Hola " . htmlspecialchars($user_name ?: 'Usuario') . ",</p>
                <p>Se ha creado tu cuenta de <strong>asesoría</strong> en Facilítame.</p>
                <p>Para activar tu cuenta y establecer tu contraseña, haz clic en el siguiente enlace:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$activationLink}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Activar mi cuenta
                    </a>
                </p>
                <p><strong>Este enlace expira en 48 horas.</strong></p>
                <p>Saludos,<br>El equipo de Facilítame</p>
            ";
            send_mail($user_email, $user_name ?: 'Usuario', $emailSubject, $emailBody, 'advisory-add-' . $user_id);
        }
    }

    // Generar código de identificación único
    $codigo_base = 'ASE-' . $cif;
    $codigo_identificacion = $codigo_base;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisories WHERE codigo_identificacion = :codigo");
    $stmt->execute([':codigo' => $codigo_identificacion]);
    $count = 0;
    while ($stmt->fetchColumn() > 0 && $count < 100) {
        $count++;
        $codigo_identificacion = $codigo_base . '-' . $count;
        $stmt->execute([':codigo' => $codigo_identificacion]);
    }

    // Insertar asesoría
    $stmt = $pdo->prepare("
        INSERT INTO advisories (
            user_id, razon_social, cif, email_empresa, direccion, telefono,
            plan, estado, codigo_identificacion, created_at, updated_at
        ) VALUES (
            :user_id, :razon_social, :cif, :email_empresa, :direccion, :telefono,
            :plan, :estado, :codigo_identificacion, NOW(), NOW()
        )
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':razon_social' => $razon_social,
        ':cif' => $cif,
        ':email_empresa' => $email_empresa,
        ':direccion' => $direccion,
        ':telefono' => $telefono,
        ':plan' => $plan,
        ':estado' => $estado,
        ':codigo_identificacion' => $codigo_identificacion
    ]);

    $advisory_id = $pdo->lastInsertId();

    $pdo->commit();

    // Log
    app_log('advisory', $advisory_id, 'advisory_create', 'advisory', $advisory_id, USER['id'], [
        'razon_social' => $razon_social,
        'cif' => $cif,
        'plan' => $plan
    ]);

    $message = "Asesoría creada correctamente";
    if ($user_id && empty($existingUser)) {
        $message .= ". Se ha enviado un email de activación al usuario.";
    }

    json_response("ok", $message, 2001359100, [
        'advisory_id' => $advisory_id,
        'codigo_identificacion' => $codigo_identificacion,
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en advisories-add: " . $e->getMessage());
    json_response("ko", "Error al crear la asesoría", 5001359100);
}
