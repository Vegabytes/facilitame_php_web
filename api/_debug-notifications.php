<?php
/**
 * Debug: Verificar estado de notificaciones
 * Acceder via: /api/_debug-notifications
 */

// Este archivo usa autenticación normal para probar que funciona

try {
    // Si llegamos aquí, la autenticación funcionó
    $debug = [
        'auth_ok' => true,
        'user_id' => USER['id'] ?? 'NOT_SET',
        'user_role' => USER['role'] ?? 'NOT_SET',
        'is_asesoria' => defined('USER') && isset(USER['role']) ? (USER['role'] === 'asesoria') : false,
    ];

    // Test básico de BD
    global $pdo;
    $stmt = $pdo->query("SELECT 1 as test");
    $debug['db_ok'] = (bool) $stmt->fetch();

    // Contar notificaciones del usuario
    if (isset(USER['id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE receiver_id = ?");
        $stmt->execute([USER['id']]);
        $row = $stmt->fetch();
        $debug['notifications_count'] = $row['total'] ?? 0;
    }

    // Verificar que la función asesoria() funciona
    $debug['asesoria_function'] = function_exists('asesoria') ? asesoria() : 'FUNCTION_NOT_EXISTS';

    json_response("ok", "Debug completado", 200, $debug);

} catch (Throwable $e) {
    json_response("ko", "Error: " . $e->getMessage(), 500, [
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
}
