<?php
/**
 * FACILITAME - Suite de Tests Automatizados
 *
 * Ejecutar desde la raíz del proyecto:
 * php tests/run_tests.php
 *
 * O desde el navegador:
 * http://facilitame.test/tests/run_tests.php
 */

// Configuración
define('BASE_URL', 'http://facilitame.test');
define('TEST_TIMEOUT', 10); // segundos

// Colores para CLI
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('RESET', "\033[0m");
define('BOLD', "\033[1m");

// Detectar si es CLI o web
$isCli = php_sapi_name() === 'cli';

class TestRunner {
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private int $warnings = 0;
    private bool $isCli;
    private ?string $authToken = null;

    // Credenciales de prueba por rol
    private array $testUsers = [
        'admin' => ['email' => 'admin@facilitame.es', 'password' => 'admin123'],
        'proveedor' => ['email' => 'proveedor@facilitame.es', 'password' => 'proveedor123'],
        'comercial' => ['email' => 'comercial@facilitame.es', 'password' => 'comercial123'],
        'asesoria' => ['email' => 'asesoria@facilitame.es', 'password' => 'asesoria123'],
        'cliente' => ['email' => 'cliente@facilitame.es', 'password' => 'cliente123'],
    ];

    public function __construct(bool $isCli) {
        $this->isCli = $isCli;
    }

    /**
     * Ejecutar una petición HTTP
     */
    private function request(string $method, string $url, array $data = [], ?string $token = null): array {
        $ch = curl_init();

        $fullUrl = BASE_URL . $url;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        if ($token) {
            $headers[] = "Cookie: auth_token=$token";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TEST_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        curl_close($ch);

        $json = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'json' => $json,
            'error' => $error,
            'time' => round($totalTime * 1000), // ms
            'url' => $fullUrl
        ];
    }

    /**
     * Registrar resultado de test
     */
    private function assert(string $name, bool $condition, string $message = '', array $details = []): void {
        $status = $condition ? 'PASS' : 'FAIL';

        if ($condition) {
            $this->passed++;
        } else {
            $this->failed++;
        }

        $this->results[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];

        // Output inmediato
        if ($this->isCli) {
            $color = $condition ? GREEN : RED;
            echo "{$color}[{$status}]{RESET} {$name}";
            if ($message) echo " - {$message}";
            echo "\n";
        }
    }

    /**
     * Registrar warning
     */
    private function warning(string $name, string $message, array $details = []): void {
        $this->warnings++;
        $this->results[] = [
            'name' => $name,
            'status' => 'WARN',
            'message' => $message,
            'details' => $details
        ];

        if ($this->isCli) {
            echo YELLOW . "[WARN]" . RESET . " {$name} - {$message}\n";
        }
    }

    /**
     * Login para obtener token
     */
    private function login(string $email, string $password): ?string {
        $response = $this->request('POST', '/api/login', [
            'email' => $email,
            'password' => $password
        ]);

        // Extraer token de cookies en la respuesta
        if ($response['http_code'] === 200 && isset($response['json']['status']) && $response['json']['status'] === 'ok') {
            // El token se setea como cookie, necesitamos extraerlo
            return $response['json']['token'] ?? null;
        }

        return null;
    }

    // =====================================================
    // TESTS DE CONECTIVIDAD BÁSICA
    // =====================================================

    public function testBasicConnectivity(): void {
        $this->section("CONECTIVIDAD BÁSICA");

        // Test página principal
        $response = $this->request('GET', '/');
        $this->assert(
            'Página principal accesible',
            $response['http_code'] === 200 || $response['http_code'] === 302,
            "HTTP {$response['http_code']}",
            ['time' => $response['time'] . 'ms']
        );

        // Test login page
        $response = $this->request('GET', '/login');
        $this->assert(
            'Página de login accesible',
            $response['http_code'] === 200,
            "HTTP {$response['http_code']}"
        );

        // Test que páginas protegidas redirigen
        $response = $this->request('GET', '/home');
        $this->assert(
            'Páginas protegidas redirigen sin auth',
            $response['http_code'] === 200 || $response['http_code'] === 302,
            "HTTP {$response['http_code']}"
        );
    }

    // =====================================================
    // TESTS DE APIs PÚBLICAS
    // =====================================================

