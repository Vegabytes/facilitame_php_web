<?php
/**
 * API: api-users-available.php
 * Listar usuarios disponibles para asignar - ADMIN
 * 
 * GET params:
 * - role: filtrar por rol (opcional)
 */

if (!admin()) {
    json_response("ko", "No autorizado", 4031358200);
}

global $pdo;

$role = isset($_GET['role']) ? trim($_GET['role']) : '';

try {
    $sql = "SELECT id, name, lastname, email FROM users WHERE deleted_at IS NULL";
    $params = [];
    
    if (!empty($role)) {
        $sql .= " AND role = :role";
        $params[':role'] = $role;
    }
    
    $sql .= " ORDER BY name ASC, lastname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = [];
    foreach ($users as $u) {
        $formatted[] = [
            'id' => (int) $u['id'],
            'name' => trim(($u['name'] ?? '') . ' ' . ($u['lastname'] ?? '')),
            'email' => $u['email'] ?? ''
        ];
    }
    
    json_response("ok", "", 9200002000, ['data' => $formatted]);
    
} catch (Throwable $e) {
    error_log("Error en api-users-available: " . $e->getMessage());
    json_response("ko", "Error interno", 9500002000);
}