<?php
/**
 * FACILITAME - Tests de Emails
 *
 * Suite de tests para verificar que todos los emails se envían correctamente
 * y contienen los enlaces de activación/recovery funcionales.
 *
 * PREREQUISITOS:
 *   1. MailHog ejecutándose en localhost:8025 (API) y localhost:1025 (SMTP)
 *      - Docker: docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog
 *   2. Configurar SMTP en bold/vars.php:
 *      - SMTP_HOST = 'localhost'
 *      - SMTP_PORT = 1025
 *      - SMTP_USERNAME = ''
 *      - SMTP_PASSWORD = ''
 *
 *   Alternativa: Usar Mailtrap y configurar credenciales de prueba
 *
 * EJECUTAR:
 *   php tests/test_emails.php
 */

define('BASE_URL', 'http://facilitame.test');
define('MAILHOG_API', 'http://localhost:8025/api/v2');
define('TEST_TIMEOUT', 30);
define('EMAIL_WAIT_MS', 2000); // Tiempo de espera para que llegue el email

class EmailTestSuite {
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private array $results = [];
    private bool $mailhogAvailable = false;

    // Datos de prueba para registro
    private array $testUser = [
        'email' => '',
        'password' => 'TestPass123',
        'name' => 'Test User',
        'role' => 'autonomo',
        'phone' => '612345678',
        'region_code' => '+34'
    ];

    public function __construct() {
        $this->printHeader();
        $this->testUser['email'] = 'test_' . time() . '@test.com';
        $this->checkMailhog();
    }

    private function printHeader(): void {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "   FACILITAME - Tests de Emails\n";
        echo str_repeat("=", 70) . "\n\n";
    }

    private function checkMailhog(): void {
        $ch = curl_init(MAILHOG_API . '/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->mailhogAvailable = ($httpCode === 200);

        if ($this->mailhogAvailable) {
            echo "  ✅ MailHog disponible en " . MAILHOG_API . "\n\n";
        } else {
            echo "  ⚠️  MailHog NO disponible. Algunos tests serán omitidos.\n";
            echo "     Ejecuta: docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog\n\n";
        }
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
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'json' => json_decode($response, true),
            'error' => $error
        ];
    }

    private function getMailhogEmails(string $searchQuery = ''): array {
        if (!$this->mailhogAvailable) return [];

        $url = MAILHOG_API . '/search?kind=containing&query=' . urlencode($searchQuery);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['items'] ?? [];
    }

