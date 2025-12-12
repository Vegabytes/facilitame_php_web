<?php

use \Firebase\JWT\JWT;

// ========================================
// VALIDACION DE ENTRADA
// ========================================

$required_fields = ['email', 'password', 'confirm-password', 'name', 'role', 'region_code', 'phone'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        json_response("ko", "Faltan campos obligatorios", 400);
    }
}

$email = strtolower(trim($_POST["email"]));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response("ko", "El email no es valido", 400);
}
if (strlen($email) > 255) {
    json_response("ko", "El email es demasiado largo", 400);
}

if (strlen($_POST["password"]) < 8) {
    json_response("ko", "La contrasena debe tener al menos 8 caracteres", 400);
}
if (strlen($_POST["password"]) > 72) {
    json_response("ko", "La contrasena es demasiado larga", 400);
}
if (!preg_match('/[a-zA-Z]/', $_POST["password"]) || !preg_match('/\d/', $_POST["password"])) {
    json_response("ko", "La contrasena debe contener letras y numeros", 400);
}

if ($_POST["password"] !== $_POST["confirm-password"]) {
    json_response("ko", "Las contrasenas no coinciden", 4172375061);
}

$name = htmlspecialchars(trim($_POST["name"]), ENT_QUOTES, 'UTF-8');
if (strlen($name) > 100 || strlen($name) < 2) {
    json_response("ko", "El nombre debe tener entre 2 y 100 caracteres", 400);
}

$lastname = isset($_POST["lastname"]) && !empty($_POST["lastname"]) 
    ? htmlspecialchars(trim($_POST["lastname"]), ENT_QUOTES, 'UTF-8') 
    : NULL;
if ($lastname !== NULL && strlen($lastname) > 100) {
    json_response("ko", "El apellido es demasiado largo", 400);
}

$phone = preg_replace('/\s+/', '', $_POST["phone"]);
if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
    json_response("ko", "El telefono no es valido", 400);
}

$allowed_roles = ['autonomo', 'empresa', 'comunidad', 'asociacion', 'particular', 'asesoria'];
if (!in_array($_POST["role"], $allowed_roles)) {
    json_response("ko", "Tipo de cuenta no valido", 400);
}

$nif_cif = NULL;
if (isset($_POST["nif_cif"]) && !empty($_POST["nif_cif"])) {
    $nif_cif = strtoupper(trim($_POST["nif_cif"]));
    if (!preg_match('/^(?:[A-Z]\d{8}|\d{8}[A-Z]|[A-Z]\d{7}[A-Z])$/', $nif_cif)) {
        json_response("ko", "El NIF/CIF no es valido", 400);
    }
}

// Validacion especifica para ASESORIA
$cif_asesoria = NULL;
$razon_social = NULL;
$direccion = NULL;
$email_empresa = NULL;
$plan = "gratuito";

if ($_POST["role"] === "asesoria") {
    // CIF obligatorio
    if (!isset($_POST["cif"]) || empty($_POST["cif"])) {
        json_response("ko", "El CIF es obligatorio para asesorias", 400);
    }
    $cif_asesoria = strtoupper(trim($_POST["cif"]));
    if (!preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $cif_asesoria)) {
        json_response("ko", "El CIF no es valido", 400);
    }
    
    // Razon social obligatoria
    if (!isset($_POST["razon_social"]) || empty($_POST["razon_social"])) {
        json_response("ko", "La razon social es obligatoria", 400);
    }
    $razon_social = htmlspecialchars(trim($_POST["razon_social"]), ENT_QUOTES, 'UTF-8');
    if (strlen($razon_social) > 255) {
        json_response("ko", "La razon social es demasiado larga", 400);
    }
    
    // Direccion obligatoria
    if (!isset($_POST["direccion"]) || empty($_POST["direccion"])) {
        json_response("ko", "La direccion es obligatoria", 400);
    }
    $direccion = htmlspecialchars(trim($_POST["direccion"]), ENT_QUOTES, 'UTF-8');
    if (strlen($direccion) > 500) {
        json_response("ko", "La direccion es demasiado larga", 400);
    }
    
    // Email empresa obligatorio
    if (!isset($_POST["email_empresa"]) || empty($_POST["email_empresa"])) {
        json_response("ko", "El email de empresa es obligatorio", 400);
    }
    $email_empresa = strtolower(trim($_POST["email_empresa"]));
    if (!filter_var($email_empresa, FILTER_VALIDATE_EMAIL)) {
        json_response("ko", "El email de empresa no es valido", 400);
    }
    
    // Plan
    if (isset($_POST["plan"]) && !empty($_POST["plan"])) {
        $allowed_plans = ['gratuito', 'basic', 'estandar', 'pro', 'premium', 'enterprise'];
        if (!in_array($_POST["plan"], $allowed_plans)) {
            json_response("ko", "Plan no valido", 400);
        }
        $plan = $_POST["plan"];
    }
}