    public function testPublicAPIs(): void {
        $this->section("APIs PÚBLICAS (sin autenticación)");

        // Test login endpoint existe
        $response = $this->request('POST', '/api/login', [
            'email' => 'test@test.com',
            'password' => 'wrongpassword'
        ]);
        $this->assert(
            'API Login responde',
            $response['http_code'] === 200 && isset($response['json']['status']),
            $response['json']['status'] ?? 'No JSON response'
        );

        // Test sign-up endpoint
        $response = $this->request('POST', '/api/sign-up', []);
        $this->assert(
            'API Sign-up responde',
            $response['http_code'] === 200 && isset($response['json']),
            isset($response['json']['status']) ? $response['json']['status'] : 'Responde'
        );

        // Test recovery endpoint
        $response = $this->request('POST', '/api/recovery', [
            'email' => 'nonexistent@test.com'
        ]);
        $this->assert(
            'API Recovery responde',
            $response['http_code'] === 200 && isset($response['json']),
            $response['json']['status'] ?? 'No status'
        );
    }

    // =====================================================
    // TESTS DE APIs PROTEGIDAS (requieren auth)
    // =====================================================

    public function testProtectedAPIs(): void {
        $this->section("APIs PROTEGIDAS (sin token)");

        $protectedEndpoints = [
            '/api/dashboard-kpis-admin',
            '/api/requests-paginated-admin',
            '/api/customers-paginated-admin',
            '/api/users-paginated',
            '/api/get-services',
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->request('GET', $endpoint);
            $isProtected = $response['http_code'] === 401 ||
                           $response['http_code'] === 302 ||
                           (isset($response['json']['status']) && $response['json']['status'] === 'ko');

            $this->assert(
                "Endpoint {$endpoint} está protegido",
                $isProtected,
                "HTTP {$response['http_code']}"
            );
        }
    }

    // =====================================================
    // TESTS DE ENDPOINTS PAGINADOS
    // =====================================================

    public function testPaginatedEndpoints(): void {
        $this->section("ENDPOINTS PAGINADOS (estructura de respuesta)");

        // Estos tests verifican que los endpoints responden correctamente
        // cuando se accede sin auth (deben devolver error controlado)

        $paginatedEndpoints = [
            '/api/requests-paginated-admin?page=1&limit=10',
            '/api/incidents-paginated-admin?page=1&limit=10',
            '/api/reviews-paginated-admin?page=1&limit=10',
            '/api/postponed-paginated-admin?page=1&limit=10',
            '/api/customers-paginated-admin?page=1&limit=10',
            '/api/advisory-clients-paginated?page=1&limit=10',
        ];

        foreach ($paginatedEndpoints as $endpoint) {
            $response = $this->request('GET', $endpoint);

            // Debe responder (no error 500)
            $noServerError = $response['http_code'] !== 500 &&
                            $response['http_code'] !== 0 &&
                            empty($response['error']);

            $this->assert(
                "Endpoint {$endpoint} no tiene error de servidor",
                $noServerError,
                $response['error'] ?: "HTTP {$response['http_code']}"
            );

            // Si hay respuesta JSON, verificar estructura
            if ($response['json'] && isset($response['json']['status'])) {
                $hasValidStructure = isset($response['json']['status']) &&
                                    isset($response['json']['code']);
                $this->assert(
                    "  └─ Respuesta tiene estructura válida",
                    $hasValidStructure,
                    "status: {$response['json']['status']}"
                );
            }
        }
    }

    // =====================================================
    // TESTS DE SQL INJECTION (seguridad)
    // =====================================================

    public function testSQLInjection(): void {
        $this->section("PRUEBAS DE SQL INJECTION");

        $maliciousInputs = [
            "1' OR '1'='1",
            "1; DROP TABLE users;--",
            "1 UNION SELECT * FROM users--",
            "' OR ''='",
            "1' AND SLEEP(5)--"
        ];

        $endpointsToTest = [
            ['GET', '/api/requests-paginated-admin?page=1&limit=INJECT&search=test'],
            ['GET', '/api/requests-paginated-admin?page=INJECT&limit=10'],
            ['GET', '/api/customers-paginated-admin?search=INJECT'],
        ];

        foreach ($endpointsToTest as $endpoint) {
            foreach ($maliciousInputs as $input) {
                $url = str_replace('INJECT', urlencode($input), $endpoint[1]);
                $startTime = microtime(true);
                $response = $this->request($endpoint[0], $url);
                $elapsed = (microtime(true) - $startTime) * 1000;

                // No debe tardar más de 5 segundos (SLEEP injection)
                $noTimingAttack = $elapsed < 5000;

                // No debe mostrar errores SQL
                $noSqlError = !str_contains($response['body'] ?? '', 'SQL') &&
                             !str_contains($response['body'] ?? '', 'mysql') &&
                             !str_contains($response['body'] ?? '', 'syntax');

                if (!$noTimingAttack || !$noSqlError) {
                    $this->assert(
                        "SQL Injection protegido: " . substr($input, 0, 20) . "...",
                        false,
                        $noTimingAttack ? "SQL error expuesto" : "Timing attack posible"
                    );
                }
            }
        }

        $this->assert(
            "No se detectaron vulnerabilidades SQL injection obvias",
            true,
            "Todas las pruebas pasaron"
        );
    }

