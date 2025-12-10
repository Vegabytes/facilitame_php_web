<?php
/**
 * API: advisory-detail.php
 * Detalle de una asesoría para ADMIN
 * 
 * GET params:
 * - id: ID de la asesoría
 */

// Verificar autorización
if (!admin()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    json_response("ko", "ID requerido", 4001358200);
}

try {
    // Consulta base de la asesoría
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.razon_social,
            a.cif,
            a.email_empresa,
            a.direccion,
            a.codigo_identificacion,
            a.plan,
            a.estado,
            a.created_at,
            a.user_id,
            CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS user_name,
            u.email AS user_email
        FROM advisories a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $advisory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada", 4041358200);
    }
    
    // Inicializar estadísticas
    $total_customers = 0;
    $pending_appointments = 0;
    $total_communications = 0;
    $total_invoices = 0;
    $pending_invoices = 0;
    
    // Contar clientes vinculados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers_advisories WHERE advisory_id = ?");
    $stmt->execute([$id]);
    $total_customers = (int) $stmt->fetchColumn();
    
    // Contar citas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = ? AND status = 'solicitado'");
    $stmt->execute([$id]);
    $pending_appointments = (int) $stmt->fetchColumn();
    
    // Contar comunicaciones
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_communications WHERE advisory_id = ?");
    $stmt->execute([$id]);
    $total_communications = (int) $stmt->fetchColumn();
    
    // Contar facturas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_invoices WHERE advisory_id = ?");
    $stmt->execute([$id]);
    $total_invoices = (int) $stmt->fetchColumn();
    
    // Facturas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_invoices WHERE advisory_id = ? AND is_processed = 0");
    $stmt->execute([$id]);
    $pending_invoices = (int) $stmt->fetchColumn();
    
    // Formatear fecha
    $created_at_formatted = '-';
    if (!empty($advisory['created_at'])) {
        $created_at_formatted = date('d/m/Y', strtotime($advisory['created_at']));
    }
    
    // Construir respuesta
    $data = [
        'id' => (int) $advisory['id'],
        'razon_social' => $advisory['razon_social'] ?? '',
        'cif' => $advisory['cif'] ?? '',
        'email_empresa' => $advisory['email_empresa'] ?? '',
        'direccion' => $advisory['direccion'] ?? '',
        'codigo_identificacion' => $advisory['codigo_identificacion'] ?? '',
        'plan' => $advisory['plan'] ?? 'basico',
        'estado' => $advisory['estado'] ?? 'activo',
        'created_at' => $created_at_formatted,
        'user_id' => !empty($advisory['user_id']) ? (int) $advisory['user_id'] : null,
        'user_name' => trim($advisory['user_name'] ?? ''),
        'user_email' => $advisory['user_email'] ?? '',
        'stats' => [
            'total_customers' => $total_customers,
            'pending_appointments' => $pending_appointments,
            'total_communications' => $total_communications,
            'total_invoices' => $total_invoices,
            'pending_invoices' => $pending_invoices
        ]
    ];
    
    json_response("ok", "", 9200002000, $data);
    
} catch (PDOException $e) {
    error_log("advisory-detail PDOException: " . $e->getMessage());
    json_response("ko", "Error de base de datos: " . $e->getMessage(), 9500002001);
} catch (Throwable $e) {
    error_log("advisory-detail Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    json_response("ko", "Error: " . $e->getMessage(), 9500002000);
}