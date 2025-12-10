<?php
/**
 * FACILITAME - Suite Completa de Tests
 *
 * Tests funcionales que cubren todos los módulos y perfiles de usuario.
 *
 * PREREQUISITOS:
 *   1. Ejecutar: mysql -u root -p facilitame < tests/setup_test_data.sql
 *   2. Tener el servidor corriendo en http://facilitame.test
 *
 * EJECUTAR:
 *   php tests/test_suite.php [módulo]
 *
 * MÓDULOS DISPONIBLES:
 *   - all (por defecto): Ejecuta todos los tests
 *   - auth: Tests de autenticación
 *   - asesoria: Tests del módulo asesoría
 *   - cliente: Tests desde perspectiva cliente
 *   - admin: Tests de administración
 *   - proveedor: Tests de proveedor
 *   - comercial: Tests de comercial
 *   - permissions: Tests de permisos cruzados
 *   - performance: Tests de rendimiento
 */

define('BASE_URL', 'http://facilitame.test');
define('TEST_TIMEOUT', 30);
define('PERFORMANCE_THRESHOLD_MS', 2000);

class FacilitameTestSuite {
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private array $results = [];
    private array $tokens = [];
    private array $testData = [];

    // Credenciales de usuarios de prueba
    private array $users = [
        'admin' => ['email' => 'admin@test.com', 'password' => 'test123'],
        'comercial' => ['email' => 'comercial@test.com', 'password' => 'test123'],
        'proveedor' => ['email' => 'proveedor@test.com', 'password' => 'test123'],
        'asesoria' => ['email' => 'asesoria@test.com', 'password' => 'test123'],
        'asesoria_gratuita' => ['email' => 'asesoria2@test.com', 'password' => 'test123'],
        'cliente' => ['email' => 'cliente@test.com', 'password' => 'test123'],
        'cliente_empresa' => ['email' => 'cliente2@test.com', 'password' => 'test123'],
        'cliente_sin_asesoria' => ['email' => 'cliente3@test.com', 'password' => 'test123'],
    ];

    public function __construct() {
        $this->printHeader();
    }

