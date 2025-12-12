<?php
/**
 * Debug: Verificar estado de sesión
 * Acceder via: /api/_debug-session
 *
 * Este archivo NO pasa por la autenticación normal
 * Solo para diagnóstico temporal
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores, los capturamos

// Info básica
$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'php_version' => PHP_VERSION,
];

// Verificar cookie
$debug['cookie_exists'] = isset($_COOKIE['auth_token']);
$debug['cookie_value_preview'] = isset($_COOKIE['auth_token'])
    ? substr($_COOKIE['auth_token'], 0, 20) . '...'
    : 'NO COOKIE';

// Todas las cookies (nombres solamente)
$debug['all_cookies'] = array_keys($_COOKIE);

// Verificar POST token
$debug['post_token_exists'] = isset($_POST['auth_token']);

// Intentar decodificar JWT manualmente
if (isset($_COOKIE['auth_token'])) {
    $jwt = $_COOKIE['auth_token'];
    $parts = explode('.', $jwt);

    if (count($parts) === 3) {
        // Decodificar payload (parte central)
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if ($payload) {
            $debug['jwt_payload'] = [
                'user_id' => $payload['user_id'] ?? 'NOT SET',
                'role' => $payload['role'] ?? 'NOT SET',
                'iat' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : 'NOT SET',
                'exp' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : 'NOT SET',
                'is_expired' => isset($payload['exp']) ? ($payload['exp'] < time() ? 'YES - EXPIRED!' : 'No') : 'Unknown',
                'seconds_until_expiry' => isset($payload['exp']) ? ($payload['exp'] - time()) : 'Unknown'
            ];
        } else {
            $debug['jwt_payload'] = 'DECODE FAILED';
        }
    } else {
        $debug['jwt_parts'] = 'INVALID JWT FORMAT (expected 3 parts, got ' . count($parts) . ')';
    }
} elseif (isset($_POST['auth_token'])) {
    $debug['jwt_source'] = 'POST (mobile app?)';
}

// Verificar archivos críticos
$critical_files = [
    '/bold/vars.php',
    '/bold/functions.php',
    '/bold/auth.php',
    '/bold/db.php',
    '/bold/classes/InmaticClient.php',
    '/bold/classes/GoogleCalendarClient.php',
];

$root_dir = realpath(__DIR__ . '/..');
$debug['root_dir'] = $root_dir;

foreach ($critical_files as $file) {
    $path = $root_dir . $file;
    $debug['files'][$file] = [
        'exists' => file_exists($path),
        'size' => file_exists($path) ? filesize($path) : 0
    ];
}

// Verificar sintaxis de archivos PHP críticos (sin ejecutarlos)
$syntax_check = [];
$files_to_check = [
    $root_dir . '/bold/functions.php',
    $root_dir . '/bold/auth.php',
    $root_dir . '/api/dashboard-kpis-advisory.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
        $syntax_check[basename($file)] = [
            'valid' => $return_var === 0,
            'output' => implode("\n", $output)
        ];
    }
}
$debug['syntax_check'] = $syntax_check;

// Output
echo json_encode($debug, JSON_PRETTY_PRINT);
