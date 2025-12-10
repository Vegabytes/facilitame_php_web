<?php
/**
 * API: Verificar sesión actual
 * GET /api/verify-session
 *
 * Devuelve el user_id del token actual para detectar
 * cambios de sesión en el frontend
 */

// Este archivo se incluye desde bold.php que ya maneja auth
// Si llegamos aquí, el usuario está autenticado

json_response("ok", "Sesión válida", 200, [
    'user_id' => USER['id'],
    'user_name' => USER['name'],
    'user_role' => USER['role']
]);
