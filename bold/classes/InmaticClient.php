<?php
/**
 * Cliente para la API de Inmatic
 * Documentacion: API Inmatic v1.0.15
 *
 * Uso:
 *   $client = new InmaticClient($advisory_id);
 *   $result = $client->uploadDocument($filePath, $fileName, 'invoice');
 */

class InmaticClient
{
    private $baseUrl = 'https://api.inmatic.ai/api';
    private $token;
    private $advisoryId;
    private $companyId;

    /**
     * Constructor - obtiene configuracion de la BD
     *
     * @param int $advisoryId ID de la asesoria
     * @throws Exception Si no hay configuracion activa
     */
    public function __construct($advisoryId)
    {
        global $pdo;
        $this->advisoryId = $advisoryId;

        $stmt = $pdo->prepare("
            SELECT inmatic_token, inmatic_company_id
            FROM advisory_inmatic_config
            WHERE advisory_id = ? AND is_active = 1
        ");
        $stmt->execute([$advisoryId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            throw new Exception("Inmatic no está configurado para esta asesoría");
        }

        $this->token = $config['inmatic_token'];
        $this->companyId = $config['inmatic_company_id'];
    }

    /**
     * Realizar peticion HTTP a la API de Inmatic
     *
     * @param string $method GET, POST, PUT, DELETE
     * @param string $endpoint Endpoint de la API (sin base URL)
     * @param array|null $data Datos a enviar
     * @param bool $isMultipart Si es upload de archivo
     * @return array Respuesta decodificada
     * @throws Exception En caso de error
     */
    private function request($method, $endpoint, $data = null, $isMultipart = false)
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json',
        ];

        if (!$isMultipart && $data !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($isMultipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                } elseif ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'GET':
            default:
                // GET es el default
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        // Log para debugging
        $this->logRequest($method, $endpoint, $httpCode, $error);

        if ($errno) {
            throw new Exception("Error de conexión con Inmatic: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = isset($decoded['message']) ? $decoded['message'] : 'Error desconocido';
            if (isset($decoded['errors'])) {
                $errorMsg .= ': ' . json_encode($decoded['errors']);
            }
            throw new Exception("Error API Inmatic ($httpCode): " . $errorMsg);
        }

        return $decoded ?: [];
    }

    /**
     * Log de peticiones para debugging
     */
    private function logRequest($method, $endpoint, $httpCode, $error = '')
    {
        $logFile = ROOT_DIR . '/logs/inmatic-api.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = date('Y-m-d H:i:s') . " | $method $endpoint | HTTP $httpCode";
        if ($error) {
            $logEntry .= " | Error: $error";
        }
        $logEntry .= "\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    // =========================================================================
    // DOCUMENTOS
    // =========================================================================

    /**
     * Subir documento/factura a Inmatic
     *
     * @param string $filePath Ruta absoluta al archivo
     * @param string $fileName Nombre original del archivo
     * @param string $type Tipo: invoice, receipt, credit_note, debit_note, other
     * @param array $metadata Metadatos adicionales
     * @return array Respuesta con ID del documento
     */
    public function uploadDocument($filePath, $fileName, $type = 'invoice', $metadata = [])
    {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: " . $filePath);
        }

        $mimeType = mime_content_type($filePath);
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception("Tipo de archivo no permitido: " . $mimeType);
        }

        $data = [
            'file' => new CURLFile($filePath, $mimeType, $fileName),
            'type' => $type
        ];

        // Agregar company_id si está configurado
        if ($this->companyId) {
            $data['company_id'] = $this->companyId;
        }

        // Agregar metadata adicional
        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        return $this->request('POST', '/documents', $data, true);
    }

    /**
     * Obtener información de un documento
     *
     * @param string $documentId ID del documento en Inmatic
     * @return array Datos del documento incluyendo estado y OCR
     */
    public function getDocument($documentId)
    {
        return $this->request('GET', '/documents/' . urlencode($documentId));
    }

    /**
     * Listar documentos con filtros
     *
     * @param array $params Filtros: page, per_page, status, type, date_from, date_to
     * @return array Lista de documentos
     */
    public function listDocuments($params = [])
    {
        $query = http_build_query($params);
        $endpoint = '/documents' . ($query ? '?' . $query : '');
        return $this->request('GET', $endpoint);
    }

    /**
     * Cambiar estado de un documento
     *
     * @param string $documentId ID del documento
     * @param string $newState Nuevo estado: approved, rejected, exported
     * @return array Respuesta
     */
    public function changeDocumentState($documentId, $newState)
    {
        $validStates = ['approved', 'rejected', 'exported'];
        if (!in_array($newState, $validStates)) {
            throw new Exception("Estado no válido: " . $newState);
        }

        return $this->request('POST', '/documents/' . urlencode($documentId) . '/change-state', [
            'state' => $newState
        ]);
    }

    /**
     * Eliminar un documento
     *
     * @param string $documentId ID del documento
     * @return array Respuesta
     */
    public function deleteDocument($documentId)
    {
        return $this->request('DELETE', '/documents/' . urlencode($documentId));
    }

    // =========================================================================
    // EMPRESAS
    // =========================================================================

    /**
     * Listar empresas
     *
     * @return array Lista de empresas
     */
    public function listCompanies()
    {
        return $this->request('GET', '/companies');
    }

    /**
     * Obtener una empresa
     *
     * @param string $companyId ID de la empresa
     * @return array Datos de la empresa
     */
    public function getCompany($companyId)
    {
        return $this->request('GET', '/companies/' . urlencode($companyId));
    }

    /**
     * Crear empresa
     *
     * @param array $data Datos: name, cif, address, email, phone
     * @return array Empresa creada con ID
     */
    public function createCompany($data)
    {
        return $this->request('POST', '/companies', $data);
    }

    /**
     * Actualizar empresa
     *
     * @param string $companyId ID de la empresa
     * @param array $data Datos a actualizar
     * @return array Empresa actualizada
     */
    public function updateCompany($companyId, $data)
    {
        return $this->request('PUT', '/companies/' . urlencode($companyId), $data);
    }

    // =========================================================================
    // PROVEEDORES
    // =========================================================================

    /**
     * Listar proveedores de una empresa
     *
     * @param string $companyId ID de la empresa
     * @return array Lista de proveedores
     */
    public function listSuppliers($companyId)
    {
        return $this->request('GET', '/companies/' . urlencode($companyId) . '/suppliers');
    }

    /**
     * Crear proveedor
     *
     * @param string $companyId ID de la empresa
     * @param array $data Datos del proveedor
     * @return array Proveedor creado
     */
    public function createSupplier($companyId, $data)
    {
        return $this->request('POST', '/companies/' . urlencode($companyId) . '/suppliers', $data);
    }

    /**
     * Actualizar proveedor
     *
     * @param string $supplierId ID del proveedor
     * @param array $data Datos a actualizar
     * @return array Proveedor actualizado
     */
    public function updateSupplier($supplierId, $data)
    {
        return $this->request('PUT', '/suppliers/' . urlencode($supplierId), $data);
    }

    // =========================================================================
    // CLIENTES
    // =========================================================================

    /**
     * Listar clientes de una empresa
     *
     * @param string $companyId ID de la empresa
     * @return array Lista de clientes
     */
    public function listCustomers($companyId)
    {
        return $this->request('GET', '/companies/' . urlencode($companyId) . '/customers');
    }

    /**
     * Crear cliente
     *
     * @param string $companyId ID de la empresa
     * @param array $data Datos del cliente
     * @return array Cliente creado
     */
    public function createCustomer($companyId, $data)
    {
        return $this->request('POST', '/companies/' . urlencode($companyId) . '/customers', $data);
    }

    /**
     * Actualizar cliente
     *
     * @param string $customerId ID del cliente
     * @param array $data Datos a actualizar
     * @return array Cliente actualizado
     */
    public function updateCustomer($customerId, $data)
    {
        return $this->request('PUT', '/customers/' . urlencode($customerId), $data);
    }

    // =========================================================================
    // WEBHOOKS
    // =========================================================================

    /**
     * Listar suscripciones a webhooks
     *
     * @return array Lista de webhooks
     */
    public function listWebhooks()
    {
        return $this->request('GET', '/webhook-subscriptions');
    }

    /**
     * Crear suscripcion a webhook
     *
     * @param string $url URL que recibirá los eventos
     * @param array $events Eventos: document.processed, document.approved, etc.
     * @return array Webhook creado
     */
    public function createWebhook($url, $events = ['document.processed'])
    {
        return $this->request('POST', '/webhook-subscriptions', [
            'url' => $url,
            'events' => $events
        ]);
    }

    /**
     * Eliminar webhook
     *
     * @param string $webhookId ID del webhook
     * @return array Respuesta
     */
    public function deleteWebhook($webhookId)
    {
        return $this->request('DELETE', '/webhook-subscriptions/' . urlencode($webhookId));
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Verificar conexión y validez del token
     *
     * @return bool True si la conexión es válida
     */
    public function testConnection()
    {
        try {
            $this->request('GET', '/companies');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener ID de la asesoría
     *
     * @return int
     */
    public function getAdvisoryId()
    {
        return $this->advisoryId;
    }

    /**
     * Obtener ID de empresa en Inmatic
     *
     * @return string|null
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }
}