$region_code = isset($_POST["region_code"]) && !empty($_POST["region_code"]) 
    ? htmlspecialchars(trim($_POST["region_code"]), ENT_QUOTES, 'UTF-8') 
    : NULL;

// ========================================
// COMPROBAR EMAIL DUPLICADO
// ========================================

$query = "SELECT users.* FROM `users` WHERE users.email = :email";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":email", $email);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($res) !== 0) {
    json_response("ko", "El email ya esta registrado", 3024679095);
}

// Comprobar CIF duplicado si es asesoria
if ($_POST["role"] === "asesoria" && $cif_asesoria !== NULL) {
    $query = "SELECT id FROM `advisories` WHERE cif = :cif";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":cif", $cif_asesoria);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        json_response("ko", "Ya existe una asesoria con ese CIF", 400);
    }
}

// ========================================
// VALIDAR CODIGO DE VENTAS (opcional)
// ========================================

$sales_code_id = false;
if (isset($_POST["sales_code"]) && $_POST["sales_code"] != "") {
    $sales_code_id = check_sales_code($_POST["sales_code"]);
    if ($sales_code_id === false) {
        json_response("ko", "El codigo de comercial no es valido", 2707895073);
    }
}

$referal_id = (isset($_POST["referal_id"]) && $_POST["referal_id"] != "") ? intval($_POST["referal_id"]) : false;

// ========================================
// VALIDAR CODIGO DE ASESORIA (opcional)
// ========================================

$advisory_id = false;
if (isset($_POST["advisory_code"]) && $_POST["advisory_code"] != "") {
    $advisory_code = trim($_POST["advisory_code"]);
    
    $query = "SELECT id FROM `advisories` WHERE codigo_identificacion = :code AND estado = 'activo'";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":code", $advisory_code);
    $stmt->execute();
    $advisory = $stmt->fetch();
    
    if ($advisory === false) {
        json_response("ko", "El código de asesoría no es válido o no está activo", 2707895074);
    }
    $advisory_id = $advisory['id'];
}

// ========================================
// CREAR USUARIO
// ========================================

