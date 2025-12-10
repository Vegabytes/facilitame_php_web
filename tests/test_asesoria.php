<?php
/**
 * FACILITAME - Tests del Módulo Asesoría
 *
 * Tests funcionales para verificar todas las funcionalidades
 * del módulo de asesorías.
 *
 * Ejecutar: php tests/test_asesoria.php
 */

define('BASE_URL', 'http://facilitame.test');
define('TEST_TIMEOUT', 15);

class AsesoriaTestSuite {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    private ?string $asesoriaToken = null;
    private ?string $clienteToken = null;

    // Credenciales de prueba
    private array $asesoriaUser = ['email' => 'asesoria@test.com', 'password' => 'test123'];
    private array $clienteUser = ['email' => 'cliente@test.com', 'password' => 'test123'];

    public function __construct() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "   FACILITAME - Tests del Módulo Asesoría\n";
        echo str_repeat("=", 60) . "\n\n";
    }

    private function request(string $method, string $url, array $data = [], ?string $token = null): array {
        $ch = curl_init();
        $fullUrl = BASE_URL . $url;

        $headers = ['Accept: application/json'];

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if ($token) {
            $headers[] = "Cookie: token=$token";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $method === 'GET' && !empty($data) ? $fullUrl . '?' . http_build_query($data) : $fullUrl,
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
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'json' => json_decode($response, true),
            'error' => $error,
            'time' => round($time * 1000)
        ];
    }

    private function test(string $name, bool $condition, string $details = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  ✅ $name\n";
        } else {
            $this->failed++;
            echo "  ❌ $name" . ($details ? " - $details" : "") . "\n";
        }
        $this->results[] = ['name' => $name, 'passed' => $condition, 'details' => $details];
    }

    private function section(string $title): void {
        echo "\n📋 $title\n" . str_repeat("-", 50) . "\n";
    }

    /**
     * Login como asesoría
     */
    public function loginAsesoria(): bool {
        $this->section("Autenticación Asesoría");

        $res = $this->request('POST', '/api/login', $this->asesoriaUser);

        $success = $res['json']['status'] === 'ok' && !empty($res['json']['data']['token']);
        $this->test('Login como asesoría', $success, $res['json']['message'] ?? '');

        if ($success) {
            $this->asesoriaToken = $res['json']['data']['token'];
        }

        return $success;
    }

    /**
     * Login como cliente de asesoría
     */
    public function loginCliente(): bool {
        $res = $this->request('POST', '/api/login', $this->clienteUser);

        $success = $res['json']['status'] === 'ok' && !empty($res['json']['data']['token']);
        $this->test('Login como cliente', $success, $res['json']['message'] ?? '');

        if ($success) {
            $this->clienteToken = $res['json']['data']['token'];
        }

        return $success;
    }

    /**
     * Tests de Clientes Vinculados
     */
    public function testClientes(): void {
        $this->section("Clientes Vinculados");

        // Listar clientes
        $res = $this->request('GET', '/api/advisory-clients-paginated', ['page' => 1, 'limit' => 10], $this->asesoriaToken);
        $this->test('Listar clientes vinculados', $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta tiene estructura de paginación',
                isset($res['json']['data']['pagination']) &&
                isset($res['json']['data']['data'])
            );
        }
    }

    /**
     * Tests de Citas
     */
    public function testCitas(): void {
        $this->section("Sistema de Citas");

        // Listar citas (asesoría)
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['page' => 1, 'limit' => 10], $this->asesoriaToken);
        $this->test('Listar citas (asesoría)', $res['json']['status'] === 'ok');

        // Verificar filtro por estado
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['status' => 'activas'], $this->asesoriaToken);
        $this->test('Filtrar citas activas', $res['json']['status'] === 'ok');

        // Listar citas (cliente)
        if ($this->clienteToken) {
            $res = $this->request('GET', '/api-customer-appointments-paginated', ['page' => 1], $this->clienteToken);
            $this->test('Listar citas (cliente)', $res['json']['status'] === 'ok');
        }
    }

    /**
     * Tests de Facturas
     */
    public function testFacturas(): void {
        $this->section("Facturas de Clientes");

        // Listar facturas recibidas (asesoría)
        $res = $this->request('GET', '/api-advisory-invoices-paginated', ['page' => 1, 'limit' => 10], $this->asesoriaToken);
        $this->test('Listar facturas recibidas', $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta tiene estadísticas',
                isset($res['json']['data']['stats'])
            );
        }

        // Listar facturas enviadas (cliente)
        if ($this->clienteToken) {
            $res = $this->request('GET', '/customer-invoices-list', ['page' => 1], $this->clienteToken);
            $this->test('Listar facturas enviadas (cliente)', $res['json']['status'] === 'ok');
        }
    }

    /**
     * Tests de Comunicaciones
     */
    public function testComunicaciones(): void {
        $this->section("Comunicaciones");

        // Listar comunicaciones enviadas (asesoría)
        $res = $this->request('GET', '/api-advisory-communications-list', ['page' => 1, 'limit' => 10], $this->asesoriaToken);
        $this->test('Listar comunicaciones (asesoría)', $res['json']['status'] === 'ok');

        // Listar comunicaciones recibidas (cliente)
        if ($this->clienteToken) {
            $res = $this->request('GET', '/api/customer-communications-list', ['page' => 1], $this->clienteToken);
            $this->test('Listar comunicaciones (cliente)', $res['json']['status'] === 'ok');
        }
    }

    /**
     * Tests de Chat
     */
    public function testChat(): void {
        $this->section("Chat Asesoría-Cliente");

        // Obtener lista de chats (asesoría)
        $res = $this->request('GET', '/api/advisory-chat-list', [], $this->asesoriaToken);
        $this->test('Obtener lista de chats', $res['http_code'] === 200 || $res['json']['status'] === 'ok');
    }

    /**
     * Tests de Notificaciones
     */
    public function testNotificaciones(): void {
        $this->section("Notificaciones");

        // Listar notificaciones (asesoría)
        $res = $this->request('GET', '/api/notifications-paginated-advisory', ['page' => 1], $this->asesoriaToken);
        $this->test('Listar notificaciones (asesoría)', $res['json']['status'] === 'ok');

        // Listar notificaciones (cliente)
        if ($this->clienteToken) {
            $res = $this->request('GET', '/api/notifications-paginated-customer', ['page' => 1], $this->clienteToken);
            $this->test('Listar notificaciones (cliente)', $res['json']['status'] === 'ok');
        }
    }

    /**
     * Tests de Descarga de Archivos
     */
    public function testDescargas(): void {
        $this->section("Descarga de Archivos");

        // Verificar que el endpoint existe y requiere autenticación
        $res = $this->request('GET', '/api/file-download', ['type' => 'advisory_invoice', 'id' => 1]);
        $this->test('Endpoint file-download requiere auth', $res['http_code'] === 403 || strpos($res['body'], 'No autorizado') !== false);

        // Con token pero ID inválido
        $res = $this->request('GET', '/api/file-download', ['type' => 'advisory_invoice', 'id' => 999999], $this->asesoriaToken);
        $this->test('file-download retorna 404 si no existe', $res['http_code'] === 404 || strpos($res['body'], 'no encontrad') !== false);
    }

    /**
     * Tests de Validación de Permisos
     */
    public function testPermisos(): void {
        $this->section("Validación de Permisos");

        // Cliente no debe poder acceder a endpoints de asesoría
        if ($this->clienteToken) {
            $res = $this->request('GET', '/api-advisory-appointments-paginated', [], $this->clienteToken);
            $this->test('Cliente NO puede acceder a citas de asesoría',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko'
            );

            $res = $this->request('GET', '/api-advisory-invoices-paginated', [], $this->clienteToken);
            $this->test('Cliente NO puede acceder a facturas de asesoría',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko'
            );
        }

        // Sin token no debe acceder
        $res = $this->request('GET', '/api-advisory-appointments-paginated', []);
        $this->test('Sin auth NO puede acceder a endpoints protegidos',
            $res['http_code'] === 403 || $res['json']['status'] === 'ko'
        );
    }

    /**
     * Tests de Rendimiento
     */
    public function testRendimiento(): void {
        $this->section("Rendimiento");

        // Endpoints principales deben responder en menos de 2 segundos
        $endpoints = [
            '/api-advisory-appointments-paginated',
            '/api-advisory-invoices-paginated',
            '/api-advisory-communications-list',
        ];

        foreach ($endpoints as $endpoint) {
            $res = $this->request('GET', $endpoint, ['page' => 1, 'limit' => 10], $this->asesoriaToken);
            $this->test(
                "Rendimiento $endpoint < 2s",
                $res['time'] < 2000,
                $res['time'] . 'ms'
            );
        }
    }

    /**
     * Ejecutar todos los tests
     */
    public function run(): void {
        // Autenticación
        $asesoriaOk = $this->loginAsesoria();
        $clienteOk = $this->loginCliente();

        if (!$asesoriaOk) {
            echo "\n⚠️  No se pudo autenticar como asesoría. Algunos tests se omitirán.\n";
            echo "    Verifica que exista el usuario: " . $this->asesoriaUser['email'] . "\n";
        }

        if (!$clienteOk) {
            echo "\n⚠️  No se pudo autenticar como cliente. Algunos tests se omitirán.\n";
        }

        // Ejecutar tests
        if ($asesoriaOk) {
            $this->testClientes();
            $this->testCitas();
            $this->testFacturas();
            $this->testComunicaciones();
            $this->testChat();
            $this->testNotificaciones();
            $this->testDescargas();
            $this->testRendimiento();
        }

        $this->testPermisos();

        // Resumen
        $this->printSummary();
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed;
        $pct = $total > 0 ? round(($this->passed / $total) * 100) : 0;

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "   RESUMEN DE TESTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "   Total:    $total tests\n";
        echo "   Pasados:  {$this->passed} ✅\n";
        echo "   Fallidos: {$this->failed} ❌\n";
        echo "   Éxito:    {$pct}%\n";
        echo str_repeat("=", 60) . "\n\n";

        if ($this->failed > 0) {
            echo "Tests fallidos:\n";
            foreach ($this->results as $r) {
                if (!$r['passed']) {
                    echo "  - {$r['name']}" . ($r['details'] ? ": {$r['details']}" : "") . "\n";
                }
            }
            echo "\n";
        }
    }
}

// Ejecutar tests
$suite = new AsesoriaTestSuite();
$suite->run();
