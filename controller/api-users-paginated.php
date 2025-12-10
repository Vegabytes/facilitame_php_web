<?php
/**
 * API: Listar usuarios staff paginados (comerciales o colaboradores)
 * GET /api/users-paginated
 */

// Validar autenticación admin
if (!admin()) {
    json_response("ko", "No autorizado", 4011380000);
}

$type = $_GET['type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(100, intval($_GET['limit'] ?? 25)));
$search = trim($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;

// Validar tipo y asignar role_id
if ($type === 'sales-rep') {
    $roleId = 7;
} elseif ($type === 'provider') {
    $roleId = 2;
} else {
    json_response("ko", "Tipo de usuario no válido", 4001380001);
}

try {
    global $pdo;
    $db = $pdo;
    
    $params = [":role_id" => $roleId];
    $searchCondition = "";
    
    // Agregar búsqueda si existe
    if (!empty($search)) {
        $searchCondition = " AND (
            CAST(u.id AS CHAR) LIKE :search
            OR CONCAT(u.name, ' ', u.lastname) LIKE :search
            OR u.email LIKE :search
            OR u.phone LIKE :search
        )";
        $params[":search"] = '%' . $search . '%';
    }
    
    // Contar total
    $countQuery = "
        SELECT COUNT(DISTINCT u.id)
        FROM users u
        INNER JOIN model_has_roles r ON r.model_id = u.id AND r.model_type = 'App\\\\Models\\\\User'
        WHERE r.role_id = :role_id
        AND u.deleted_at IS NULL
        {$searchCondition}
    ";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    
    // Obtener usuarios
    $dataQuery = "
        SELECT u.id, u.name, u.lastname, u.email, u.phone, u.created_at, u.email_verified_at
        FROM users u
        INNER JOIN model_has_roles r ON r.model_id = u.id AND r.model_type = 'App\\\\Models\\\\User'
        WHERE r.role_id = :role_id
        AND u.deleted_at IS NULL
        {$searchCondition}
        ORDER BY u.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $db->prepare($dataQuery);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear respuesta
    $formattedUsers = array_map(function($user) {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at'],
            'is_active' => !empty($user['email_verified_at'])
        ];
    }, $users);
    
    json_response("ok", "Usuarios obtenidos", 2001380001, [
        'data' => $formattedUsers,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en users-paginated: " . $e->getMessage());
    json_response("ko", "Error al obtener los usuarios", 5001380001);
}