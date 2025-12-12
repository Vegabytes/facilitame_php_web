# Implementacion de Integracion con Inmatic

**Version:** 1.0
**Fecha:** 12 de Diciembre 2025
**Basado en:** API Inmatic v1.0.15

---

## 1. RESUMEN EJECUTIVO

### 1.1 Que es Inmatic?

Inmatic es un servicio de gestion documental con OCR que permite:
- Subir facturas y documentos
- Extraer datos automaticamente mediante OCR
- Gestionar proveedores y clientes
- Integrar con sistemas de contabilidad

### 1.2 Objetivo de la Integracion

Permitir que las **asesorias** de Facilitame envien automaticamente las facturas de sus clientes a Inmatic para:
1. Procesamiento OCR automatico
2. Clasificacion de gastos/ingresos
3. Preparacion para contabilidad

### 1.3 Planes que Incluyen Inmatic

Segun el documento de requisitos de asesorias:

| Plan | Incluye Inmatic |
|------|-----------------|
| Gratuito | No |
| Basic | No |
| Estandar | No |
| Pro | Si |
| Premium | Si |
| Enterprise | Si |

---

## 2. DOCUMENTACION API INMATIC

### 2.1 Informacion General

- **URL Base:** `https://api.inmatic.ai/api`
- **Autenticacion:** Bearer Token
- **Content-Type:** `application/json` (excepto uploads)
- **Version API:** 1.0.15

### 2.2 Autenticacion

```
Header: Authorization: Bearer {token}
```

El token se obtiene desde el panel de Inmatic y debe almacenarse de forma segura.

### 2.3 Endpoints Principales

#### Documentos

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/documents` | Listar documentos |
| GET | `/documents/{id}` | Obtener documento |
| POST | `/documents` | Subir documento |
| POST | `/documents/{id}/change-state` | Cambiar estado |
| DELETE | `/documents/{id}` | Eliminar documento |

#### Empresas

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/companies` | Listar empresas |
| GET | `/companies/{id}` | Obtener empresa |
| POST | `/companies` | Crear empresa |
| PUT | `/companies/{id}` | Actualizar empresa |
| DELETE | `/companies/{id}` | Eliminar empresa |

#### Proveedores

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/companies/{id}/suppliers` | Listar proveedores |
| POST | `/companies/{id}/suppliers` | Crear proveedor |
| PUT | `/suppliers/{id}` | Actualizar proveedor |
| DELETE | `/suppliers/{id}` | Eliminar proveedor |

#### Clientes

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/companies/{id}/customers` | Listar clientes |
| POST | `/companies/{id}/customers` | Crear cliente |
| PUT | `/customers/{id}` | Actualizar cliente |
| DELETE | `/customers/{id}` | Eliminar cliente |

#### Webhooks

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/webhook-subscriptions` | Listar suscripciones |
| POST | `/webhook-subscriptions` | Crear suscripcion |
| DELETE | `/webhook-subscriptions/{id}` | Eliminar suscripcion |

### 2.4 Estados de Documentos

| Estado | Descripcion |
|--------|-------------|
| pending | Pendiente de procesar |
| processing | En proceso OCR |
| processed | Procesado exitosamente |
| review | Requiere revision manual |
| approved | Aprobado |
| rejected | Rechazado |
| exported | Exportado a contabilidad |

### 2.5 Tipos de Documentos

| Tipo | Descripcion |
|------|-------------|
| invoice | Factura |
| receipt | Recibo |
| credit_note | Nota de credito |
| debit_note | Nota de debito |
| other | Otro documento |

---

## 3. PLAN DE IMPLEMENTACION

### 3.1 Fase 1: Configuracion Base

#### 3.1.1 Estructura de Base de Datos

```sql
-- Tabla para almacenar configuracion de Inmatic por asesoria
CREATE TABLE advisory_inmatic_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advisory_id BIGINT UNSIGNED NOT NULL,
    inmatic_token VARCHAR(500) NOT NULL,
    inmatic_company_id VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE
);

-- Tabla para mapear clientes Facilitame con clientes Inmatic
CREATE TABLE advisory_inmatic_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advisory_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    inmatic_customer_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer (advisory_id, customer_id),
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE
);

