<?php
/**
 * API: api-dashboard-kpis-advisory.php
 * Endpoint consolidado para KPIs del dashboard de la asesoría
 */
if (!asesoria()) {
    json_response("ko", "No autorizado", 4031358510);
}

global $pdo;

try {
    // Obtener advisory_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisoryId = $stmt->fetchColumn();
    
    if (!$advisoryId) {
        json_response("ok", "", 9200010300, [
            'clientes' => 0,
            'citas' => 0,
            'facturas' => 0,
            'mensajes' => 0
        ]);
    }
    
    $query = "
        SELECT 
            -- Total clientes
            (
                SELECT COUNT(*)
                FROM customers_advisories
                WHERE advisory_id = :adv1
            ) AS clientes,
            
            -- Citas pendientes (solicitado o agendado)
            (
                SELECT COUNT(*)
                FROM advisory_appointments
                WHERE advisory_id = :adv2
                AND status IN ('solicitado', 'agendado')
            ) AS citas,
            
            -- Facturas sin procesar
            (
                SELECT COUNT(*)
                FROM advisory_invoices
                WHERE advisory_id = :adv3
                AND is_processed = 0
            ) AS facturas,
            
            -- Mensajes sin leer (de clientes)
            (
                SELECT COUNT(*)
                FROM advisory_messages
                WHERE advisory_id = :adv4
                AND sender_type = 'customer'
                AND is_read = 0
            ) AS mensajes
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':adv1', $advisoryId, PDO::PARAM_INT);
    $stmt->bindValue(':adv2', $advisoryId, PDO::PARAM_INT);
    $stmt->bindValue(':adv3', $advisoryId, PDO::PARAM_INT);
    $stmt->bindValue(':adv4', $advisoryId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    json_response("ok", "", 9200010300, [
        'clientes' => (int) $row['clientes'],
        'citas' => (int) $row['citas'],
        'facturas' => (int) $row['facturas'],
        'mensajes' => (int) $row['mensajes']
    ]);
    
} catch (Throwable $e) {
    error_log("Error en api-dashboard-kpis-advisory: " . $e->getMessage());
    json_response("ko", "Error interno", 9500010300);
}