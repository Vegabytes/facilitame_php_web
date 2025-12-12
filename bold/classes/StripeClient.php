<?php
/**
 * Cliente para Stripe API
 * Maneja suscripciones, pagos y clientes
 */
class StripeClient
{
    private $secretKey;
    private $publishableKey;
    private $webhookSecret;
    private $pdo;

    const API_BASE = 'https://api.stripe.com/v1';

    // Mapeo de planes a Price IDs de Stripe
    // IMPORTANTE: Estos IDs deben configurarse en el panel de Stripe
    private $planPrices = [
        'gratuito' => null, // Sin cobro
        'basic' => 'price_basic_annual',
        'estandar' => 'price_estandar_annual',
        'pro' => 'price_pro_annual',
        'premium' => 'price_premium_annual',
        'enterprise' => 'price_enterprise_annual'
    ];

    // Precios en céntimos (para referencia/validación)
    private $planAmounts = [
        'gratuito' => 0,
        'basic' => 30000,      // 300€
        'estandar' => 65000,   // 650€
        'pro' => 179900,       // 1.799€
        'premium' => 279900,   // 2.799€
        'enterprise' => 579900 // 5.799€
    ];

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;

        if (!defined('STRIPE_SECRET_KEY')) {
            throw new Exception('STRIPE_SECRET_KEY no configurado');
        }

