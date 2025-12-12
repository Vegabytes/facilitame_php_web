<?php
/**
 * API: Configuracion de Inmatic para Asesorias
 *
 * Acciones:
 * - GET (sin action): Obtener configuracion actual
 * - POST action=save: Guardar configuracion
 * - POST action=test: Probar conexion
 * - POST action=disable: Desactivar integracion
 */

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id y plan
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advisory) {
    json_response("ko", "Asesoría no encontrada", 404);
}

// Verificar plan - solo Pro, Premium y Enterprise tienen Inmatic
$planesConInmatic = ['pro', 'premium', 'enterprise'];
if (!in_array($advisory['plan'], $planesConInmatic)) {
    json_response("ko", "Tu plan no incluye integración con Inmatic. Actualiza a Pro o superior.", 403, [
        'current_plan' => $advisory['plan'],
        'required_plans' => $planesConInmatic
    ]);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save':
        // Guardar configuracion
        $token = trim($_POST['inmatic_token'] ?? '');
        $companyId = trim($_POST['inmatic_company_id'] ?? '');

        // Verificar si ya existe configuracion
        $stmt = $pdo->prepare("SELECT id, inmatic_token FROM advisory_inmatic_config WHERE advisory_id = ?");
        $stmt->execute([$advisory['id']]);
        $existing = $stmt->fetch();

        // Si es nuevo, el token es obligatorio
        if (!$existing && empty($token)) {
            json_response("ko", "El token de Inmatic es obligatorio", 400);
        }

        // Validar token si se proporciona uno nuevo
        if (!empty($token) && strlen($token) < 20) {
            json_response("ko", "El token parece inválido (muy corto)", 400);
        }

        try {
            $pdo->beginTransaction();

            if ($existing) {
                // Actualizar - solo cambiar token si se proporciona uno nuevo
                if (!empty($token)) {
                    $stmt = $pdo->prepare("
                        UPDATE advisory_inmatic_config
                        SET inmatic_token = ?, inmatic_company_id = ?, is_active = 1, updated_at = NOW()
                        WHERE advisory_id = ?
                    ");
                    $stmt->execute([$token, $companyId ?: null, $advisory['id']]);
                } else {
                    // Solo actualizar company_id sin tocar el token
                    $stmt = $pdo->prepare("
                        UPDATE advisory_inmatic_config
                        SET inmatic_company_id = ?, is_active = 1, updated_at = NOW()
                        WHERE advisory_id = ?
                    ");
                    $stmt->execute([$companyId ?: null, $advisory['id']]);
                }
            } else {
                // Insertar
                $stmt = $pdo->prepare("
                    INSERT INTO advisory_inmatic_config (advisory_id, inmatic_token, inmatic_company_id, is_active)
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$advisory['id'], $token, $companyId ?: null]);
            }

            $pdo->commit();

            // Intentar probar la conexion y configurar webhook
            $connectionOk = false;
            $connectionError = '';
            $webhookCreated = false;
            $webhookError = '';

            try {
                require_once ROOT_DIR . '/bold/classes/InmaticClient.php';
                $client = new InmaticClient($advisory['id']);
                $connectionOk = $client->testConnection();

                // Si la conexión es exitosa, intentar crear webhook automáticamente
                if ($connectionOk) {
                    try {
                        // Verificar si ya existe un webhook
                        $existingWebhooks = $client->listWebhooks();
                        $webhookUrl = ROOT_URL . '/api/webhook-inmatic';
                        $webhookExists = false;

                        if (isset($existingWebhooks['data']) && is_array($existingWebhooks['data'])) {
                            foreach ($existingWebhooks['data'] as $wh) {
                                if (isset($wh['url']) && strpos($wh['url'], 'webhook-inmatic') !== false) {
                                    $webhookExists = true;
                                    break;
                                }
                            }
                        }

                        if (!$webhookExists) {
                            $client->createWebhook($webhookUrl, [
                                'document.processed',
                                'document.approved',
                                'document.rejected',
                                'document.exported'
                            ]);
                            $webhookCreated = true;
                        } else {
                            $webhookCreated = true; // Ya existía
                        }
                    } catch (Exception $webhookEx) {
                        $webhookError = $webhookEx->getMessage();
                        // No es crítico, continuamos
                    }
                }
            } catch (Exception $e) {
                $connectionError = $e->getMessage();
            }

            json_response("ok", "Configuración guardada correctamente", 200, [
                'connection_tested' => true,
                'connection_ok' => $connectionOk,
                'connection_error' => $connectionError,
                'webhook_configured' => $webhookCreated,
                'webhook_error' => $webhookError
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            json_response("ko", "Error al guardar: " . $e->getMessage(), 500);
        }
        break;

    case 'test':
        // Probar conexion
        try {
            require_once ROOT_DIR . '/bold/classes/InmaticClient.php';
            $client = new InmaticClient($advisory['id']);

            if ($client->testConnection()) {
                // Intentar obtener info de empresas para dar mas detalles
                $companies = $client->listCompanies();

                json_response("ok", "Conexión exitosa con Inmatic", 200, [
                    'companies_count' => isset($companies['data']) ? count($companies['data']) : 0
                ]);
            } else {
                json_response("ko", "No se pudo conectar con Inmatic", 400);
            }
        } catch (Exception $e) {
            json_response("ko", "Error de conexión: " . $e->getMessage(), 400);
        }
        break;

    case 'disable':
        // Desactivar integracion
        $stmt = $pdo->prepare("
            UPDATE advisory_inmatic_config
            SET is_active = 0, updated_at = NOW()
            WHERE advisory_id = ?
        ");
        $stmt->execute([$advisory['id']]);

        json_response("ok", "Integración con Inmatic desactivada", 200);
        break;

    case 'webhooks':
        // Gestionar webhooks
        $webhookAction = $_POST['webhook_action'] ?? '';

        try {
            require_once ROOT_DIR . '/bold/classes/InmaticClient.php';
            $client = new InmaticClient($advisory['id']);

            if ($webhookAction === 'create') {
                $webhookUrl = ROOT_URL . '/api/webhook-inmatic?advisory_id=' . $advisory['id'];
                $result = $client->createWebhook($webhookUrl, [
                    'document.processed',
                    'document.approved',
                    'document.rejected'
                ]);
                json_response("ok", "Webhook creado", 200, $result);

            } elseif ($webhookAction === 'list') {
                $result = $client->listWebhooks();
                json_response("ok", "", 200, $result);

            } elseif ($webhookAction === 'delete') {
                $webhookId = $_POST['webhook_id'] ?? '';
                if (empty($webhookId)) {
                    json_response("ko", "ID de webhook requerido", 400);
                }
                $client->deleteWebhook($webhookId);
                json_response("ok", "Webhook eliminado", 200);

            } else {
                json_response("ko", "Acción de webhook no válida", 400);
            }
        } catch (Exception $e) {
            json_response("ko", $e->getMessage(), 400);
        }
        break;

    default:
        // GET - Obtener configuracion actual
        $stmt = $pdo->prepare("
            SELECT inmatic_company_id, is_active, created_at, updated_at
            FROM advisory_inmatic_config
            WHERE advisory_id = ?
        ");
        $stmt->execute([$advisory['id']]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener estadisticas de documentos enviados
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processed' => 0,
            'error' => 0
        ];

        if ($config) {
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN inmatic_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN inmatic_status IN ('processed', 'approved', 'exported') THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN inmatic_status = 'error' THEN 1 ELSE 0 END) as error
                FROM advisory_inmatic_documents aid
                JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
                WHERE ai.advisory_id = ?
            ");
            $stmt->execute([$advisory['id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        json_response("ok", "", 200, [
            'configured' => (bool)$config,
            'is_active' => $config ? (bool)$config['is_active'] : false,
            'company_id' => $config ? $config['inmatic_company_id'] : null,
            'created_at' => $config ? $config['created_at'] : null,
            'updated_at' => $config ? $config['updated_at'] : null,
            'stats' => $stats
        ]);
        break;
}