    private function printHeader(): void {
        echo "\n" . str_repeat("═", 70) . "\n";
        echo "   ███████╗ █████╗  ██████╗██╗██╗     ██╗████████╗ █████╗ ███╗   ███╗███████╗\n";
        echo "   ██╔════╝██╔══██╗██╔════╝██║██║     ██║╚══██╔══╝██╔══██╗████╗ ████║██╔════╝\n";
        echo "   █████╗  ███████║██║     ██║██║     ██║   ██║   ███████║██╔████╔██║█████╗  \n";
        echo "   ██╔══╝  ██╔══██║██║     ██║██║     ██║   ██║   ██╔══██║██║╚██╔╝██║██╔══╝  \n";
        echo "   ██║     ██║  ██║╚██████╗██║███████╗██║   ██║   ██║  ██║██║ ╚═╝ ██║███████╗\n";
        echo "   ╚═╝     ╚═╝  ╚═╝ ╚═════╝╚═╝╚══════╝╚═╝   ╚═╝   ╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝\n";
        echo str_repeat("═", 70) . "\n";
        echo "                    SUITE COMPLETA DE TESTS\n";
        echo str_repeat("═", 70) . "\n\n";
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

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

    private function test(string $name, bool $condition, string $details = ''): bool {
        if ($condition) {
            $this->passed++;
            echo "  ✅ $name\n";
        } else {
            $this->failed++;
            echo "  ❌ $name" . ($details ? " — $details" : "") . "\n";
        }
        $this->results[] = ['name' => $name, 'passed' => $condition, 'details' => $details];
        return $condition;
    }

    private function skip(string $name, string $reason): void {
        $this->skipped++;
        echo "  ⏭️  $name — SKIPPED: $reason\n";
        $this->results[] = ['name' => $name, 'passed' => null, 'details' => "Skipped: $reason"];
    }

    private function section(string $title): void {
        echo "\n" . str_repeat("─", 60) . "\n";
        echo "📋 $title\n";
        echo str_repeat("─", 60) . "\n";
    }

    private function module(string $title): void {
        echo "\n" . str_repeat("═", 60) . "\n";
        echo "🔷 $title\n";
        echo str_repeat("═", 60) . "\n";
    }

    // =========================================================================
    // AUTENTICACIÓN
    // =========================================================================

    public function setupAuth(): bool {
        $this->module("AUTENTICACIÓN");
        $allOk = true;

        foreach ($this->users as $role => $creds) {
            $res = $this->request('POST', '/api/login', $creds);
            $success = isset($res['json']['status']) && $res['json']['status'] === 'ok' && !empty($res['json']['data']['token']);

            if ($success) {
                $this->tokens[$role] = $res['json']['data']['token'];
                echo "  ✅ Login $role\n";
            } else {
                echo "  ❌ Login $role — " . ($res['json']['message'] ?? 'Error desconocido') . "\n";
                $allOk = false;
            }
        }

        return $allOk;
    }

    public function testAuth(): void {
        $this->section("Tests de Autenticación");

        // Test login con credenciales correctas
        $res = $this->request('POST', '/api/login', $this->users['admin']);
        $this->test('Login con credenciales correctas',
            $res['json']['status'] === 'ok' && !empty($res['json']['data']['token']));

        // Test login con credenciales incorrectas
        $res = $this->request('POST', '/api/login', ['email' => 'admin@test.com', 'password' => 'wrongpassword']);
        $this->test('Login rechaza credenciales incorrectas',
            $res['json']['status'] === 'ko');

        // Test login sin email
        $res = $this->request('POST', '/api/login', ['password' => 'test123']);
        $this->test('Login requiere email',
            $res['json']['status'] === 'ko');

        // Test login sin password
        $res = $this->request('POST', '/api/login', ['email' => 'admin@test.com']);
        $this->test('Login requiere password',
            $res['json']['status'] === 'ko');

        // Test login con email inexistente
        $res = $this->request('POST', '/api/login', ['email' => 'noexiste@test.com', 'password' => 'test123']);
        $this->test('Login rechaza email inexistente',
            $res['json']['status'] === 'ko');

        // Test endpoint protegido sin token
        $res = $this->request('GET', '/api-advisory-appointments-paginated', []);
        $this->test('Endpoint protegido rechaza sin token',
            $res['http_code'] === 403 || (isset($res['json']['status']) && $res['json']['status'] === 'ko'));
    }

    // =========================================================================
    // MÓDULO ASESORÍA - DESDE ASESORÍA
    // =========================================================================

    public function testAsesoriaModule(): void {
        $this->module("MÓDULO ASESORÍA");

        if (empty($this->tokens['asesoria'])) {
            $this->skip('Módulo Asesoría', 'No se pudo autenticar como asesoría');
            return;
        }

        $token = $this->tokens['asesoria'];

        // --- Clientes Vinculados ---
        $this->section("Clientes Vinculados");

        $res = $this->request('GET', '/api/advisory-clients-paginated', ['page' => 1, 'limit' => 10], $token);
        $this->test('Listar clientes vinculados',
            isset($res['json']['status']) && $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta tiene paginación',
                isset($res['json']['data']['pagination']));
            $this->test('Respuesta tiene datos',
                isset($res['json']['data']['data']));

            // Guardar datos para tests posteriores
            if (!empty($res['json']['data']['data'])) {
                $this->testData['cliente_vinculado'] = $res['json']['data']['data'][0];
            }
        }

        // --- Sistema de Citas ---
        $this->section("Sistema de Citas (Asesoría)");

        // Listar todas las citas
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['page' => 1, 'limit' => 10], $token);
        $this->test('Listar todas las citas',
            isset($res['json']['status']) && $res['json']['status'] === 'ok');

