<?php
/**
 * API: api-advisory-detail.php
 * Detalle de una asesoría para ADMIN
 * 
 * GET params:
 * - id: ID de la asesoría
 */
if (!admin()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    json_response("ko", "ID requerido", 4001358200);
}

try {
    // Datos básicos de la asesoría
    $sql = "
        SELECT 
            a.*,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS user_name,
            u.email AS user_email,
            u.phone AS user_phone
        FROM advisories a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.id = :id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $advisory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 4041358200);
    }
    
    // Estadísticas por separado (más eficiente)
    
    // Total clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers_advisories WHERE advisory_id = :id");
    $stmt->execute([':id' => $id]);
    $total_customers = (int) $stmt->fetchColumn();
    
    // Citas pendientes (solicitado o agendado)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = :id AND status IN ('solicitado', 'agendado')");
    $stmt->execute([':id' => $id]);
    $pending_appointments = (int) $stmt->fetchColumn();
    
    // Total citas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = :id");
    $stmt->execute([':id' => $id]);
    $total_appointments = (int) $stmt->fetchColumn();
    
    // Total comunicaciones
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_communications WHERE advisory_id = :id");
    $stmt->execute([':id' => $id]);
    $total_communications = (int) $stmt->fetchColumn();
    
    // Facturas totales
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_invoices WHERE advisory_id = :id");
    $stmt->execute([':id' => $id]);
    $total_invoices = (int) $stmt->fetchColumn();
    
    // Facturas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_invoices WHERE advisory_id = :id AND is_processed = 0");
    $stmt->execute([':id' => $id]);
    $pending_invoices = (int) $stmt->fetchColumn();
    
    // Mensajes sin leer (de clientes a la asesoría)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_messages WHERE advisory_id = :id AND sender_type = 'customer' AND is_read = 0");
    $stmt->execute([':id' => $id]);
    $unread_messages = (int) $stmt->fetchColumn();
    
    $data = [
        'id' => (int) $advisory['id'],
        'razon_social' => $advisory['razon_social'] ?? '',
        'cif' => $advisory['cif'] ?? '',
        'email_empresa' => $advisory['email_empresa'] ?? '',
        'direccion' => $advisory['direccion'] ?? '',
        'codigo_identificacion' => $advisory['codigo_identificacion'] ?? '',
        'plan' => $advisory['plan'] ?? 'gratuito',
        'estado' => $advisory['estado'] ?? 'pendiente',
        'created_at' => $advisory['created_at'] ? fdate($advisory['created_at']) : '-',
        'user_id' => $advisory['user_id'] ? (int) $advisory['user_id'] : null,
        'user_name' => trim($advisory['user_name'] ?? ''),
        'user_email' => $advisory['user_email'] ?? '',
        'user_phone' => $advisory['user_phone'] ?? '',
        'stats' => [
            'total_customers' => $total_customers,
            'pending_appointments' => $pending_appointments,
            'total_appointments' => $total_appointments,
            'total_communications' => $total_communications,
            'total_invoices' => $total_invoices,
            'pending_invoices' => $pending_invoices,
            'unread_messages' => $unread_messages
        ]
    ];
    
    json_response("ok", "", 9200002000, $data);
    
} catch (Throwable $e) {
    error_log("Error en api-advisory-detail: " . $e->getMessage());
    json_response("ko", "Error interno", 9500002000);
}