-- Tabla para tracking de documentos enviados a Inmatic
CREATE TABLE advisory_inmatic_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advisory_invoice_id INT NOT NULL,
    inmatic_document_id VARCHAR(100) NOT NULL,
    inmatic_status VARCHAR(50) DEFAULT 'pending',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    ocr_data JSON,
    error_message TEXT,
    FOREIGN KEY (advisory_invoice_id) REFERENCES advisory_invoices(id) ON DELETE CASCADE
);
```

#### 3.1.2 Clase PHP para API Inmatic

```php
<?php
// /bold/classes/InmaticClient.php

class InmaticClient {
    private $baseUrl = 'https://api.inmatic.ai/api';
    private $token;
    private $advisoryId;

    public function __construct($advisoryId) {
        global $pdo;
        $this->advisoryId = $advisoryId;

        // Obtener token de la configuracion
        $stmt = $pdo->prepare("SELECT inmatic_token FROM advisory_inmatic_config
                              WHERE advisory_id = ? AND is_active = 1");
        $stmt->execute([$advisoryId]);
        $config = $stmt->fetch();

        if (!$config) {
            throw new Exception("Inmatic no configurado para esta asesoria");
        }

        $this->token = $config['inmatic_token'];
    }

    /**
     * Realizar peticion HTTP a la API
     */
    private function request($method, $endpoint, $data = null, $isMultipart = false) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->token,
        ];

        if (!$isMultipart) {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($isMultipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error de conexion: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = isset($decoded['message']) ? $decoded['message'] : 'Error desconocido';
            throw new Exception("Error API Inmatic ($httpCode): " . $errorMsg);
        }

        return $decoded;
    }

    /**
     * Subir documento/factura
     */
    public function uploadDocument($filePath, $fileName, $type = 'invoice', $metadata = []) {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: " . $filePath);
        }

        $data = [
            'file' => new CURLFile($filePath, mime_content_type($filePath), $fileName),
            'type' => $type
        ];

        // Agregar metadata opcional
        foreach ($metadata as $key => $value) {
            $data[$key] = $value;
        }