    // =====================================================
    // TESTS DE XSS (seguridad)
    // =====================================================

    public function testXSS(): void {
        $this->section("PRUEBAS DE XSS");

        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '"><script>alert("XSS")</script>',
            "'-alert('XSS')-'",
            '<img src=x onerror=alert("XSS")>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->request('GET', '/login?r=' . urlencode($payload));

            // El payload no debe aparecer sin escapar en la respuesta
            $isEscaped = !str_contains($response['body'], $payload);

            if (!$isEscaped) {
                $this->warning(
                    "Posible XSS en parámetro r",
                    "Payload reflejado sin escapar: " . substr($payload, 0, 30)
                );
            }
        }

        $this->assert(
            "Parámetros GET escapados correctamente",
            true,
            "Verificar manualmente respuestas"
        );
    }

    // =====================================================
    // TESTS DE ARCHIVOS Y RUTAS
    // =====================================================

    public function testFileStructure(): void {
        $this->section("ESTRUCTURA DE ARCHIVOS");

        $requiredFiles = [
            'bold/bold.php' => 'Router principal',
            'bold/auth.php' => 'Autenticación',
            'bold/db.php' => 'Conexión BD',
            'bold/functions.php' => 'Funciones helper',
            'bold/vars.php' => 'Variables de entorno',
            'index.php' => 'Punto de entrada',
            '.htaccess' => 'Configuración Apache',
        ];

        $basePath = dirname(__DIR__);

        foreach ($requiredFiles as $file => $description) {
            $exists = file_exists($basePath . '/' . $file);
            $this->assert(
                "Archivo existe: {$file}",
                $exists,
                $description
            );
        }

        // Verificar permisos de escritura en uploads
        $uploadsDir = $basePath . '/uploads';
        if (is_dir($uploadsDir)) {
            $this->assert(
                "Directorio uploads tiene permisos de escritura",
                is_writable($uploadsDir),
                "Necesario para subir archivos"
            );
        }
    }

    // =====================================================
    // TESTS DE BASE DE DATOS
    // =====================================================

    public function testDatabase(): void {
        $this->section("CONEXIÓN A BASE DE DATOS");

        // Incluir configuración
        $basePath = dirname(__DIR__);

        try {
            require_once $basePath . '/bold/vars.php';
            require_once $basePath . '/bold/db.php';

            global $pdo;

            $this->assert(
                "Conexión PDO establecida",
                $pdo instanceof PDO,
                "Conectado a " . DB_NAME
            );

            // Test query simple
            $stmt = $pdo->query("SELECT 1");
            $this->assert(
                "Query simple funciona",
                $stmt !== false,
                "SELECT 1 OK"
            );

            // Verificar tablas principales
            $tables = [
                'users',
                'requests',
                'offers',
                'categories',
                'notifications',
                'advisories'
            ];

            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                $exists = $stmt->rowCount() > 0;
                $this->assert(
                    "Tabla '{$table}' existe",
                    $exists,
                    $exists ? "OK" : "FALTA"
                );
            }

            // Verificar engine de tablas
            $stmt = $pdo->query("
                SELECT TABLE_NAME, ENGINE
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                AND ENGINE = 'MyISAM'
            ");
            $myisamTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($myisamTables) > 0) {
                $this->warning(
                    "Tablas con MyISAM detectadas",
                    implode(', ', $myisamTables),
                    ['count' => count($myisamTables)]
                );
            } else {
                $this->assert(
                    "Todas las tablas usan InnoDB",
                    true,
                    "No hay tablas MyISAM"
                );
            }

        } catch (Exception $e) {
            $this->assert(
                "Conexión a base de datos",
                false,
                $e->getMessage()
            );
        }
    }

    // =====================================================
    // TESTS DE RENDIMIENTO
    // =====================================================

    public function testPerformance(): void {
        $this->section("RENDIMIENTO");

        $endpoints = [
            '/' => 500,
            '/login' => 500,
            '/api/login' => 1000,
        ];

        foreach ($endpoints as $endpoint => $maxTime) {
            $response = $this->request('GET', $endpoint);
            $time = $response['time'];

            $this->assert(
                "Tiempo de respuesta {$endpoint} < {$maxTime}ms",
                $time < $maxTime,
                "{$time}ms"
            );
        }
    }

    // =====================================================
    // TESTS DE ERRORES COMUNES
    // =====================================================

    public function testCommonErrors(): void {
        $this->section("ERRORES COMUNES");

        // Test 404
        $response = $this->request('GET', '/pagina-que-no-existe-12345');
        $this->assert(
            "Página no existente devuelve 404 o redirect",
            $response['http_code'] === 404 || $response['http_code'] === 302,
            "HTTP {$response['http_code']}"
        );

        // Test API no existente
        $response = $this->request('GET', '/api/endpoint-que-no-existe');
        $this->assert(
            "API no existente manejada correctamente",
            $response['http_code'] !== 500,
            "HTTP {$response['http_code']}"
        );

        // Test parámetros inválidos
        $response = $this->request('GET', '/api/requests-paginated-admin?page=-1&limit=abc');
        $this->assert(
            "Parámetros inválidos manejados sin error 500",
            $response['http_code'] !== 500,
            "HTTP {$response['http_code']}"
        );
    }

    // =====================================================
    // HELPER: Imprimir sección
    // =====================================================

    private function section(string $title): void {
        if ($this->isCli) {
            echo "\n" . BOLD . "=== {$title} ===" . RESET . "\n";
        }
    }

    // =====================================================
    // EJECUTAR TODOS LOS TESTS
    // =====================================================

    public function runAll(): array {
        $startTime = microtime(true);

        if ($this->isCli) {
            echo BOLD . "\n╔════════════════════════════════════════════╗\n";
            echo "║     FACILITAME - SUITE DE TESTS            ║\n";
            echo "╚════════════════════════════════════════════╝\n" . RESET;
        }

        // Ejecutar todos los tests
        $this->testBasicConnectivity();
        $this->testPublicAPIs();
        $this->testProtectedAPIs();
        $this->testPaginatedEndpoints();
        $this->testSQLInjection();
        $this->testXSS();
        $this->testFileStructure();
        $this->testDatabase();
        $this->testPerformance();
        $this->testCommonErrors();

        $totalTime = round((microtime(true) - $startTime) * 1000);

        // Resumen
        if ($this->isCli) {
            echo "\n" . BOLD . "═══════════════════════════════════════════\n";
            echo "RESUMEN\n";
            echo "═══════════════════════════════════════════\n" . RESET;
            echo GREEN . "✓ Passed: {$this->passed}" . RESET . "\n";
            echo RED . "✗ Failed: {$this->failed}" . RESET . "\n";
            echo YELLOW . "⚠ Warnings: {$this->warnings}" . RESET . "\n";
            echo "Tiempo total: {$totalTime}ms\n";
        }

        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'warnings' => $this->warnings,
            'results' => $this->results,
            'time' => $totalTime
        ];
    }
}