try {
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(40));
    $token_expires_at = (new DateTime())->modify("+72 hours")->format("Y-m-d H:i:s");

    $pdo->beginTransaction();

    $query = "SELECT id FROM `roles` WHERE name = :role_name";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":role_name", $_POST["role"]);
    $stmt->execute();
    $role_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($role_result)) {
        throw new Exception("Rol no encontrado");
    }
    $role_id = $role_result[0]["id"];

    $query = "INSERT INTO `users` SET 
    name = :name,
    lastname = :lastname,
    phone = :phone,
    email = :email,
    password = :password,
    nif_cif = :nif_cif,
    verification_token = :verification_token,
    token_expires_at = :token_expires_at,
    region_code = :region_code
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":name", $name);
    $stmt->bindValue(":lastname", $lastname);
    $stmt->bindValue(":phone", $phone);
    $stmt->bindValue(":email", $email);
    $stmt->bindValue(":password", $password_hash);
    $stmt->bindValue(":nif_cif", $nif_cif);
    $stmt->bindValue(":verification_token", $verification_token);
    $stmt->bindValue(":token_expires_at", $token_expires_at);
    $stmt->bindValue(":region_code", $region_code);

    $stmt->execute();
    $new_user_id = $pdo->lastInsertId();

    $query = "INSERT INTO `model_has_roles` SET role_id = :role_id, model_type = 'App\\Models\\User', model_id = :new_user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":role_id", $role_id);
    $stmt->bindValue(":new_user_id", $new_user_id);
    $stmt->execute();

    if ($referal_id !== false) {
        $query = "UPDATE `users` SET referal_id = :referal_id WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $new_user_id);
        $stmt->bindValue(":referal_id", $referal_id);
        $stmt->execute();
    }

    if ($sales_code_id !== false) {
        $query = "INSERT INTO `customers_sales_codes` SET 
        customer_id = :new_user_id,
        sales_code_id = :sales_code_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":new_user_id", $new_user_id);
        $stmt->bindValue(":sales_code_id", $sales_code_id);
        $stmt->execute();
    }

    // Si tiene código de asesoría -> vincular cliente con asesoría
    if ($advisory_id !== false) {
        // Guardar tipo de cliente (autonomo/empresa/particular)
        $client_type = $_POST["role"];

        // Guardar subtipo (tamaño del negocio/comunidad/asociación)
        $client_subtype = NULL;
        if (isset($_POST["client_subtype"]) && !empty($_POST["client_subtype"])) {
            $allowed_subtypes = [
                // Autónomo
                '1-10', '10-50', '50+',
                // Empresa
                '0-10', '10-50', '50-250', '250+',
                // Comunidad de Bienes
                'vecinos', 'propietarios',
                // Asociación
                'con_lucro', 'sin_lucro', 'federacion'
            ];
            if (in_array($_POST["client_subtype"], $allowed_subtypes)) {
                $client_subtype = $_POST["client_subtype"];
            }
        }

        $query = "INSERT INTO `customers_advisories` SET
        customer_id = :new_user_id,
        advisory_id = :advisory_id,
        client_type = :client_type,
        client_subtype = :client_subtype";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":new_user_id", $new_user_id);
        $stmt->bindValue(":advisory_id", $advisory_id);
        $stmt->bindValue(":client_type", $client_type);
        $stmt->bindValue(":client_subtype", $client_subtype);
        $stmt->execute();
    }

    // Si el rol es Asesoria -> insertamos en advisories
    if ($_POST["role"] === "asesoria") {
        // Generar codigo unico: ASE-{CIF}
        $codigo_identificacion = "ASE-" . $cif_asesoria;

        // Estado inicial (pendiente, activo, suspendido)
        if (!empty($_POST["sales_code"]) && $sales_code_id !== false) {
            $estado = "activo";
        } else {
            $estado = "pendiente";
        }

        $query = "INSERT INTO `advisories` 
              (user_id, cif, razon_social, direccion, email_empresa, plan, codigo_identificacion, estado, created_at) 
              VALUES 
              (:user_id, :cif, :razon_social, :direccion, :email_empresa, :plan, :codigo_identificacion, :estado, NOW())";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $new_user_id);
        $stmt->bindValue(":cif", $cif_asesoria);
        $stmt->bindValue(":razon_social", $razon_social);
        $stmt->bindValue(":direccion", $direccion);
        $stmt->bindValue(":email_empresa", $email_empresa);
        $stmt->bindValue(":plan", $plan);
        $stmt->bindValue(":codigo_identificacion", $codigo_identificacion);
        $stmt->bindValue(":estado", $estado);
        $stmt->execute();
        
        $new_advisory_id = $pdo->lastInsertId();
        
        // Si la asesoría usó código de comercial -> vincular
        if ($sales_code_id !== false) {
            $query = "INSERT INTO `advisories_sales_codes` SET 
                advisory_id = :advisory_id,
                sales_code_id = :sales_code_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":advisory_id", $new_advisory_id);
            $stmt->bindValue(":sales_code_id", $sales_code_id);
            $stmt->execute();
        }

        // Notificacion al admin si queda pendiente
        if ($estado === "pendiente") {
            notification_v2(
                $new_user_id,
                ADMIN_ID,
                null,
                'Nueva asesoría pendiente',
                "La asesoría {$razon_social} ({$cif_asesoria}) está pendiente de aprobación",
                'Nueva asesoría pendiente de aprobación',
                'notification-admin-advisory-pending',
                [
                    'advisory_name' => $razon_social,
                    'advisory_cif' => $cif_asesoria,
                    'advisory_email' => $email_empresa,
                    'user_name' => $name . ' ' . $lastname
                ]
            );
        }
    }

    $pdo->commit();

    app_log("customer", $new_user_id, "sign_up", "customer", $new_user_id, $new_user_id);

    // Envio del email con el enlace de activacion
    ob_start();
?>
    <p style="font-size:1.2rem"><b>Bienvenido <?php echo $name; ?> a Facilitame</b></p>
    <p>Es un placer tenerte con nosotros!</p>
    <p>Con Facilitame podras ahorrar y simplificar la gestion de todos tus servicios en un solo lugar.</p>
    <p>Para empezar a disfrutar de todas las ventajas, por favor verifica tu cuenta:</p>
    <p><b><a target="_blank" href="<?php echo ROOT_URL ?>/activate?token=<?php echo $verification_token ?>">Verifica tu cuenta aqui</a></b></p>
    <p>A partir de ahora, cuentas con nuestro equipo para cualquier consulta o gestion.</p>
    <br>
    <p>Atentamente,<br><b>El Equipo de Facilitame</b></p>
<?php
    $body = ob_get_clean();
    $subject = "Activa tu cuenta de Facilitame";
    $data["send"] = send_mail($email, $name, $subject, $body, 3869343253);

    $message = "Cuenta creada!<br><br>Te hemos enviado un email con un enlace para que la actives cuanto antes,<br><b>comprueba tu correo!</b><br><br>Y, por si acaso,<br><b>revisa tambien la carpeta de spam.</b>";
    json_response("ok", $message, 599904633);
} catch (Exception $e) {
    $pdo->rollBack();
    json_response("ko", "Error: " . $e->getMessage() . " en línea " . $e->getLine(), 3031435312);
}
?>