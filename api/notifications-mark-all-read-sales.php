<?php
/**
 * API: Marcar todas las notificaciones como leídas
 * Endpoint: /api/notifications-mark-all-read-sales
 * Method: POST
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4051358201);
}

if (!comercial()) {
    json_response("ko", "No autorizado", 4031358203);
}

global $pdo;
$comercial_id = (int) USER['id'];

try {
    // Obtener IDs de clientes del comercial
    $stmtCustomers = $pdo->prepare("
        SELECT DISTINCT csc.customer_id 
        FROM customers_sales_codes csc
        INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
        WHERE sc.user_id = ?
        AND sc.deleted_at IS NULL
    ");
    $stmtCustomers->execute([$comercial_id]);
    $customerIds = $stmtCustomers->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customerIds)) {
        json_response("ok", "No hay notificaciones", 9200101003, ['updated' => 0]);
    }
    
    $in_customers = implode(',', array_map('intval', $customerIds));
    
    // Marcar todas como leídas
    $sql = "
        UPDATE notifications n
        INNER JOIN requests req ON req.id = n.request_id
        SET n.status = 1
        WHERE req.user_id IN ($in_customers)
        AND n.status = 0
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    json_response("ok", "Se marcaron $updated notificaciones como leídas", 9200101004, ['updated' => $updated]);
    
} catch (Throwable $e) {
    error_log("Error en api-notifications-mark-all-read-sales: " . $e->getMessage());
    json_response("ko", $e->getMessage(), 9500101002);
}