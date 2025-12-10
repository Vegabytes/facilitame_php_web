<?php
/**
 * Controller: Comunicaciones
 * - Para CLIENTE: Muestra las comunicaciones recibidas de su asesoría
 * - Para ASESORÍA: Muestra el historial de comunicaciones enviadas
 */
global $pdo;
$currentPage = 'communications';

// === ASESORÍA ===
if (asesoria()) {
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
    $stmt->execute([USER['id']]);
    $advisory = $stmt->fetch();
    
    if (!$advisory) {
        header('Location: /home');
        exit;
    }
    
    $advisory_id = $advisory['id'];
    
    // Obtener clientes para el modal de envío
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.lastname, u.email
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        WHERE ca.advisory_id = ?
        ORDER BY u.name, u.lastname
    ");
    $stmt->execute([$advisory_id]);
    $clients = $stmt->fetchAll();
    
    $info = compact("advisory_id", "clients");
    return; // Bold cargará automáticamente /pages/asesoria/communications.php
}

// === CLIENTE ===
if (cliente()) {
    $stmt = $pdo->prepare("SELECT advisory_id FROM customers_advisories WHERE customer_id = ?");
    $stmt->execute([USER['id']]);
    $customer_advisory = $stmt->fetch();
    
    if (!$customer_advisory) {
        header('Location: /home');
        exit;
    }
    
    $advisory_id = $customer_advisory['advisory_id'];
    
    // Marcar comunicaciones como leídas
    try {
        $stmt = $pdo->prepare("
            UPDATE advisory_communication_recipients 
            SET is_read = 1, read_at = NOW() 
            WHERE customer_id = ? AND is_read = 0
        ");
        $stmt->execute([USER['id']]);
    } catch (PDOException $e) {
        error_log("Error marking communications as read: " . $e->getMessage());
    }
    
    // Obtener información de la asesoría
    $stmt = $pdo->prepare("
        SELECT a.id, a.razon_social, u.name, u.email, u.phone 
        FROM advisories a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.id = ?
    ");
    $stmt->execute([$advisory_id]);
    $advisory = $stmt->fetch();
    
    $info = compact("advisory_id", "advisory");
    return; // Bold cargará automáticamente /pages/cliente/communications.php
}

// Si no es ni cliente ni asesoría
header('Location: /home');
exit;