        // Filtrar por estado: activas
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['status' => 'activas'], $token);
        $this->test('Filtrar citas activas (solicitado + agendado)',
            $res['json']['status'] === 'ok');

        // Filtrar por estado: finalizadas
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['status' => 'finalizado'], $token);
        $this->test('Filtrar citas finalizadas',
            $res['json']['status'] === 'ok');

        // Filtrar por estado: canceladas
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['status' => 'cancelado'], $token);
        $this->test('Filtrar citas canceladas',
            $res['json']['status'] === 'ok');

        // Obtener cita específica para tests
        $res = $this->request('GET', '/api-advisory-appointments-paginated', ['status' => 'solicitado'], $token);
        if ($res['json']['status'] === 'ok' && !empty($res['json']['data']['appointments'])) {
            $appointment = $res['json']['data']['appointments'][0];
            $this->testData['cita_solicitada'] = $appointment;

            // Agendar cita
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $res = $this->request('POST', '/api/advisory-update-appointment', [
                'id' => $appointment['id'],
                'action' => 'agendar',
                'scheduled_date' => $tomorrow,
                'scheduled_time' => '10:00'
            ], $token);
            $this->test('Agendar cita con fecha y hora',
                $res['json']['status'] === 'ok');

            // Intentar agendar sin fecha
            $res = $this->request('POST', '/api/advisory-update-appointment', [
                'id' => $appointment['id'],
                'action' => 'agendar'
            ], $token);
            $this->test('Agendar sin fecha debe fallar',
                $res['json']['status'] === 'ko');
        }

        // --- Facturas Recibidas ---
        $this->section("Facturas Recibidas (Asesoría)");

        $res = $this->request('GET', '/api-advisory-invoices-paginated', ['page' => 1, 'limit' => 10], $token);
        $this->test('Listar facturas recibidas',
            $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta tiene estadísticas',
                isset($res['json']['data']['stats']));
            $this->test('Stats incluye pendientes',
                isset($res['json']['data']['stats']['pending']));
            $this->test('Stats incluye procesadas',
                isset($res['json']['data']['stats']['processed']));

            // Guardar factura para test de marcar procesada
            if (!empty($res['json']['data']['invoices'])) {
                foreach ($res['json']['data']['invoices'] as $inv) {
                    if (!$inv['is_processed']) {
                        $this->testData['factura_pendiente'] = $inv;
                        break;
                    }
                }
            }
        }

        // Filtrar por tipo
        $res = $this->request('GET', '/api-advisory-invoices-paginated', ['type' => 'gasto'], $token);
        $this->test('Filtrar facturas por tipo (gasto)',
            $res['json']['status'] === 'ok');

        // Filtrar por estado procesada
        $res = $this->request('GET', '/api-advisory-invoices-paginated', ['processed' => '1'], $token);
        $this->test('Filtrar facturas procesadas',
            $res['json']['status'] === 'ok');

        // Marcar como procesada
        if (!empty($this->testData['factura_pendiente'])) {
            $res = $this->request('POST', '/api-advisory-mark-invoice-processed', [
                'id' => $this->testData['factura_pendiente']['id']
            ], $token);
            $this->test('Marcar factura como procesada',
                $res['json']['status'] === 'ok');
        }

        // --- Comunicaciones ---
        $this->section("Comunicaciones (Asesoría)");

        $res = $this->request('GET', '/api-advisory-communications-list', ['page' => 1, 'limit' => 10], $token);
        $this->test('Listar comunicaciones enviadas',
            $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta incluye estadísticas de lectura',
                isset($res['json']['data']['communications'][0]['read_count']) || empty($res['json']['data']['communications']));
        }

        // Enviar comunicación
        $res = $this->request('POST', '/api/advisory-send-communication', [
            'subject' => 'Test de comunicación ' . time(),
            'message' => 'Este es un mensaje de prueba automático.',
            'importance' => 'media',
            'recipient_filter' => 'todos'
        ], $token);
        $this->test('Enviar comunicación a todos los clientes',
            $res['json']['status'] === 'ok');

        // Enviar comunicación solo a autónomos
        $res = $this->request('POST', '/api/advisory-send-communication', [
            'subject' => 'Test solo autónomos',
            'message' => 'Mensaje solo para autónomos.',
            'importance' => 'leve',
            'recipient_filter' => 'autonomos'
        ], $token);
        $this->test('Enviar comunicación a autónomos',
            $res['json']['status'] === 'ok');

        // Enviar comunicación solo a empresas
        $res = $this->request('POST', '/api/advisory-send-communication', [
            'subject' => 'Test solo empresas',
            'message' => 'Mensaje solo para empresas.',
            'importance' => 'importante',
            'recipient_filter' => 'empresas'
        ], $token);
        $this->test('Enviar comunicación a empresas',
            $res['json']['status'] === 'ok');

        // Enviar sin asunto (debe fallar)
        $res = $this->request('POST', '/api/advisory-send-communication', [
            'message' => 'Mensaje sin asunto',
            'importance' => 'media',
            'recipient_filter' => 'todos'
        ], $token);
        $this->test('Comunicación sin asunto debe fallar',
            $res['json']['status'] === 'ko');

        // --- Chat ---
        $this->section("Chat Asesoría-Cliente");

        $res = $this->request('GET', '/api/advisory-chat-list', [], $token);
        $this->test('Obtener lista de chats',
            $res['http_code'] === 200);

        if (!empty($this->testData['cliente_vinculado'])) {
            $customerId = $this->testData['cliente_vinculado']['id'];

            // Obtener mensajes del chat
            $res = $this->request('GET', '/api/advisory-chat-messages', ['customer_id' => $customerId], $token);
            $this->test('Obtener mensajes de chat con cliente',
                $res['http_code'] === 200);

            // Enviar mensaje
            $res = $this->request('POST', '/api/advisory-chat-send', [
                'customer_id' => $customerId,
                'message' => 'Mensaje de prueba desde asesoría ' . time()
            ], $token);
            $this->test('Enviar mensaje de chat',
                $res['json']['status'] === 'ok' || $res['http_code'] === 200);
        }

        // --- Notificaciones ---
        $this->section("Notificaciones (Asesoría)");

        $res = $this->request('GET', '/api/notifications-paginated-advisory', ['page' => 1], $token);
        $this->test('Listar notificaciones',
            $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok') {
            $this->test('Respuesta tiene contador de no leídas',
                isset($res['json']['data']['unread_count']) || isset($res['json']['data']['pagination']));
        }
    }

    // =========================================================================
    // MÓDULO ASESORÍA - DESDE CLIENTE
    // =========================================================================

    public function testClienteAsesoriaModule(): void {
        $this->module("CLIENTE - INTERACCIÓN CON ASESORÍA");

        if (empty($this->tokens['cliente'])) {
            $this->skip('Módulo Cliente-Asesoría', 'No se pudo autenticar como cliente');
            return;
        }

        $token = $this->tokens['cliente'];

        // --- Citas ---
        $this->section("Sistema de Citas (Cliente)");

        $res = $this->request('GET', '/api-customer-appointments-paginated', ['page' => 1], $token);
        $this->test('Listar mis citas',
            $res['json']['status'] === 'ok');

        // Solicitar nueva cita
        $res = $this->request('POST', '/api-customer-request-appointment', [
            'type' => 'llamada',
            'department' => 'contabilidad',
            'preferred_time' => 'mañana',
            'reason' => 'Consulta de prueba automática ' . time()
        ], $token);
        $this->test('Solicitar nueva cita',
            $res['json']['status'] === 'ok');

        if ($res['json']['status'] === 'ok' && isset($res['json']['data']['id'])) {
            $citaId = $res['json']['data']['id'];

            // Cancelar cita
            $res = $this->request('POST', '/api-customer-cancel-appointment', [
                'id' => $citaId
            ], $token);
            $this->test('Cancelar cita propia',
                $res['json']['status'] === 'ok');
        }

        // Solicitar con campos faltantes
        $res = $this->request('POST', '/api-customer-request-appointment', [
            'type' => 'llamada'
        ], $token);
        $this->test('Solicitar cita sin campos requeridos debe fallar',
            $res['json']['status'] === 'ko');

        // --- Facturas (Envío) ---
        $this->section("Envío de Facturas (Cliente)");

        $res = $this->request('GET', '/api/customer-invoices-list', ['page' => 1], $token);
        $this->test('Listar facturas enviadas',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);

        // --- Comunicaciones Recibidas ---
        $this->section("Comunicaciones Recibidas (Cliente)");

        $res = $this->request('GET', '/api-customer-communications-list', ['page' => 1], $token);
        $this->test('Listar comunicaciones recibidas',
            $res['json']['status'] === 'ok');

        // Filtrar por importancia
        $res = $this->request('GET', '/api-customer-communications-list', ['importance' => 'importante'], $token);
        $this->test('Filtrar por importancia',
            $res['json']['status'] === 'ok');

        // --- Chat ---
        $this->section("Chat (Cliente)");

        $res = $this->request('GET', '/api/customer-chat-messages', [], $token);
        $this->test('Obtener mensajes de chat',
            $res['http_code'] === 200);

        $res = $this->request('POST', '/api/customer-chat-send', [
            'message' => 'Mensaje de prueba desde cliente ' . time()
        ], $token);
        $this->test('Enviar mensaje de chat',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);

        // --- Notificaciones ---
        $this->section("Notificaciones (Cliente)");

        $res = $this->request('GET', '/api/notifications-paginated-customer', ['page' => 1], $token);
        $this->test('Listar notificaciones',
            $res['json']['status'] === 'ok');
    }

    // =========================================================================
    // CLIENTE SIN ASESORÍA
    // =========================================================================

    public function testClienteSinAsesoria(): void {
        $this->module("CLIENTE SIN ASESORÍA VINCULADA");

        if (empty($this->tokens['cliente_sin_asesoria'])) {
            $this->skip('Cliente sin asesoría', 'No se pudo autenticar');
            return;
        }

        $token = $this->tokens['cliente_sin_asesoria'];

        // No debe poder solicitar citas
        $res = $this->request('POST', '/api-customer-request-appointment', [
            'type' => 'llamada',
            'department' => 'contabilidad',
            'preferred_time' => 'mañana',
            'reason' => 'Test'
        ], $token);
        $this->test('Cliente sin asesoría no puede solicitar cita',
            $res['json']['status'] === 'ko');

        // No debe ver comunicaciones
        $res = $this->request('GET', '/api-customer-communications-list', [], $token);
        $this->test('Cliente sin asesoría ve lista vacía de comunicaciones',
            $res['json']['status'] === 'ok' && empty($res['json']['data']['communications']));
    }

    // =========================================================================
    // ASESORÍA PLAN GRATUITO
    // =========================================================================

    public function testAsesoriaGratuita(): void {
        $this->module("ASESORÍA PLAN GRATUITO");

        if (empty($this->tokens['asesoria_gratuita'])) {
            $this->skip('Asesoría gratuita', 'No se pudo autenticar');
            return;
        }

        $token = $this->tokens['asesoria_gratuita'];

        // No debe poder recibir facturas (restricción del plan)
        // Este test depende de la implementación - verificar que la restricción existe
        $res = $this->request('GET', '/api-advisory-invoices-paginated', [], $token);
        $this->test('Asesoría gratuita puede listar facturas (aunque vacía)',
            $res['json']['status'] === 'ok');
    }

    // =========================================================================
    // PERMISOS CRUZADOS
    // =========================================================================

    public function testPermissions(): void {
        $this->module("VALIDACIÓN DE PERMISOS CRUZADOS");

        // Cliente intentando acceder a endpoints de asesoría
        $this->section("Cliente → Endpoints de Asesoría");

        if (!empty($this->tokens['cliente'])) {
            $token = $this->tokens['cliente'];

            $res = $this->request('GET', '/api-advisory-appointments-paginated', [], $token);
            $this->test('Cliente NO puede listar citas de asesoría',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');

            $res = $this->request('GET', '/api-advisory-invoices-paginated', [], $token);
            $this->test('Cliente NO puede listar facturas de asesoría',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');

            $res = $this->request('POST', '/api/advisory-send-communication', [
                'subject' => 'Hack',
                'message' => 'Test',
                'importance' => 'media',
                'recipient_filter' => 'todos'
            ], $token);
            $this->test('Cliente NO puede enviar comunicaciones',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');
        }

        // Asesoría intentando acceder a endpoints de admin
        $this->section("Asesoría → Endpoints de Admin");

        if (!empty($this->tokens['asesoria'])) {
            $token = $this->tokens['asesoria'];

            $res = $this->request('GET', '/api-users-paginated', [], $token);
            $this->test('Asesoría NO puede listar usuarios (admin)',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');

            $res = $this->request('GET', '/api-advisories-paginated-admin', [], $token);
            $this->test('Asesoría NO puede listar asesorías (admin)',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');
        }

        // Proveedor intentando acceder a endpoints de asesoría
        $this->section("Proveedor → Endpoints de Asesoría");

        if (!empty($this->tokens['proveedor'])) {
            $token = $this->tokens['proveedor'];

            $res = $this->request('GET', '/api-advisory-appointments-paginated', [], $token);
            $this->test('Proveedor NO puede acceder a citas de asesoría',
                $res['http_code'] === 403 || $res['json']['status'] === 'ko');
        }

        // Sin autenticación
        $this->section("Sin Autenticación");

        $endpoints = [
            '/api-advisory-appointments-paginated',
            '/api-advisory-invoices-paginated',
            '/api-customer-appointments-paginated',
            '/api/notifications-paginated-advisory',
            '/api-users-paginated',
        ];

        foreach ($endpoints as $endpoint) {
            $res = $this->request('GET', $endpoint, []);
            $this->test("Sin auth NO puede acceder a $endpoint",
                $res['http_code'] === 403 || (isset($res['json']['status']) && $res['json']['status'] === 'ko'));
        }
    }

    // =========================================================================
    // DESCARGA DE ARCHIVOS
    // =========================================================================

    public function testFileDownload(): void {
        $this->module("DESCARGA SEGURA DE ARCHIVOS");

        // Sin autenticación
        $res = $this->request('GET', '/api/file-download', ['type' => 'advisory_invoice', 'id' => 1]);
        $this->test('file-download requiere autenticación',
            $res['http_code'] === 403 || strpos($res['body'], 'No autorizado') !== false);

        // Con token pero tipo inválido
        if (!empty($this->tokens['asesoria'])) {
            $res = $this->request('GET', '/api/file-download', ['type' => 'invalid_type', 'id' => 1], $this->tokens['asesoria']);
            $this->test('file-download rechaza tipo inválido',
                $res['http_code'] === 400 || strpos($res['body'], 'Tipo no válido') !== false);

            // Con ID inexistente
            $res = $this->request('GET', '/api/file-download', ['type' => 'advisory_invoice', 'id' => 999999], $this->tokens['asesoria']);
            $this->test('file-download 404 si no existe',
                $res['http_code'] === 404 || strpos($res['body'], 'no encontrad') !== false);
        }

        // Cliente intentando descargar factura de otro
        if (!empty($this->tokens['cliente_sin_asesoria']) && !empty($this->testData['factura_pendiente'])) {
            $res = $this->request('GET', '/api/file-download', [
                'type' => 'advisory_invoice',
                'id' => $this->testData['factura_pendiente']['id']
            ], $this->tokens['cliente_sin_asesoria']);
            $this->test('Cliente NO puede descargar factura de otro',
                $res['http_code'] === 403);
        }
    }

    // =========================================================================
    // ADMIN
    // =========================================================================

    public function testAdminModule(): void {
        $this->module("MÓDULO ADMINISTRACIÓN");

        if (empty($this->tokens['admin'])) {
            $this->skip('Módulo Admin', 'No se pudo autenticar como admin');
            return;
        }

        $token = $this->tokens['admin'];

        // Usuarios
        $this->section("Gestión de Usuarios");

        $res = $this->request('GET', '/api-users-paginated', ['page' => 1, 'limit' => 10], $token);
        $this->test('Listar usuarios',
            $res['json']['status'] === 'ok');

        // Asesorías
        $this->section("Gestión de Asesorías");

        $res = $this->request('GET', '/api-advisories-paginated-admin', ['page' => 1], $token);
        $this->test('Listar asesorías',
            $res['json']['status'] === 'ok');

        // Citas de todas las asesorías
        $res = $this->request('GET', '/api-advisory-appointments-paginated-admin', ['page' => 1], $token);
        $this->test('Listar todas las citas (admin)',
            $res['json']['status'] === 'ok');

        // Facturas de todas las asesorías
        $res = $this->request('GET', '/api-advisory-invoices-paginated-admin', ['page' => 1], $token);
        $this->test('Listar todas las facturas (admin)',
            $res['json']['status'] === 'ok');

        // Comunicaciones
        $res = $this->request('GET', '/api-advisory-communications-list-admin', ['page' => 1], $token);
        $this->test('Listar todas las comunicaciones (admin)',
            $res['json']['status'] === 'ok');

        // Solicitudes
        $this->section("Gestión de Solicitudes");

        $res = $this->request('GET', '/api-requests-paginated-admin', ['page' => 1], $token);
        $this->test('Listar solicitudes',
            $res['json']['status'] === 'ok');

        // KPIs Dashboard
        $this->section("Dashboard Admin");

        $res = $this->request('GET', '/api-dashboard-kpis-admin', [], $token);
        $this->test('Obtener KPIs dashboard',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);
    }

    // =========================================================================
    // PROVEEDOR
    // =========================================================================

    public function testProveedorModule(): void {
        $this->module("MÓDULO PROVEEDOR");

        if (empty($this->tokens['proveedor'])) {
            $this->skip('Módulo Proveedor', 'No se pudo autenticar como proveedor');
            return;
        }

        $token = $this->tokens['proveedor'];

        // Solicitudes
        $this->section("Solicitudes (Proveedor)");

        $res = $this->request('GET', '/api-requests-paginated-provider', ['page' => 1], $token);
        $this->test('Listar solicitudes de mi categoría',
            $res['json']['status'] === 'ok');

        // Dashboard KPIs
        $res = $this->request('GET', '/api-dashboard-kpis-provider', [], $token);
        $this->test('Obtener KPIs proveedor',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);

        // Notificaciones
        $this->section("Notificaciones (Proveedor)");

        $res = $this->request('GET', '/api/notifications-paginated-provider', ['page' => 1], $token);
        $this->test('Listar notificaciones',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);
    }

    // =========================================================================
    // COMERCIAL
    // =========================================================================

    public function testComercialModule(): void {
        $this->module("MÓDULO COMERCIAL");

        if (empty($this->tokens['comercial'])) {
            $this->skip('Módulo Comercial', 'No se pudo autenticar como comercial');
            return;
        }

        $token = $this->tokens['comercial'];

        // Clientes
        $this->section("Clientes (Comercial)");

        $res = $this->request('GET', '/api-customers-paginated-sales', ['page' => 1], $token);
        $this->test('Listar mis clientes',
            $res['json']['status'] === 'ok');

        // Solicitudes
        $this->section("Solicitudes (Comercial)");

        $res = $this->request('GET', '/api-requests-paginated-sales', ['page' => 1], $token);
        $this->test('Listar solicitudes de mis clientes',
            $res['json']['status'] === 'ok');

        // Dashboard KPIs
        $res = $this->request('GET', '/api-dashboard-kpis-sales', [], $token);
        $this->test('Obtener KPIs comercial',
            $res['json']['status'] === 'ok' || $res['http_code'] === 200);

        // Notificaciones
        $res = $this->request('GET', '/api-notifications-paginated-sales', ['page' => 1], $token);
        $this->test('Listar notificaciones',
            $res['json']['status'] === 'ok');

        // Asesorías vinculadas
        $this->section("Asesorías (Comercial)");

        $res = $this->request('GET', '/api-salesrep-advisories-paginated', ['page' => 1], $token);
        $this->test('Listar asesorías de mi código',
            $res['json']['status'] === 'ok');
    }

    // =========================================================================
    // RENDIMIENTO
    // =========================================================================

    public function testPerformance(): void {
        $this->module("TESTS DE RENDIMIENTO");

        if (empty($this->tokens['asesoria'])) {
            $this->skip('Tests de rendimiento', 'No se pudo autenticar');
            return;
        }

        $this->section("Tiempos de Respuesta (< " . PERFORMANCE_THRESHOLD_MS . "ms)");

        $endpoints = [
            ['GET', '/api-advisory-appointments-paginated', $this->tokens['asesoria']],
            ['GET', '/api-advisory-invoices-paginated', $this->tokens['asesoria']],
            ['GET', '/api-advisory-communications-list', $this->tokens['asesoria']],
            ['GET', '/api-customer-appointments-paginated', $this->tokens['cliente'] ?? null],
            ['GET', '/api-customer-communications-list', $this->tokens['cliente'] ?? null],
            ['GET', '/api-users-paginated', $this->tokens['admin'] ?? null],
            ['GET', '/api-requests-paginated-admin', $this->tokens['admin'] ?? null],
        ];

        foreach ($endpoints as [$method, $endpoint, $token]) {
            if (!$token) continue;

            $res = $this->request($method, $endpoint, ['page' => 1, 'limit' => 10], $token);
            $this->test(
                "Rendimiento $endpoint",
                $res['time'] < PERFORMANCE_THRESHOLD_MS,
                $res['time'] . 'ms'
            );
        }
    }

    // =========================================================================
    // EJECUTAR TESTS
    // =========================================================================

    public function run(string $module = 'all'): void {
        // Siempre necesitamos autenticación primero
        $authOk = $this->setupAuth();

        if (!$authOk) {
            echo "\n⚠️  ADVERTENCIA: No todos los usuarios se pudieron autenticar.\n";
            echo "   Ejecuta: mysql -u root -p facilitame < tests/setup_test_data.sql\n\n";
        }

        switch ($module) {
            case 'auth':
                $this->testAuth();
                break;
            case 'asesoria':
                $this->testAsesoriaModule();
                break;
            case 'cliente':
                $this->testClienteAsesoriaModule();
                $this->testClienteSinAsesoria();
                break;
            case 'admin':
                $this->testAdminModule();
                break;
            case 'proveedor':
                $this->testProveedorModule();
                break;
            case 'comercial':
                $this->testComercialModule();
                break;
            case 'permissions':
                $this->testPermissions();
                $this->testFileDownload();
                break;
            case 'performance':
                $this->testPerformance();
                break;
            case 'all':
            default:
                $this->testAuth();
                $this->testAsesoriaModule();
                $this->testClienteAsesoriaModule();
                $this->testClienteSinAsesoria();
                $this->testAsesoriaGratuita();
                $this->testAdminModule();
                $this->testProveedorModule();
                $this->testComercialModule();
                $this->testPermissions();
                $this->testFileDownload();
                $this->testPerformance();
                break;
        }

        $this->printSummary();
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed;
        $pct = $total > 0 ? round(($this->passed / $total) * 100) : 0;

        echo "\n" . str_repeat("═", 70) . "\n";
        echo "   RESUMEN DE TESTS\n";
        echo str_repeat("═", 70) . "\n";
        echo "   Total ejecutados:  $total tests\n";
        echo "   Pasados:           {$this->passed} ✅\n";
        echo "   Fallidos:          {$this->failed} ❌\n";
        echo "   Omitidos:          {$this->skipped} ⏭️\n";
        echo "   Porcentaje éxito:  {$pct}%\n";
        echo str_repeat("═", 70) . "\n";

        if ($this->failed > 0) {
            echo "\n❌ TESTS FALLIDOS:\n";
            foreach ($this->results as $r) {
                if (isset($r['passed']) && !$r['passed']) {
                    echo "   • {$r['name']}" . ($r['details'] ? " — {$r['details']}" : "") . "\n";
                }
            }
        }

        if ($this->skipped > 0) {
            echo "\n⏭️  TESTS OMITIDOS:\n";
            foreach ($this->results as $r) {
                if ($r['passed'] === null) {
                    echo "   • {$r['name']}" . ($r['details'] ? " — {$r['details']}" : "") . "\n";
                }
            }
        }

        echo "\n";

        if ($pct === 100) {
            echo "🎉 ¡TODOS LOS TESTS PASARON!\n\n";
        } elseif ($pct >= 80) {
            echo "👍 La mayoría de tests pasaron. Revisa los fallos.\n\n";
        } else {
            echo "⚠️  Hay varios tests fallando. Revisa la configuración.\n\n";
        }
    }
}

// =========================================================================
// EJECUCIÓN
// =========================================================================

$module = $argv[1] ?? 'all';
$validModules = ['all', 'auth', 'asesoria', 'cliente', 'admin', 'proveedor', 'comercial', 'permissions', 'performance'];

if (!in_array($module, $validModules)) {
    echo "Módulo '$module' no válido.\n";
    echo "Módulos disponibles: " . implode(', ', $validModules) . "\n";
    exit(1);
}

$suite = new FacilitameTestSuite();
$suite->run($module);
