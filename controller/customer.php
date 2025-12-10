<?php
if (!admin() && !proveedor() && !asesoria() && !comercial()) {
    header("Location:home?r=2724205720");
    exit;
}

$info = [];
global $pdo;
$user = new User();

if (admin() || proveedor()) {
    $customer_id = intval($_GET["id"]);
    
    // =============================================
    // QUERY 1: Cliente + Comercial + Conteo (todo en uno)
    // =============================================
    $sql = "
        SELECT
            u.*,
            r.name AS role_name,
            pic.filename AS profile_picture,
            (SELECT COUNT(*) FROM requests req WHERE req.user_id = u.id AND req.deleted_at IS NULL) AS services_number,
            (SELECT CONCAT(sr.name, ' ', sr.lastname)
             FROM customers_sales_codes csc2
             INNER JOIN sales_codes sc2 ON sc2.id = csc2.sales_code_id
             INNER JOIN users sr ON sr.id = sc2.user_id
             WHERE csc2.customer_id = u.id
             LIMIT 1) AS sales_rep_name
        FROM users u
        INNER JOIN model_has_roles mhr ON mhr.model_id = u.id
        INNER JOIN roles r ON r.id = mhr.role_id
        LEFT JOIN user_pictures pic ON pic.user_id = u.id
        WHERE u.id = :customer_id
          AND mhr.role_id IN (4,5,6)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $info["customer"] = $stmt->fetch();
    
    if (empty($info["customer"])) {
        header("Location:/customers");
        exit;
    }
    
    // =============================================
    // QUERY 2: Solicitudes del cliente
    // =============================================
    if (admin()) {
        $sql = "
            SELECT req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            WHERE req.user_id = :customer_id
              AND req.deleted_at IS NULL
            ORDER BY req.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $info["requests"] = $stmt->fetchAll();
    } elseif (proveedor()) {
        // Proveedor solo ve requests de sus categorías
        $sql = "
            SELECT req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            WHERE req.user_id = :customer_id
              AND req.deleted_at IS NULL
              AND req.category_id IN (" . USER["categories"] . ")
            ORDER BY req.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $info["requests"] = $stmt->fetchAll();
    }

// =============================================
// COMERCIAL
// =============================================
} elseif (comercial()) {
    $customer_id = intval($_GET["id"]);
    $comercial_id = (int) USER['id'];
    
    // Verificar que el cliente pertenece al comercial
    $stmt = $pdo->prepare("
        SELECT 1 FROM customers_sales_codes csc
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        WHERE csc.customer_id = :customer_id
        AND sc.user_id = :comercial_id
        AND sc.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->bindValue(":comercial_id", $comercial_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        set_toastr("ko", "Cliente no encontrado");
        header("Location:/customers");
        exit;
    }
    
    // Cliente + Datos
    $sql = "
        SELECT
            u.*,
            r.name AS role_name,
            pic.filename AS profile_picture,
            (SELECT COUNT(*) FROM requests req WHERE req.user_id = u.id AND req.deleted_at IS NULL) AS services_number
        FROM users u
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles r ON r.id = mhr.role_id
        LEFT JOIN user_pictures pic ON pic.user_id = u.id
        WHERE u.id = :customer_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $info["customer"] = $stmt->fetch();
    
    if (empty($info["customer"])) {
        header("Location:/customers");
        exit;
    }
    
    // Solicitudes del cliente
    $sql = "
        SELECT req.*, cat.name AS category_name, sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE req.user_id = :customer_id
        AND req.deleted_at IS NULL
        ORDER BY req.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $info["requests"] = $stmt->fetchAll();

// =============================================
// ASESORIA
// =============================================
} elseif (asesoria()) {
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory_row = $stmt->fetch();
    
    if (!$advisory_row) {
        header("Location:/home");
        exit;
    }
    $advisory_id = $advisory_row['id'];
    $customer_id = intval($_GET["id"]);

    // Obtener datos del cliente
    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, pic.filename AS profile_picture,
               (SELECT COUNT(*) FROM advisory_appointments WHERE customer_id = u.id AND advisory_id = ?) AS services_number
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles r ON r.id = mhr.role_id
        LEFT JOIN user_pictures pic ON pic.user_id = u.id
        WHERE ca.advisory_id = ? AND u.id = ?
    ");
    $stmt->execute([$advisory_id, $advisory_id, $customer_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        header("Location:/customers");
        exit;
    }
    
    $info["customer"] = $customer;
    $info["advisory_id"] = $advisory_id;
    
    // =============================================
    // CITAS - Con todos los campos completos
    // =============================================
    $stmt = $pdo->prepare("
        SELECT 
            id,
            advisory_id,
            customer_id,
            type,
            department,
            preferred_time,
            specific_time,
            reason,
            status,
            scheduled_date,
            notes_advisory,
            created_at,
            updated_at
        FROM advisory_appointments
        WHERE customer_id = ? AND advisory_id = ?
        ORDER BY 
            CASE WHEN status = 'solicitado' THEN 0 
                 WHEN status = 'agendado' THEN 1 
                 ELSE 2 END,
            COALESCE(scheduled_date, created_at) DESC
    ");
    $stmt->execute([$customer_id, $advisory_id]);
    $info["appointments"] = $stmt->fetchAll();
    
    // =============================================
    // FACTURAS - Enviadas por este cliente
    // =============================================
    $stmt = $pdo->prepare("
        SELECT 
            id,
            customer_id,
            advisory_id,
            filename,
            original_name,
            mime_type,
            file_size,
            tag,
            notes,
            is_processed,
            created_at
        FROM advisory_invoices
        WHERE customer_id = ? AND advisory_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$customer_id, $advisory_id]);
    $info["invoices"] = $stmt->fetchAll();
    
    // =============================================
    // COMUNICADOS - Recibidos por este cliente
    // =============================================
    $stmt = $pdo->prepare("
        SELECT 
            ac.id,
            ac.subject,
            ac.message,
            ac.importance,
            ac.created_at,
            acr.is_read,
            acr.read_at
        FROM advisory_communications ac
        INNER JOIN advisory_communication_recipients acr ON acr.communication_id = ac.id
        WHERE acr.customer_id = ? AND ac.advisory_id = ?
        ORDER BY ac.created_at DESC
    ");
    $stmt->execute([$customer_id, $advisory_id]);
    $info["communications"] = $stmt->fetchAll();
    
    // =============================================
    // MENSAJES DE CHAT
    // =============================================
    $info["messages"] = get_advisory_messages($advisory_id, $customer_id);
    
    // Marcar mensajes como leídos
    $stmt = $pdo->prepare("
        UPDATE advisory_messages SET is_read = 1 
        WHERE advisory_id = ? AND customer_id = ? AND sender_type = 'customer'
    ");
    $stmt->execute([$advisory_id, $customer_id]);
    
    // =============================================
    // LEGACY: requests formateados (por compatibilidad)
    // =============================================
    $formatted_requests = [];
    foreach ($info["appointments"] as $apt) {
        $formatted_requests[] = [
            'id' => $apt['id'],
            'category_name' => 'Cita de Asesoría',
            'status' => $apt['status'],
            'request_date' => $apt['created_at'],
            'updated_at' => $apt['updated_at'] ?? $apt['created_at']
        ];
    }
    $info["requests"] = $formatted_requests;
}