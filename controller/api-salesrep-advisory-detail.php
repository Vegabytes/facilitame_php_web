<?php
/**
 * API: Detalle de asesoría para comercial
 * Endpoint: /api-salesrep-advisory-detail
 * Method: GET
 * Params: id (advisory_id)
 */

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358312);
}

global $pdo;
$salesUserId = USER['id'];

$advisory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$advisory_id) {
    json_response("ko", "ID de asesoría requerido", 4001358312);
}

try {
    // Verificar que la asesoría pertenece al comercial y obtener datos
    $query = "
        SELECT 
            a.id,
            a.cif,
            a.razon_social,
            a.direccion,
            a.email_empresa,
            a.plan,
            a.estado,
            a.codigo_identificacion,
            DATE_FORMAT(a.created_at, '%d/%m/%Y') as created_at,
            u.id as user_id,
            CONCAT(u.name, ' ', COALESCE(u.lastname, '')) as user_name,
            u.email as user_email,
            u.phone as user_phone
        FROM advisories a
        INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
        INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.id = :advisory_id AND sc.user_id = :sales_user_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':advisory_id', $advisory_id, PDO::PARAM_INT);
    $stmt->bindValue(':sales_user_id', $salesUserId, PDO::PARAM_INT);
    $stmt->execute();
    
    $advisory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisory) {
        json_response("ko", "Asesoría no encontrada o sin acceso", 4041358312);
    }
    
    // Obtener estadísticas
    $stats = [];
    
    // Total clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers_advisories WHERE advisory_id = ?");
    $stmt->execute([$advisory_id]);
    $stats['total_customers'] = (int) $stmt->fetchColumn();
    
    // Citas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = ? AND status IN ('solicitado', 'agendado')");
    $stmt->execute([$advisory_id]);
    $stats['pending_appointments'] = (int) $stmt->fetchColumn();
    
    // Total citas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = ?");
    $stmt->execute([$advisory_id]);
    $stats['total_appointments'] = (int) $stmt->fetchColumn();
    
    $advisory['stats'] = $stats;
    
    json_response("ok", "", 9200010102, $advisory);
    
} catch (Exception $e) {
    error_log("Error en api-salesrep-advisory-detail: " . $e->getMessage());
    json_response("ko", "Error al obtener asesoría", 9500010102);
}