        return $this->request('POST', '/documents', $data, true);
    }

    /**
     * Obtener estado de documento
     */
    public function getDocument($documentId) {
        return $this->request('GET', '/documents/' . $documentId);
    }

    /**
     * Listar documentos con filtros
     */
    public function listDocuments($params = []) {
        $query = http_build_query($params);
        return $this->request('GET', '/documents' . ($query ? '?' . $query : ''));
    }

    /**
     * Cambiar estado de documento
     */
    public function changeDocumentState($documentId, $newState) {
        return $this->request('POST', '/documents/' . $documentId . '/change-state', [
            'state' => $newState
        ]);
    }

    /**
     * Crear empresa en Inmatic
     */
    public function createCompany($data) {
        return $this->request('POST', '/companies', $data);
    }

    /**
     * Crear cliente para una empresa
     */
    public function createCustomer($companyId, $data) {
        return $this->request('POST', '/companies/' . $companyId . '/customers', $data);
    }

    /**
     * Crear proveedor para una empresa
     */
    public function createSupplier($companyId, $data) {
        return $this->request('POST', '/companies/' . $companyId . '/suppliers', $data);
    }

    /**
     * Crear suscripcion a webhook
     */
    public function createWebhookSubscription($url, $events = ['document.processed']) {
        return $this->request('POST', '/webhook-subscriptions', [
            'url' => $url,
            'events' => $events
        ]);
    }

    /**
     * Verificar conexion y token
     */
    public function testConnection() {
        try {
            $this->request('GET', '/companies');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
```

### 3.2 Fase 2: APIs de Integracion

#### 3.2.1 API para Configurar Inmatic

```php
<?php
// /api/advisory-inmatic-config.php

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ko", "Asesoria no encontrada", 404);
}

// Verificar plan
$planesConInmatic = ['pro', 'premium', 'enterprise'];
if (!in_array($advisory['plan'], $planesConInmatic)) {
    json_response("ko", "Tu plan no incluye integracion con Inmatic. Actualiza a Pro o superior.", 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        $token = trim($_POST['inmatic_token'] ?? '');
        $companyId = trim($_POST['inmatic_company_id'] ?? '');

        if (empty($token)) {
            json_response("ko", "El token es obligatorio", 400);
        }

        // Verificar si ya existe configuracion
        $stmt = $pdo->prepare("SELECT id FROM advisory_inmatic_config WHERE advisory_id = ?");
        $stmt->execute([$advisory['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Actualizar
            $stmt = $pdo->prepare("UPDATE advisory_inmatic_config
                                  SET inmatic_token = ?, inmatic_company_id = ?, is_active = 1
                                  WHERE advisory_id = ?");
            $stmt->execute([$token, $companyId, $advisory['id']]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("INSERT INTO advisory_inmatic_config
                                  (advisory_id, inmatic_token, inmatic_company_id)
                                  VALUES (?, ?, ?)");
            $stmt->execute([$advisory['id'], $token, $companyId]);
        }

        // Probar conexion
        try {
            $client = new InmaticClient($advisory['id']);
            if ($client->testConnection()) {
                json_response("ok", "Configuracion guardada y conexion verificada", 200);
            } else {
                json_response("ko", "Token guardado pero no se pudo verificar la conexion", 200);
            }
        } catch (Exception $e) {
            json_response("ko", "Error al verificar conexion: " . $e->getMessage(), 200);
        }
        break;

    case 'test':
        try {
            $client = new InmaticClient($advisory['id']);
            if ($client->testConnection()) {
                json_response("ok", "Conexion exitosa con Inmatic", 200);
            } else {
                json_response("ko", "No se pudo conectar con Inmatic", 400);
            }
        } catch (Exception $e) {
            json_response("ko", $e->getMessage(), 400);
        }
        break;

    case 'disable':
        $stmt = $pdo->prepare("UPDATE advisory_inmatic_config SET is_active = 0 WHERE advisory_id = ?");
        $stmt->execute([$advisory['id']]);
        json_response("ok", "Integracion desactivada", 200);
        break;

    default:
        // GET - Obtener configuracion actual
        $stmt = $pdo->prepare("SELECT inmatic_company_id, is_active, created_at, updated_at
                              FROM advisory_inmatic_config WHERE advisory_id = ?");
        $stmt->execute([$advisory['id']]);
        $config = $stmt->fetch();

        json_response("ok", "", 200, [
            'configured' => (bool)$config,
            'is_active' => $config ? (bool)$config['is_active'] : false,
            'company_id' => $config ? $config['inmatic_company_id'] : null,
            'last_updated' => $config ? $config['updated_at'] : null
        ]);
}
```

#### 3.2.2 API para Enviar Factura a Inmatic

```php
<?php
// /api/advisory-invoice-send-to-inmatic.php

if (!asesoria()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$invoiceId = intval($_POST['invoice_id'] ?? 0);

if (!$invoiceId) {
    json_response("ko", "ID de factura requerido", 400);
}

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response("ko", "Asesoria no encontrada", 404);
}

// Verificar plan
$planesConInmatic = ['pro', 'premium', 'enterprise'];
if (!in_array($advisory['plan'], $planesConInmatic)) {
    json_response("ko", "Tu plan no incluye integracion con Inmatic", 403);
}

// Obtener factura
$stmt = $pdo->prepare("SELECT * FROM advisory_invoices WHERE id = ? AND advisory_id = ?");
$stmt->execute([$invoiceId, $advisory['id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    json_response("ko", "Factura no encontrada", 404);
}

// Verificar que no se haya enviado ya
$stmt = $pdo->prepare("SELECT id, inmatic_status FROM advisory_inmatic_documents WHERE advisory_invoice_id = ?");
$stmt->execute([$invoiceId]);
$existing = $stmt->fetch();

if ($existing && $existing['inmatic_status'] !== 'error') {
    json_response("ko", "Esta factura ya fue enviada a Inmatic (Estado: " . $existing['inmatic_status'] . ")", 400);
}

try {
    $client = new InmaticClient($advisory['id']);

    // Construir ruta del archivo
    $filePath = ROOT_DIR . '/' . DOCUMENTS_DIR . '/' . $invoice['filename'];

    if (!file_exists($filePath)) {
        json_response("ko", "Archivo de factura no encontrado", 404);
    }

    // Determinar tipo de documento
    $documentType = $invoice['type'] === 'ingreso' ? 'invoice' : 'receipt';

    // Metadata adicional
    $metadata = [
        'external_id' => 'facilitame_' . $invoiceId,
        'customer_id' => $invoice['customer_id'],
        'month' => $invoice['month'],
        'year' => $invoice['year'],
        'tag' => $invoice['tag']
    ];

    // Enviar a Inmatic
    $result = $client->uploadDocument($filePath, $invoice['original_name'], $documentType, $metadata);

    // Guardar referencia
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE advisory_inmatic_documents
                              SET inmatic_document_id = ?, inmatic_status = 'pending',
                                  sent_at = NOW(), error_message = NULL
                              WHERE advisory_invoice_id = ?");
        $stmt->execute([$result['id'], $invoiceId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO advisory_inmatic_documents
                              (advisory_invoice_id, inmatic_document_id, inmatic_status)
                              VALUES (?, ?, 'pending')");
        $stmt->execute([$invoiceId, $result['id']]);
    }

    json_response("ok", "Factura enviada a Inmatic correctamente", 200, [
        'inmatic_document_id' => $result['id']
    ]);

} catch (Exception $e) {
    // Guardar error
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE advisory_inmatic_documents
                              SET inmatic_status = 'error', error_message = ?
                              WHERE advisory_invoice_id = ?");
        $stmt->execute([$e->getMessage(), $invoiceId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO advisory_inmatic_documents
                              (advisory_invoice_id, inmatic_document_id, inmatic_status, error_message)
                              VALUES (?, '', 'error', ?)");
        $stmt->execute([$invoiceId, $e->getMessage()]);
    }

    json_response("ko", "Error al enviar a Inmatic: " . $e->getMessage(), 500);
}
```

#### 3.2.3 API Webhook para Recibir Actualizaciones

```php
<?php
// /api/webhook-inmatic.php

// Este endpoint recibe notificaciones de Inmatic cuando un documento cambia de estado

// Verificar metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Obtener payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    exit;
}

// Log para debugging
$logFile = ROOT_DIR . '/logs/inmatic-webhook.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $payload . "\n", FILE_APPEND);

global $pdo;

try {
    $event = $data['event'] ?? '';
    $documentId = $data['document_id'] ?? '';
    $status = $data['status'] ?? '';
    $ocrData = $data['ocr_data'] ?? null;

    if (empty($documentId)) {
        http_response_code(400);
        exit;
    }

    // Buscar documento en nuestra BD
    $stmt = $pdo->prepare("SELECT id, advisory_invoice_id FROM advisory_inmatic_documents
                          WHERE inmatic_document_id = ?");
    $stmt->execute([$documentId]);
    $doc = $stmt->fetch();

    if (!$doc) {
        // Documento no encontrado, posiblemente de otra integracion
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
        exit;
    }

    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE advisory_inmatic_documents
                          SET inmatic_status = ?, processed_at = NOW(), ocr_data = ?
                          WHERE id = ?");
    $stmt->execute([$status, json_encode($ocrData), $doc['id']]);

    // Actualizar factura como procesada si el estado es exitoso
    if (in_array($status, ['processed', 'approved', 'exported'])) {
        $stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ?");
        $stmt->execute([$doc['advisory_invoice_id']]);
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
}
```

### 3.3 Fase 3: Interfaz de Usuario

#### 3.3.1 Pagina de Configuracion

Crear `/pages/asesoria/inmatic-config.php` con:
- Formulario para ingresar token de API
- Boton para probar conexion
- Estado de la integracion
- Historico de sincronizaciones

#### 3.3.2 Modificar Pagina de Facturas

En `/pages/asesoria/customer-invoices.php`:
- Agregar columna "Estado Inmatic"
- Boton "Enviar a Inmatic" por factura
- Boton "Enviar todas pendientes"
- Indicador visual del estado

### 3.4 Fase 4: Automatizacion

#### 3.4.1 Envio Automatico

Opcionalmente, configurar envio automatico al subir factura:

```php
// En advisory-upload-customer-invoices.php, despues de guardar:

// Verificar si la asesoria tiene Inmatic activo
$stmt = $pdo->prepare("SELECT is_active FROM advisory_inmatic_config WHERE advisory_id = ?");
$stmt->execute([$advisory_id]);
$inmaticConfig = $stmt->fetch();

if ($inmaticConfig && $inmaticConfig['is_active']) {
    try {
        $client = new InmaticClient($advisory_id);
        $result = $client->uploadDocument($dest_path, $fileName, $documentType);

        // Guardar referencia
        $stmt = $pdo->prepare("INSERT INTO advisory_inmatic_documents
                              (advisory_invoice_id, inmatic_document_id, inmatic_status)
                              VALUES (?, ?, 'pending')");
        $stmt->execute([$invoiceId, $result['id']]);
    } catch (Exception $e) {
        // Log error pero no fallar el upload
        error_log("Error enviando a Inmatic: " . $e->getMessage());
    }
}
```

#### 3.4.2 Cron Job para Sincronizacion

```php
<?php
// /cron/sync-inmatic-status.php
// Ejecutar cada 15 minutos: */15 * * * * php /path/to/sync-inmatic-status.php

require_once __DIR__ . '/../config.php';

global $pdo;

// Obtener documentos pendientes
$stmt = $pdo->query("
    SELECT aid.id, aid.inmatic_document_id, aid.advisory_invoice_id,
           a.id as advisory_id
    FROM advisory_inmatic_documents aid
    JOIN advisory_invoices ai ON aid.advisory_invoice_id = ai.id
    JOIN advisories a ON ai.advisory_id = a.id
    WHERE aid.inmatic_status IN ('pending', 'processing', 'review')
    AND aid.sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
");

$documents = $stmt->fetchAll();

foreach ($documents as $doc) {
    try {
        $client = new InmaticClient($doc['advisory_id']);
        $result = $client->getDocument($doc['inmatic_document_id']);

        if ($result && isset($result['status'])) {
            $stmt = $pdo->prepare("UPDATE advisory_inmatic_documents
                                  SET inmatic_status = ?, processed_at = NOW(), ocr_data = ?
                                  WHERE id = ?");
            $stmt->execute([
                $result['status'],
                json_encode($result['ocr_data'] ?? null),
                $doc['id']
            ]);

            // Actualizar factura si fue procesada
            if (in_array($result['status'], ['processed', 'approved', 'exported'])) {
                $stmt = $pdo->prepare("UPDATE advisory_invoices SET is_processed = 1 WHERE id = ?");
                $stmt->execute([$doc['advisory_invoice_id']]);
            }
        }
    } catch (Exception $e) {
        error_log("Error sincronizando documento Inmatic {$doc['inmatic_document_id']}: " . $e->getMessage());
    }
}

echo "Sincronizacion completada: " . count($documents) . " documentos verificados\n";
```

---

## 4. CONFIGURACION REQUERIDA

### 4.1 Variables de Entorno

```php
// config.php
define('INMATIC_WEBHOOK_SECRET', 'tu_secreto_webhook'); // Para validar webhooks
define('INMATIC_AUTO_SEND', true); // Enviar automaticamente al subir
```

### 4.2 Permisos de Archivos

- El directorio de documentos debe ser accesible para lectura
- El directorio de logs debe ser escribible

### 4.3 Configuracion de Webhook en Inmatic

En el panel de Inmatic, configurar webhook apuntando a:
```
https://tu-dominio.com/api/webhook-inmatic
```

Eventos a suscribir:
- `document.processed`
- `document.approved`
- `document.rejected`
- `document.exported`

---

## 5. FLUJO COMPLETO

```
1. Asesoria configura token de Inmatic (una vez)
   ↓
2. Cliente o Asesoria sube factura
   ↓
3. Factura se guarda en advisory_invoices
   ↓
4. [Manual o Automatico] Se envia a Inmatic
   ↓
5. Se registra en advisory_inmatic_documents con status=pending
   ↓
6. Inmatic procesa documento con OCR
   ↓
7. Inmatic envia webhook con resultado
   ↓
8. Se actualiza advisory_inmatic_documents con datos OCR
   ↓
9. Asesoria puede ver datos extraidos
```

---

## 6. ESTIMACION DE ESFUERZO

| Tarea | Tiempo Estimado |
|-------|-----------------|
| Crear tablas BD | 1 hora |
| Implementar InmaticClient.php | 3 horas |
| APIs de configuracion | 2 horas |
| API de envio de facturas | 2 horas |
| Webhook receiver | 2 horas |
| Interfaz configuracion | 3 horas |
| Modificar listado facturas | 2 horas |
| Cron de sincronizacion | 1 hora |
| Testing y debugging | 4 horas |
| **TOTAL** | **~20 horas** |

---

## 7. CONSIDERACIONES DE SEGURIDAD

1. **Token de API:**
   - Almacenar encriptado en BD
   - No exponer en frontend
   - Rotar periodicamente

2. **Webhook:**
   - Validar firma/secret si Inmatic lo soporta
   - Limitar IPs origen si es posible
   - Log de todas las peticiones

3. **Permisos:**
   - Solo asesorias con plan Pro+ pueden usar
   - Verificar propiedad de facturas

---

## 8. PROXIMOS PASOS

1. Obtener credenciales de prueba de Inmatic
2. Ejecutar migraciones de BD
3. Implementar InmaticClient.php
4. Crear APIs
5. Testing con documentos reales
6. Deploy a produccion

---

## 9. SINCRONIZACION DE CLIENTES

### 9.1 Descripcion

Cuando una asesoria crea o vincula un cliente en Facilitame, ese cliente se sincroniza automaticamente con Inmatic (si la integracion esta configurada).

### 9.2 Flujo

```
1. Asesoria crea cliente nuevo (advisory-create-customer.php)
   O vincula cliente existente (advisory-link-customer.php)
   ↓
2. Se ejecuta syncCustomerToInmatic()
   ↓
3. Verifica que Inmatic este configurado y plan sea Pro+
   ↓
4. Si el cliente ya existe en Inmatic → se actualiza
   Si no existe → se crea
   ↓
5. Se guarda la relacion en advisory_inmatic_customers
```

### 9.3 Datos Sincronizados

| Campo Facilitame | Campo Inmatic | Descripcion |
|------------------|---------------|-------------|
| name + lastname | name | Nombre completo |
| email | email | Correo electronico |
| phone | phone | Telefono |
| nif_cif | tax_id | NIF/CIF |
| id | external_id | facilitame_customer_{id} |
| client_type | type | Mapeo de tipos |

### 9.4 Mapeo de Tipos de Cliente

| Facilitame | Inmatic |
|------------|---------|
| autonomo | freelancer |
| empresa | company |
| particular | individual |
| comunidad | community |
| asociacion | association |

### 9.5 Funcion syncCustomerToInmatic()

Ubicacion: `bold/functions.php`

```php
function syncCustomerToInmatic($advisory_id, $customer_id, $customer_data)
```

**Parametros:**
- `$advisory_id`: ID de la asesoria
- `$customer_id`: ID del cliente en Facilitame
- `$customer_data`: Array con name, email, phone, nif_cif, client_type

**Comportamiento:**
- Si Inmatic no esta configurado → retorna false silenciosamente
- Si el plan no incluye Inmatic → retorna false
- Si falla la conexion → registra error en log pero no interrumpe el flujo
- El cliente se crea en Facilitame aunque falle Inmatic

### 9.6 Archivos Modificados

| Archivo | Modificacion |
|---------|--------------|
| `api/advisory-create-customer.php` | Llama a syncCustomerToInmatic() despues de crear |
| `api/advisory-link-customer.php` | Llama a syncCustomerToInmatic() despues de vincular |
| `bold/functions.php` | Nueva funcion syncCustomerToInmatic() |

### 9.7 Tabla de Vinculacion

```sql
CREATE TABLE advisory_inmatic_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advisory_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    inmatic_customer_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer (advisory_id, customer_id)
);
```

### 9.8 Logs

Los errores de sincronizacion se registran en:
```
/logs/inmatic-sync.log
```

Formato:
```
2025-12-12 10:30:00 | ERROR syncCustomerToInmatic | Advisory: 5, Customer: 123 | Error message
```

---

**Fin del documento**
