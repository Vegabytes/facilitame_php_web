<?php
header('Content-Type: application/json');

if (!asesoria()) {
    json_response("ko", "No autorizado", 4001);
}

// Obtener el ID real de la asesoría (de la tabla advisories, no el user_id)
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_row = $stmt->fetch();
if (!$advisory_row) {
    json_response("ko", "Asesoría no encontrada", 4004);
}
$advisory_id = $advisory_row['id'];
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$importance = $_POST['importance'] ?? 'media';
$target_type = $_POST['target_type'] ?? 'all';
$selected_clients = $_POST['selected_clients'] ?? [];

if (empty($subject) || empty($message)) {
    json_response("ko", "Asunto y mensaje son obligatorios", 4002);
}

if (!in_array($importance, ['leve', 'media', 'importante'])) {
    $importance = 'media';
}

try {
    $pdo->beginTransaction();
    
    // Guardar comunicación
    $query = "INSERT INTO advisory_communications 
              (advisory_id, subject, message, importance, target_type) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$advisory_id, $subject, $message, $importance, $target_type]);
    $communication_id = $pdo->lastInsertId();
    
    // Obtener clientes según filtro
    // Usar tabla roles para obtener el tipo real de cliente (empresa, autonomo, particular)
    $query = "SELECT u.id, u.email, u.name, u.lastname, u.firebase_token, u.platform
              FROM users u
              INNER JOIN customers_advisories ca ON u.id = ca.customer_id
              INNER JOIN model_has_roles mhr ON mhr.model_id = u.id
              INNER JOIN roles r ON r.id = mhr.role_id
              WHERE ca.advisory_id = ?";
    $params = [$advisory_id];

    // Aplicar filtros
    if ($target_type === 'selected' && !empty($selected_clients)) {
        // Selección manual de clientes
        $selected_clients = array_map('intval', $selected_clients);
        $placeholders = implode(',', array_fill(0, count($selected_clients), '?'));
        $query .= " AND u.id IN ($placeholders)";
        $params = array_merge($params, $selected_clients);
    } elseif ($target_type !== 'all') {
        // Filtrar por rol: autonomo, empresa, particular
        $query .= " AND r.name = ?";
        $params[] = $target_type;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
    
    if (empty($customers)) {
        $pdo->rollBack();
        // Mensaje descriptivo según el filtro
        $tipo_mensaje = match($target_type) {
            'empresa' => 'No tienes clientes de tipo "Empresa" asignados a tu asesoría.',
            'autonomo' => 'No tienes clientes de tipo "Autónomo" asignados a tu asesoría.',
            'particular' => 'No tienes clientes de tipo "Particular" asignados a tu asesoría.',
            'selected' => 'No se seleccionaron clientes válidos.',
            default => 'No hay clientes que coincidan con el filtro seleccionado.'
        };
        json_response("ko", $tipo_mensaje, 4003);
    }
    
    // Preparar inserción de destinatarios
    $stmt_recipient = $pdo->prepare(
        "INSERT INTO advisory_communication_recipients (communication_id, customer_id) VALUES (?, ?)"
    );
    
    $sent_count = 0;
    $push_count = 0;
    
    // Obtener nombre de la asesoría
    $advisory_name = USER['name'] . ' ' . (USER['lastname'] ?? '');
    
    foreach ($customers as $customer) {
        // Guardar destinatario
        $stmt_recipient->execute([$communication_id, $customer['id']]);
        
        // Preparar email
        $to_email = $customer['email'];
        $to_name = ucwords($customer['name'] . ' ' . ($customer['lastname'] ?? ''));
        
        // Crear cuerpo del email
        $email_body = build_advisory_communication_email($to_name, $subject, $message, $importance, $advisory_name);
        
        // Enviar email (solo si importancia es media o importante)
        if ($importance !== 'leve') {
            $email_sent = send_mail($to_email, $to_name, $subject, $email_body, $communication_id, [], "Facilítame - Asesoría");
            if ($email_sent) {
                $sent_count++;
            }
        }
        
        // Enviar push notification
        if (!empty($customer['firebase_token']) && !empty($customer['platform'])) {
            $user_notification_info = [
                'firebase_token' => $customer['firebase_token'],
                'platform' => $customer['platform']
            ];
            send_notification($user_notification_info, $subject, "Nueva comunicación de tu asesoría", 0);
            $push_count++;
        }
    }
    
    $pdo->commit();
    
    $message_result = "Comunicación enviada a " . count($customers) . " clientes";
    if ($importance !== 'leve') {
        $message_result .= " ($sent_count emails enviados)";
    }
    
    json_response("ok", $message_result, 2001, [
        'communication_id' => $communication_id,
        'recipients_count' => count($customers),
        'emails_sent' => $sent_count,
        'push_sent' => $push_count
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en advisory-send-communication: " . $e->getMessage() . " | Line: " . $e->getLine());
    json_response("ko", "Error al enviar la comunicación: " . $e->getMessage(), 5001);
}


function build_advisory_communication_email($to_name, $subject, $message, $importance, $advisory_name) {
    $importance_label = '';
    $importance_color = '#64748b';
    
    switch ($importance) {
        case 'importante':
            $importance_label = '⚠️ IMPORTANTE';
            $importance_color = '#ef4444';
            break;
        case 'media':
            $importance_label = 'Información';
            $importance_color = '#eab308';
            break;
        case 'leve':
            $importance_label = 'Aviso';
            $importance_color = '#22c55e';
            break;
    }
    
    $formatted_message = nl2br(htmlspecialchars($message));
    
    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: linear-gradient(135deg, #00c2cb 0%, #009ba3 100%); padding: 20px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 24px;">Facilítame</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0;">Comunicación de tu Asesoría</p>
        </div>
        
        <div style="padding: 30px; background: #ffffff;">
            <p style="font-size: 16px; color: #1e293b;">
                <b>Hola <?php echo $to_name; ?>,</b>
            </p>
            
            <div style="background: <?php echo $importance_color; ?>15; border-left: 4px solid <?php echo $importance_color; ?>; padding: 12px 16px; margin: 20px 0;">
                <span style="color: <?php echo $importance_color; ?>; font-weight: 600; font-size: 14px;">
                    <?php echo $importance_label; ?>
                </span>
            </div>
            
            <h2 style="color: #1e293b; font-size: 20px; margin: 20px 0 10px 0;">
                <?php echo htmlspecialchars($subject); ?>
            </h2>
            
            <div style="color: #475569; font-size: 15px; line-height: 1.6; margin: 20px 0;">
                <?php echo $formatted_message; ?>
            </div>
            
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">
            
            <p style="color: #64748b; font-size: 14px;">
                Este mensaje ha sido enviado por <b><?php echo htmlspecialchars($advisory_name); ?></b> a través de Facilítame.
            </p>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo ROOT_URL; ?>" style="background: #00c2cb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                    Acceder a Facilítame
                </a>
            </div>
        </div>
        
        <div style="background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 12px;">
            <p style="margin: 0;">© <?php echo date('Y'); ?> Facilítame. Todos los derechos reservados.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}