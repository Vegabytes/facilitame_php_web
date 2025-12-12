<?php
/**
 * API: Vincular cliente con asesoría usando código
 * POST /api/customer-link-advisory
 *
 * Permite a clientes existentes (autónomos o empresas) vincularse
 * con una asesoría usando el código de identificación.
 *
 * Parámetros POST:
 * - advisory_code (required): Código de identificación de la asesoría
 * - client_type (optional): autonomo, empresa, comunidad, asociacion
 * - client_subtype (optional): Subtipo específico
 */

// Solo clientes pueden usar esta API
if (!cliente()) {
    json_response("ko", "No autorizado", 4031360100);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051360100);
}

global $pdo;

$advisory_code = isset($_POST['advisory_code']) ? trim($_POST['advisory_code']) : '';

if (empty($advisory_code)) {
    json_response("ko", "El código de asesoría es obligatorio", 4001360100);
}

$customer_id = USER['id'];

try {
    // Verificar que el código de asesoría existe y está activo
    $stmt = $pdo->prepare("
        SELECT id, razon_social, user_id, plan
        FROM advisories
        WHERE codigo_identificacion = :code
        AND estado = 'activo'
        AND deleted_at IS NULL
    ");
    $stmt->execute([':code' => $advisory_code]);
    $advisory = $stmt->fetch();

    if (!$advisory) {
        json_response("ko", "El código de asesoría no es válido o la asesoría no está activa", 4041360100);
    }

    $advisory_id = $advisory['id'];
    $advisory_name = $advisory['razon_social'];

    // Verificar que el cliente no está ya vinculado
    $stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = :customer_id AND advisory_id = :advisory_id");
    $stmt->execute([':customer_id' => $customer_id, ':advisory_id' => $advisory_id]);

    if ($stmt->fetch()) {
        json_response("ko", "Ya estás vinculado a esta asesoría", 4091360100);
    }

    // Obtener datos del cliente
    $stmt = $pdo->prepare("SELECT name, lastname, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch();

    // Determinar tipo de cliente
    $client_type = isset($_POST['client_type']) ? trim($_POST['client_type']) : 'autonomo';
    $allowed_types = ['autonomo', 'empresa', 'comunidad', 'asociacion'];
    if (!in_array($client_type, $allowed_types)) {
        $client_type = 'autonomo';
    }

    $client_subtype = isset($_POST['client_subtype']) ? trim($_POST['client_subtype']) : null;

    $pdo->beginTransaction();

    // Vincular cliente con asesoría
    $stmt = $pdo->prepare("
        INSERT INTO customers_advisories (customer_id, advisory_id, client_type, client_subtype, created_at)
        VALUES (:customer_id, :advisory_id, :client_type, :client_subtype, NOW())
    ");
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':advisory_id' => $advisory_id,
        ':client_type' => $client_type,
        ':client_subtype' => $client_subtype
    ]);

    $pdo->commit();

    // Sincronizar con Inmatic si la asesoría lo tiene configurado
    $stmt = $pdo->prepare("SELECT name, lastname, email, phone, nif_cif FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer_full = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer_full) {
        syncCustomerToInmatic($advisory_id, $customer_id, [
            'name' => $customer_full['name'] . ' ' . $customer_full['lastname'],
            'email' => $customer_full['email'],
            'phone' => $customer_full['phone'] ?? '',
            'nif_cif' => $customer_full['nif_cif'] ?? '',
            'client_type' => $client_type
        ]);
    }

    // Log
    app_log('customer', $customer_id, 'customer_link_advisory', 'advisory', $advisory_id, $customer_id, [
        'advisory_name' => $advisory_name,
        'advisory_code' => $advisory_code
    ]);

    // Notificar a la asesoría
    if ($advisory['user_id']) {
        notification_v2(
            $customer_id,
            $advisory['user_id'],
            null,
            'Nuevo cliente vinculado',
            "{$customer['name']} {$customer['lastname']} se ha vinculado a tu asesoría usando el código {$advisory_code}",
            'Nuevo cliente vinculado a tu asesoría',
            'notification-advisory-customer-linked',
            [
                'customer_name' => $customer['name'] . ' ' . $customer['lastname'],
                'customer_email' => $customer['email'],
                'advisory_name' => $advisory_name
            ]
        );
    }

    // Enviar email de confirmación al cliente
    $data = [
        'name' => $customer['name'],
        'advisory_name' => $advisory_name
    ];

    ob_start();
    if (file_exists(ROOT_DIR . "/email-templates/customer-linked-advisory.php")) {
        require(ROOT_DIR . "/email-templates/customer-linked-advisory.php");
    } else {
        ?>
        <p>Hola <?php echo htmlspecialchars($data['name']); ?>,</p>
        <p>Te has vinculado correctamente con <strong><?php echo htmlspecialchars($data['advisory_name']); ?></strong>.</p>
        <p>A partir de ahora podrás:</p>
        <ul>
            <li>Enviar facturas a tu asesoría</li>
            <li>Solicitar citas y reuniones</li>
            <li>Comunicarte directamente con ellos</li>
        </ul>
        <p>Saludos,<br>El equipo de Facilítame</p>
        <?php
    }
    $body = ob_get_clean();

    $subject = "Te has vinculado a " . $advisory_name;
    send_mail($customer['email'], $customer['name'], $subject, $body, 'customer-link-advisory-' . $customer_id);

    json_response("ok", "Te has vinculado correctamente a " . $advisory_name, 2001360100, [
        'advisory_id' => $advisory_id,
        'advisory_name' => $advisory_name
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en customer-link-advisory: " . $e->getMessage());
    json_response("ko", "Error al vincular con la asesoría", 5001360100);
}
