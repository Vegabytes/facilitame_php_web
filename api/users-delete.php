<?php
/**
 * API: Eliminar usuario staff (soft delete)
 * POST /api/users-delete
 */

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response("ko", "Método no permitido", 4001370000);
}

// Validar autenticación admin
if (!admin()) {
    json_response("ko", "No autorizado", 4011370000);
}

$userId = intval($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    json_response("ko", "ID de usuario no válido", 4001370001);
}

try {
    global $pdo;
    $db = $pdo;
    
    // Verificar que el usuario existe y tiene rol 2 o 7
    $stmt = $db->prepare("
        SELECT u.id, u.email, r.role_id
        FROM users u
        INNER JOIN model_has_roles r ON r.model_id = u.id AND r.model_type = 'App\\\\Models\\\\User'
        WHERE u.id = :user_id 
        AND u.deleted_at IS NULL
        AND r.role_id IN (2, 7)
    ");
    $stmt->bindValue(":user_id", $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_response("ko", "Usuario no encontrado o no es un comercial/colaborador", 4041370001);
    }
    
    $roleId = $user['role_id'];
    $isCommercial = ($roleId == 7);
    
    $db->beginTransaction();
    
    // Soft delete del usuario
    $stmt = $db->prepare("UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = :user_id");
    $stmt->bindValue(":user_id", $userId);
    $stmt->execute();
    
    // Si es comercial, soft delete del código
    if ($isCommercial) {
        $stmt = $db->prepare("UPDATE sales_codes SET deleted_at = NOW(), updated_at = NOW() WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", $userId);
        $stmt->execute();
    }
    
    $db->commit();
    
    // Registrar en log
    $logAction = $isCommercial ? 'sales_rep_delete' : 'provider_delete';
    app_log('user', $userId, $logAction, 'user', $userId, USER['id'], [
        'email' => $user['email']
    ]);
    
    json_response("ok", "Usuario eliminado correctamente", 2001370001);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error en users-delete: " . $e->getMessage());
    json_response("ko", "Error al eliminar el usuario", 5001370001);
}