        $this->secretKey = STRIPE_SECRET_KEY;
        $this->publishableKey = defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : null;
        $this->webhookSecret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : null;
    }

    /**
     * Obtiene la clave pública para el frontend
     */
    public function getPublishableKey()
    {
        return $this->publishableKey;
    }

    /**
     * Configura los Price IDs desde la configuración
     */
    public function setPlanPrices($prices)
    {
        $this->planPrices = array_merge($this->planPrices, $prices);
    }

    // =========================================
    // CUSTOMERS
    // =========================================

    /**
     * Crea un cliente en Stripe
     */
    public function createCustomer($email, $name, $metadata = [])
    {
        $data = [
            'email' => $email,
            'name' => $name,
            'metadata' => $metadata
        ];

        return $this->request('POST', '/customers', $data);
    }

    /**
     * Obtiene un cliente de Stripe
     */
    public function getCustomer($customerId)
    {
        return $this->request('GET', '/customers/' . $customerId);
    }

    /**
     * Actualiza un cliente en Stripe
     */
    public function updateCustomer($customerId, $data)
    {
        return $this->request('POST', '/customers/' . $customerId, $data);
    }

    /**
     * Obtiene o crea un cliente de Stripe para una asesoría
     */
    public function getOrCreateCustomer($advisoryId)
    {
        // Buscar si ya tiene stripe_customer_id
        $stmt = $this->pdo->prepare("
            SELECT a.stripe_customer_id, a.razon_social, a.email_empresa, u.email, u.name
            FROM advisories a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$advisoryId]);
        $advisory = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$advisory) {
            throw new Exception('Asesoría no encontrada');
        }

        // Si ya tiene customer_id, verificar que existe en Stripe
        if (!empty($advisory['stripe_customer_id'])) {
            try {
                $customer = $this->getCustomer($advisory['stripe_customer_id']);
                if ($customer && !isset($customer['deleted'])) {
                    return $customer;
                }
            } catch (Exception $e) {
                // Customer no existe, crear uno nuevo
            }
        }

        // Crear nuevo customer
        $email = $advisory['email_empresa'] ?: $advisory['email'];
        $name = $advisory['razon_social'] ?: $advisory['name'];

        $customer = $this->createCustomer($email, $name, [
            'advisory_id' => $advisoryId
        ]);

        // Guardar stripe_customer_id
        $stmt = $this->pdo->prepare("UPDATE advisories SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$customer['id'], $advisoryId]);

        return $customer;
    }

    // =========================================
    // SUBSCRIPTIONS
    // =========================================

    /**
     * Crea una sesión de Checkout para suscripción
     */
    public function createCheckoutSession($advisoryId, $plan, $successUrl, $cancelUrl)
    {
        if ($plan === 'gratuito') {
            throw new Exception('El plan gratuito no requiere pago');
        }

        $priceId = $this->planPrices[$plan] ?? null;
        if (!$priceId) {
            throw new Exception('Plan no válido o no configurado: ' . $plan);
        }

        $customer = $this->getOrCreateCustomer($advisoryId);

        $data = [
            'customer' => $customer['id'],
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'advisory_id' => $advisoryId,
                'plan' => $plan
            ],
            'subscription_data' => [
                'metadata' => [
                    'advisory_id' => $advisoryId,
                    'plan' => $plan
                ]
            ],
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true]
        ];

        return $this->request('POST', '/checkout/sessions', $data);
    }

    /**
     * Obtiene una sesión de Checkout
     */
    public function getCheckoutSession($sessionId)
    {
        return $this->request('GET', '/checkout/sessions/' . $sessionId);
    }

    /**
     * Crea una suscripción directamente (requiere payment method)
     */
    public function createSubscription($customerId, $priceId, $paymentMethodId = null)
    {
        $data = [
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent']
        ];

        if ($paymentMethodId) {
            $data['default_payment_method'] = $paymentMethodId;
        }

        return $this->request('POST', '/subscriptions', $data);
    }

    /**
     * Obtiene una suscripción
     */
    public function getSubscription($subscriptionId)
    {
        return $this->request('GET', '/subscriptions/' . $subscriptionId);
    }

    /**
     * Actualiza una suscripción (cambio de plan)
     */
    public function updateSubscription($subscriptionId, $newPriceId)
    {
        // Primero obtener la suscripción para el item_id
        $subscription = $this->getSubscription($subscriptionId);
        $itemId = $subscription['items']['data'][0]['id'];

        $data = [
            'items' => [[
                'id' => $itemId,
                'price' => $newPriceId
            ]],
            'proration_behavior' => 'create_prorations'
        ];

        return $this->request('POST', '/subscriptions/' . $subscriptionId, $data);
    }

    /**
     * Cancela una suscripción
     */
    public function cancelSubscription($subscriptionId, $atPeriodEnd = true)
    {
        if ($atPeriodEnd) {
            return $this->request('POST', '/subscriptions/' . $subscriptionId, [
                'cancel_at_period_end' => true
            ]);
        } else {
            return $this->request('DELETE', '/subscriptions/' . $subscriptionId);
        }
    }

    /**
     * Reactiva una suscripción cancelada (si aún no ha expirado)
     */
    public function reactivateSubscription($subscriptionId)
    {
        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'cancel_at_period_end' => false
        ]);
    }

    // =========================================
    // PORTAL DEL CLIENTE
    // =========================================

    /**
     * Crea una sesión del portal del cliente
     * Permite al usuario gestionar su suscripción, métodos de pago, etc.
     */
    public function createPortalSession($customerId, $returnUrl)
    {
        $data = [
            'customer' => $customerId,
            'return_url' => $returnUrl
        ];

        return $this->request('POST', '/billing_portal/sessions', $data);
    }

    // =========================================
    // INVOICES
    // =========================================

    /**
     * Lista las facturas de un cliente
     */
    public function listInvoices($customerId, $limit = 10)
    {
        return $this->request('GET', '/invoices', [
            'customer' => $customerId,
            'limit' => $limit
        ]);
    }

    /**
     * Obtiene una factura
     */
    public function getInvoice($invoiceId)
    {
        return $this->request('GET', '/invoices/' . $invoiceId);
    }

    // =========================================
    // PAYMENT METHODS
    // =========================================

    /**
     * Lista los métodos de pago de un cliente
     */
    public function listPaymentMethods($customerId, $type = 'card')
    {
        return $this->request('GET', '/payment_methods', [
            'customer' => $customerId,
            'type' => $type
        ]);
    }

    /**
     * Establece el método de pago por defecto
     */
    public function setDefaultPaymentMethod($customerId, $paymentMethodId)
    {
        return $this->updateCustomer($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId
            ]
        ]);
    }

    // =========================================
    // WEBHOOKS
    // =========================================

    /**
     * Verifica la firma de un webhook
     */
    public function verifyWebhookSignature($payload, $sigHeader)
    {
        if (!$this->webhookSecret) {
            throw new Exception('STRIPE_WEBHOOK_SECRET no configurado');
        }

        $elements = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            throw new Exception('Firma de webhook inválida');
        }

        // Verificar que no es muy antiguo (5 minutos)
        if (abs(time() - $timestamp) > 300) {
            throw new Exception('Timestamp del webhook muy antiguo');
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return json_decode($payload, true);
            }
        }

        throw new Exception('Firma de webhook no coincide');
    }

    // =========================================
    // HELPERS
    // =========================================

    /**
     * Obtiene el Price ID para un plan
     */
    public function getPriceIdForPlan($plan)
    {
        return $this->planPrices[$plan] ?? null;
    }

    /**
     * Obtiene el precio en céntimos para un plan
     */
    public function getPlanAmount($plan)
    {
        return $this->planAmounts[$plan] ?? 0;
    }

    /**
     * Realiza una petición a la API de Stripe
     */
    private function request($method, $endpoint, $data = null)
    {
        $url = self::API_BASE . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->flattenArray($data)));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && $data) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $result['error']['message'] ?? 'Error desconocido de Stripe';
            throw new Exception($errorMessage);
        }

        return $result;
    }

    /**
     * Aplana un array para http_build_query de Stripe
     */
    private function flattenArray($array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '[' . $key . ']' : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
