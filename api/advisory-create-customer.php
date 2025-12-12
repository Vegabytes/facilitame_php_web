<?php
header('Content-Type: application/json; charset=utf-8');

if (!asesoria()) {
    json_response('error', 'No autorizado', 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, codigo_identificacion FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('error', 'Asesoría no encontrada', 404);
}

$advisory_id = $advisory['id'];
$advisory_code = $advisory['codigo_identificacion'];

// Validar campos requeridos
$required = ['name', 'lastname', 'email', 'client_type'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        json_response('error', "El campo {$field} es obligatorio", 400);
    }
}

$name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
$lastname = htmlspecialchars(trim($_POST['lastname']), ENT_QUOTES, 'UTF-8');
$email = trim($_POST['email']);
$phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$nif_cif = htmlspecialchars(trim($_POST['nif_cif'] ?? ''), ENT_QUOTES, 'UTF-8');
$client_type = $_POST['client_type'];

// Validar NIF/CIF obligatorio
if (empty($nif_cif)) {
    json_response('error', 'El NIF/CIF es obligatorio', 400);
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response('error', 'Email inválido', 400);
}

// Verificar que el email no exista
$stmt = $pdo->prepare("SELECT id, name, lastname FROM users WHERE email = ?");
$stmt->execute([$email]);
$existing_user = $stmt->fetch();

if ($existing_user) {
    // Verificar si ya está vinculado a esta asesoría
    $stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
    $stmt->execute([$existing_user['id'], $advisory_id]);

    if ($stmt->fetch()) {
        json_response('error', 'Este cliente ya está vinculado a tu asesoría', 409);
    }

    // Ofrecer vincular el cliente existente
    json_response('exists', 'Ya existe un usuario con este email', 200, [
        'existing_user_id' => $existing_user['id'],
        'existing_user_name' => $existing_user['name'] . ' ' . $existing_user['lastname'],
        'email' => $email
    ]);
}

// Determinar subtipo según client_type
$subtype = '';
switch ($client_type) {
    case 'autonomo':
        $subtype = $_POST['autonomo_subtype'] ?? '';
        break;
    case 'empresa':
        $subtype = $_POST['empresa_subtype'] ?? '';
        break;
    case 'comunidad':
        $subtype = $_POST['comunidad_subtype'] ?? '';
        break;
    case 'asociacion':
        $subtype = $_POST['asociacion_subtype'] ?? '';
        break;
}

// Validar que se haya seleccionado subtipo
if (empty($subtype)) {
    json_response('error', 'Debes seleccionar el subtipo', 400);
}

// Determinar role_id según client_type
$role_map = [
    'autonomo' => 4,
    'empresa' => 5,
    'particular' => 6,
    'comunidad' => 9,
    'asociacion' => 10
];

$role_id = $role_map[$client_type] ?? 6;

// Generar token para verificación
$verification_token = bin2hex(random_bytes(32));
$token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

try {
    $pdo->beginTransaction();

    // 1. Crear usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name,
            lastname,
            email,
            phone,
            nif_cif,
            verification_token,
            token_expires_at,
            email_verified_at,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())
    ");

    $stmt->execute([
        $name,
        $lastname,
        $email,
        $phone,
        $nif_cif,
        $verification_token,
        $token_expires
    ]);

    $customer_id = $pdo->lastInsertId();

    // 2. Asignar rol
    $stmt = $pdo->prepare("
        INSERT INTO model_has_roles (role_id, model_type, model_id)
        VALUES (?, 'App\\\\Models\\\\User', ?)
    ");
    $stmt->execute([$role_id, $customer_id]);

    // 3. Vincular con asesoría
    $stmt = $pdo->prepare("
        INSERT INTO customers_advisories (customer_id, advisory_id, client_type, client_subtype, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$customer_id, $advisory_id, $client_type, $subtype]);

    $pdo->commit();

    // Sincronizar cliente con Inmatic si está configurado
    syncCustomerToInmatic($advisory_id, $customer_id, [
        'name' => $name . ' ' . $lastname,
        'email' => $email,
        'phone' => $phone,
        'nif_cif' => $nif_cif,
        'client_type' => $client_type
    ]);

    // Obtener nombre de la asesoría
    $query = "SELECT razon_social FROM advisories WHERE id = :advisory_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":advisory_id", $advisory['id']);
    $stmt->execute();
    $advisory_info = $stmt->fetch();

    $advisory_name = $advisory_info ? $advisory_info['razon_social'] : 'Tu asesoría';

    // Envío del email con el enlace de activación
    $data = [
        'name' => $name,
        'token' => $verification_token,
        'advisory_name' => $advisory_name
    ];

    ob_start();
    require(ROOT_DIR . "/email-templates/customer-activation.php");
    $body = ob_get_clean();

    $subject = "Activa tu cuenta de Facilítame";
    send_mail($email, $name, $subject, $body, 8844552211);

    // Notificar a la asesoría (confirmación in-app)
    notification_v2(
        $customer_id,
        USER['id'],
        null,
        'Cliente creado',
        "Has creado el cliente {$name} {$lastname} ({$email})",
        'Cliente creado correctamente',
        'notification-advisory-customer-created',
        [
            'customer_name' => $name . ' ' . $lastname,
            'customer_email' => $email,
            'client_type' => $client_type
        ]
    );

    json_response('ok', 'Cliente creado correctamente. Se ha enviado un email de activación.', 200, [
        'customer_id' => $customer_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    json_response('error', 'Error al crear el cliente: ' . $e->getMessage(), 500);
}