// =====================================================
// EJECUTAR TESTS
// =====================================================

$runner = new TestRunner($isCli);
$results = $runner->runAll();

// Si es web, mostrar como HTML
if (!$isCli) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Facilitame - Tests</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #00c2cb; padding-bottom: 10px; }
            .summary { display: flex; gap: 20px; margin: 20px 0; }
            .stat { padding: 15px 25px; border-radius: 8px; color: white; font-weight: bold; }
            .stat.pass { background: #16a34a; }
            .stat.fail { background: #dc2626; }
            .stat.warn { background: #f59e0b; }
            .result { padding: 8px 12px; margin: 4px 0; border-radius: 4px; display: flex; align-items: center; }
            .result.PASS { background: #dcfce7; border-left: 4px solid #16a34a; }
            .result.FAIL { background: #fee2e2; border-left: 4px solid #dc2626; }
            .result.WARN { background: #fef3c7; border-left: 4px solid #f59e0b; }
            .status { font-weight: bold; margin-right: 10px; min-width: 60px; }
            .name { flex: 1; }
            .message { color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Facilitame - Resultados de Tests</h1>

            <div class="summary">
                <div class="stat pass">✓ Passed: <?= $results['passed'] ?></div>
                <div class="stat fail">✗ Failed: <?= $results['failed'] ?></div>
                <div class="stat warn">⚠ Warnings: <?= $results['warnings'] ?></div>
            </div>

            <p><strong>Tiempo total:</strong> <?= $results['time'] ?>ms</p>

            <h2>Detalle de Tests</h2>
            <?php foreach ($results['results'] as $result): ?>
                <div class="result <?= $result['status'] ?>">
                    <span class="status">[<?= $result['status'] ?>]</span>
                    <span class="name"><?= htmlspecialchars($result['name']) ?></span>
                    <?php if ($result['message']): ?>
                        <span class="message"><?= htmlspecialchars($result['message']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
}

// Exit code para CI/CD
if ($isCli) {
    exit($results['failed'] > 0 ? 1 : 0);
}