    private function clearMailhogEmails(): void {
        if (!$this->mailhogAvailable) return;

        $ch = curl_init(MAILHOG_API . '/messages');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function waitForEmail(string $recipient, int $timeoutMs = 5000): ?array {
        if (!$this->mailhogAvailable) return null;

        $start = microtime(true);
        while ((microtime(true) - $start) * 1000 < $timeoutMs) {
            $emails = $this->getMailhogEmails($recipient);
            if (!empty($emails)) {
                return $emails[0];
            }
            usleep(500000); // 500ms
        }
        return null;
    }

    private function extractLinkFromEmail(array $email, string $pattern): ?string {
        $body = $email['Content']['Body'] ?? '';
        // Decodificar si está en base64 o quoted-printable
        if (isset($email['Content']['Headers']['Content-Transfer-Encoding'])) {
            $encoding = $email['Content']['Headers']['Content-Transfer-Encoding'][0] ?? '';
            if ($encoding === 'base64') {
                $body = base64_decode($body);
            } elseif ($encoding === 'quoted-printable') {
                $body = quoted_printable_decode($body);
            }
        }

        // Buscar enlaces en el cuerpo
        preg_match_all('/href=["\']([^"\']*' . preg_quote($pattern, '/') . '[^"\']*)["\']/', $body, $matches);
        if (!empty($matches[1])) {
            return $matches[1][0];
        }

        // Buscar URLs sueltas
        preg_match_all('/https?:\/\/[^\s<>"\']+' . preg_quote($pattern, '/') . '[^\s<>"\']*/', $body, $matches);
        if (!empty($matches[0])) {
            return $matches[0][0];
        }

        return null;
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
    // TEST: REGISTRO Y ACTIVACIÓN
    // =========================================================================

    public function testSignUpAndActivation(): void {
        $this->module("REGISTRO Y ACTIVACIÓN DE CUENTA");

        if (!$this->mailhogAvailable) {
            $this->skip('Email de registro', 'MailHog no disponible');
            return;
        }

        // Limpiar emails previos
        $this->clearMailhogEmails();

        // --- Test de Registro ---
        $this->section("Registro de nuevo usuario");

        $signupData = [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password'],
            'confirm-password' => $this->testUser['password'],
            'name' => $this->testUser['name'],
            'role' => $this->testUser['role'],
            'phone' => $this->testUser['phone'],
            'region_code' => $this->testUser['region_code']
        ];

        $res = $this->request('POST', '/api/sign-up', $signupData);
        $signupOk = $this->test('Registro de usuario exitoso',
            isset($res['json']['status']) && $res['json']['status'] === 'ok',
            $res['json']['message_html'] ?? $res['json']['message'] ?? 'Error desconocido');

        if (!$signupOk) {
            $this->skip('Resto de tests de activación', 'Registro falló');
            return;
        }

        // --- Test de Email de Activación ---
        $this->section("Email de Activación");

        usleep(EMAIL_WAIT_MS * 1000); // Esperar a que llegue el email

        $email = $this->waitForEmail($this->testUser['email'], 10000);
        $emailReceived = $this->test('Email de activación recibido', $email !== null);

        if (!$emailReceived) {
            $this->skip('Resto de tests de activación', 'Email no recibido');
            return;
        }

        // Verificar asunto
        $subject = $email['Content']['Headers']['Subject'][0] ?? '';
        $this->test('Asunto contiene "Activa"', stripos($subject, 'activa') !== false, "Asunto: $subject");

        // Verificar destinatario
        $to = $email['Content']['Headers']['To'][0] ?? '';
        $this->test('Destinatario correcto', stripos($to, $this->testUser['email']) !== false, "To: $to");

        // --- Test de Enlace de Activación ---
        $this->section("Enlace de Activación");

        $activationLink = $this->extractLinkFromEmail($email, '/activate?token=');
        $linkFound = $this->test('Enlace de activación encontrado', $activationLink !== null);

        if (!$linkFound) {
            $this->skip('Test de enlace funcional', 'Enlace no encontrado en email');
            return;
        }

        echo "     Link: $activationLink\n";

        // Extraer el token
        preg_match('/token=([a-f0-9]+)/', $activationLink, $matches);
        $token = $matches[1] ?? null;
        $this->test('Token de activación extraído', $token !== null && strlen($token) > 20);

        // Verificar que el enlace responde
        $ch = curl_init($activationLink);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10
        ]);
        $pageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->test('Página de activación accesible (HTTP 200)', $httpCode === 200, "HTTP $httpCode");

        // Verificar que la página muestra formulario de contraseña
        // (porque el usuario fue creado con contraseña pero sin verificar)
        $hasForm = (stripos($pageContent, 'Nueva Contraseña') !== false) ||
                   (stripos($pageContent, 'Activar') !== false) ||
                   (stripos($pageContent, 'kt_activate_form') !== false);

        // Si no requiere contraseña (usuario ya tiene una), debe mostrar mensaje de éxito o redirigir
        if (!$hasForm) {
            $hasSuccess = (stripos($pageContent, 'login') !== false) ||
                         (stripos($pageContent, 'activada') !== false);
            $this->test('Página de activación funcional', $hasSuccess, 'Usuario ya tiene contraseña');
        } else {
            $this->test('Formulario de activación presente', $hasForm);
        }
    }

    // =========================================================================
    // TEST: RECUPERACIÓN DE CONTRASEÑA
    // =========================================================================

    public function testPasswordRecovery(): void {
        $this->module("RECUPERACIÓN DE CONTRASEÑA");

        if (!$this->mailhogAvailable) {
            $this->skip('Email de recuperación', 'MailHog no disponible');
            return;
        }

        // Usar usuario existente
        $existingEmail = 'cliente@test.com'; // Del setup_test_data.sql

        // Limpiar emails previos
        $this->clearMailhogEmails();

        // --- Test de Solicitud de Recovery ---
        $this->section("Solicitud de Recuperación");

        $res = $this->request('POST', '/api/recovery', ['email' => $existingEmail]);
        $requestOk = $this->test('Solicitud de recovery aceptada',
            isset($res['json']['status']) && $res['json']['status'] === 'ok');

        if (!$requestOk) {
            $this->skip('Resto de tests de recovery', 'Solicitud falló');
            return;
        }

        // --- Test de Email de Recovery ---
        $this->section("Email de Recuperación");

        usleep(EMAIL_WAIT_MS * 1000);

        $email = $this->waitForEmail($existingEmail, 10000);
        $emailReceived = $this->test('Email de recovery recibido', $email !== null);

        if (!$emailReceived) {
            $this->skip('Resto de tests de recovery', 'Email no recibido');
            return;
        }

        // Verificar asunto
        $subject = $email['Content']['Headers']['Subject'][0] ?? '';
        $this->test('Asunto contiene "recuperación" o "contraseña"',
            stripos($subject, 'recuperación') !== false || stripos($subject, 'contraseña') !== false,
            "Asunto: $subject");

        // --- Test de Enlace de Recovery ---
        $this->section("Enlace de Recuperación");

        $recoveryLink = $this->extractLinkFromEmail($email, '/restore?token=');
        $linkFound = $this->test('Enlace de recovery encontrado', $recoveryLink !== null);

        if (!$linkFound) {
            $this->skip('Test de enlace funcional', 'Enlace no encontrado en email');
            return;
        }

        echo "     Link: $recoveryLink\n";

        // Extraer el token
        preg_match('/token=([a-f0-9-]+)/', $recoveryLink, $matches);
        $token = $matches[1] ?? null;
        $this->test('Token de recovery extraído', $token !== null && strlen($token) > 20);

        // Verificar que el enlace responde
        $ch = curl_init($recoveryLink);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10
        ]);
        $pageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->test('Página de recovery accesible (HTTP 200)', $httpCode === 200, "HTTP $httpCode");

        // Verificar que muestra formulario de nueva contraseña
        $hasForm = (stripos($pageContent, 'Nueva contraseña') !== false) ||
                   (stripos($pageContent, 'kt_restore_form') !== false) ||
                   (stripos($pageContent, 'Restablecer') !== false);
        $this->test('Formulario de nueva contraseña presente', $hasForm);
    }

    // =========================================================================
    // TEST: EMAILS DE ADMIN/USUARIO CREADO
    // =========================================================================

    public function testAdminCreateUser(): void {
        $this->module("CREACIÓN DE USUARIO POR ADMIN");

        if (!$this->mailhogAvailable) {
            $this->skip('Email de usuario creado por admin', 'MailHog no disponible');
            return;
        }

        // Primero login como admin
        $res = $this->request('POST', '/api/login', [
            'email' => 'admin@test.com',
            'password' => 'test123'
        ]);

        if (!isset($res['json']['data']['token'])) {
            $this->skip('Tests de admin', 'No se pudo autenticar como admin');
            return;
        }

        $adminToken = $res['json']['data']['token'];
        $newUserEmail = 'staff_' . time() . '@test.com';

        // Limpiar emails
        $this->clearMailhogEmails();

        // --- Test de Creación ---
        $this->section("Crear Usuario Staff");

        $res = $this->request('POST', '/api/users-add', [
            'type' => 'sales-rep',
            'name' => 'Staff Test',
            'lastname' => 'Usuario',
            'email' => $newUserEmail,
            'phone' => '612345999',
            'code' => 'TEST-' . time()
        ], $adminToken);

        $createOk = $this->test('Usuario staff creado',
            isset($res['json']['status']) && $res['json']['status'] === 'ok',
            $res['json']['message_html'] ?? $res['json']['message'] ?? '');

        if (!$createOk) {
            $this->skip('Resto de tests', 'Creación falló');
            return;
        }

        // --- Test de Email ---
        $this->section("Email de Activación para Staff");

        usleep(EMAIL_WAIT_MS * 1000);

        $email = $this->waitForEmail($newUserEmail, 10000);
        $emailReceived = $this->test('Email de activación recibido', $email !== null);

        if (!$emailReceived) {
            $this->skip('Resto de tests', 'Email no recibido');
            return;
        }

        // Verificar enlace
        $activationLink = $this->extractLinkFromEmail($email, '/activate?token=');
        $this->test('Enlace de activación encontrado', $activationLink !== null);

        if ($activationLink) {
            echo "     Link: $activationLink\n";
        }
    }

    // =========================================================================
    // TEST: EMAILS DE NOTIFICACIONES
    // =========================================================================

    public function testNotificationEmails(): void {
        $this->module("EMAILS DE NOTIFICACIONES");

        if (!$this->mailhogAvailable) {
            $this->skip('Emails de notificaciones', 'MailHog no disponible');
            return;
        }

        // Login como asesoría
        $res = $this->request('POST', '/api/login', [
            'email' => 'asesoria@test.com',
            'password' => 'test123'
        ]);

        if (!isset($res['json']['data']['token'])) {
            $this->skip('Tests de notificaciones', 'No se pudo autenticar como asesoría');
            return;
        }

        $asesoriaToken = $res['json']['data']['token'];

        // Limpiar emails
        $this->clearMailhogEmails();

        // --- Test de Comunicación Masiva ---
        $this->section("Comunicación a Clientes");

        $res = $this->request('POST', '/api/advisory-send-communication', [
            'subject' => 'Test de comunicación ' . date('Y-m-d H:i:s'),
            'message' => 'Este es un mensaje de prueba automático para verificar el envío de emails.',
            'importance' => 'media',
            'recipient_filter' => 'todos'
        ], $asesoriaToken);

        $sendOk = $this->test('Comunicación enviada',
            isset($res['json']['status']) && $res['json']['status'] === 'ok');

        if ($sendOk) {
            usleep(EMAIL_WAIT_MS * 2000); // Dar más tiempo para múltiples emails

            $emails = $this->getMailhogEmails('comunicación');
            $this->test('Al menos un email de comunicación enviado', count($emails) > 0,
                "Emails encontrados: " . count($emails));
        }
    }

    // =========================================================================
    // TEST: VERIFICACIÓN DE CONTENIDO DE EMAILS
    // =========================================================================

    public function testEmailContent(): void {
        $this->module("VERIFICACIÓN DE CONTENIDO");

        if (!$this->mailhogAvailable) {
            $this->skip('Verificación de contenido', 'MailHog no disponible');
            return;
        }

        $this->section("Análisis de Emails Enviados");

        $emails = $this->getMailhogEmails('');

        if (empty($emails)) {
            $this->skip('Análisis de contenido', 'No hay emails para analizar');
            return;
        }

        echo "     Analizando " . count($emails) . " email(s) en MailHog...\n\n";

        foreach (array_slice($emails, 0, 5) as $i => $email) {
            $subject = $email['Content']['Headers']['Subject'][0] ?? 'Sin asunto';
            $to = $email['Content']['Headers']['To'][0] ?? 'Desconocido';
            $from = $email['Content']['Headers']['From'][0] ?? 'Desconocido';

            echo "     📧 Email #" . ($i + 1) . ":\n";
            echo "        Asunto: $subject\n";
            echo "        De: $from\n";
            echo "        Para: $to\n";

            // Verificar que tiene contenido HTML
            $body = $email['Content']['Body'] ?? '';
            $hasHtml = (stripos($body, '<html') !== false) ||
                       (stripos($body, '<p>') !== false) ||
                       (stripos($body, '<a ') !== false);

            if ($hasHtml) {
                echo "        Formato: HTML ✓\n";
            } else {
                echo "        Formato: Texto plano\n";
            }
            echo "\n";
        }

        $this->test('Emails presentes en MailHog', count($emails) > 0);
    }

    // =========================================================================
    // EJECUTAR
    // =========================================================================

    public function run(): void {
        $this->testSignUpAndActivation();
        $this->testPasswordRecovery();
        $this->testAdminCreateUser();
        $this->testNotificationEmails();
        $this->testEmailContent();

        $this->printSummary();
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed;
        $pct = $total > 0 ? round(($this->passed / $total) * 100) : 0;

        echo "\n" . str_repeat("═", 70) . "\n";
        echo "   RESUMEN DE TESTS DE EMAILS\n";
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
        echo "📋 INSTRUCCIONES:\n";
        echo "   1. Instala MailHog: docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog\n";
        echo "   2. Configura SMTP en bold/vars.php: SMTP_HOST='localhost', SMTP_PORT=1025\n";
        echo "   3. Ejecuta: php tests/test_emails.php\n";
        echo "   4. Ver emails en: http://localhost:8025\n\n";
    }
}

// Ejecutar
$suite = new EmailTestSuite();
$suite->run();
