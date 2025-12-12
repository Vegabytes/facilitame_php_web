<?php
/**
 * Webhook de Stripe
 * POST /api/webhook-stripe
 *
 * Recibe eventos de Stripe y actualiza el estado de las suscripciones
 *
 * Eventos manejados:
 * - checkout.session.completed: Pago inicial completado
 * - customer.subscription.created: Suscripción creada
 * - customer.subscription.updated: Suscripción actualizada
 * - customer.subscription.deleted: Suscripción cancelada/eliminada
 * - invoice.paid: Factura pagada
 * - invoice.payment_failed: Pago fallido
 * - customer.updated: Cliente actualizado
 */

// No requiere autenticación normal, usa firma de Stripe
// Obtener el payload raw
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sigHeader)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

require_once ROOT_DIR . '/bold/classes/StripeClient.php';

try {
    $stripe = new StripeClient();
    $event = $stripe->verifyWebhookSignature($payload, $sigHeader);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Verificar que no hemos procesado este evento antes
$stmt = $pdo->prepare("SELECT id FROM stripe_events WHERE stripe_event_id = ?");
$stmt->execute([$event['id']]);
if ($stmt->fetch()) {
    // Ya procesado, devolver 200 para que Stripe no reintente
    http_response_code(200);
    echo json_encode(['status' => 'already_processed']);
    exit;
}

// Guardar el evento
$stmt = $pdo->prepare("
    INSERT INTO stripe_events (stripe_event_id, type, data)
    VALUES (?, ?, ?)
");
$stmt->execute([
    $event['id'],
    $event['type'],
    json_encode($event['data'])
]);
$eventDbId = $pdo->lastInsertId();

try {
    $object = $event['data']['object'];

    switch ($event['type']) {
        case 'checkout.session.completed':
            handleCheckoutCompleted($pdo, $object);
            break;

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            handleSubscriptionUpdate($pdo, $object);
            break;

        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($pdo, $object);
            break;

        case 'invoice.paid':
            handleInvoicePaid($pdo, $object);
            break;

        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($pdo, $object);
            break;

        case 'customer.updated':
            // Podemos actualizar datos del cliente si es necesario
            break;
    }

    // Marcar como procesado
    $stmt = $pdo->prepare("UPDATE stripe_events SET processed = 1, processed_at = NOW() WHERE id = ?");
    $stmt->execute([$eventDbId]);

    http_response_code(200);
    echo json_encode(['status' => 'processed']);

} catch (Exception $e) {
    // Guardar error pero devolver 200 para no reintentar
    $stmt = $pdo->prepare("UPDATE stripe_events SET error_message = ? WHERE id = ?");
    $stmt->execute([$e->getMessage(), $eventDbId]);

    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// =========================================
// HANDLERS
// =========================================

/**
 * Checkout completado - crear/actualizar suscripción
 */
function handleCheckoutCompleted($pdo, $session)
{
    $advisoryId = $session['metadata']['advisory_id'] ?? null;
    $plan = $session['metadata']['plan'] ?? null;
    $customerId = $session['customer'];
    $subscriptionId = $session['subscription'];

    if (!$advisoryId || !$plan) {
        throw new Exception('Metadata incompleta en checkout session');
    }

    // Actualizar stripe_customer_id en advisories
    $stmt = $pdo->prepare("UPDATE advisories SET stripe_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $advisoryId]);

    // La suscripción se creará/actualizará con el evento subscription.created
}

/**
 * Suscripción creada o actualizada
 */
function handleSubscriptionUpdate($pdo, $subscription)
{
    $stripeSubId = $subscription['id'];
    $customerId = $subscription['customer'];
    $status = $subscription['status'];
    $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
    $plan = $subscription['metadata']['plan'] ?? null;

    // Buscar advisory por stripe_customer_id
    $stmt = $pdo->prepare("SELECT id FROM advisories WHERE stripe_customer_id = ?");
    $stmt->execute([$customerId]);
    $advisory = $stmt->fetch();

    if (!$advisory) {
        throw new Exception('Advisory no encontrada para customer: ' . $customerId);
    }

    $advisoryId = $advisory['id'];

    // Fechas
    $periodStart = $subscription['current_period_start']
        ? date('Y-m-d H:i:s', $subscription['current_period_start'])
        : null;
    $periodEnd = $subscription['current_period_end']
        ? date('Y-m-d H:i:s', $subscription['current_period_end'])
        : null;
    $trialStart = $subscription['trial_start']
        ? date('Y-m-d H:i:s', $subscription['trial_start'])
        : null;
    $trialEnd = $subscription['trial_end']
        ? date('Y-m-d H:i:s', $subscription['trial_end'])
        : null;
    $canceledAt = $subscription['canceled_at']
        ? date('Y-m-d H:i:s', $subscription['canceled_at'])
        : null;

    // Verificar si existe
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE advisory_id = ?");
    $stmt->execute([$advisoryId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Actualizar
        $stmt = $pdo->prepare("
            UPDATE subscriptions SET
                stripe_subscription_id = ?,
                stripe_price_id = ?,
                plan = COALESCE(?, plan),
                status = ?,
                current_period_start = ?,
                current_period_end = ?,
                cancel_at_period_end = ?,
                canceled_at = ?,
                trial_start = ?,
                trial_end = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $stripeSubId,
            $priceId,
            $plan,
            $status,
            $periodStart,
            $periodEnd,
            $subscription['cancel_at_period_end'] ? 1 : 0,
            $canceledAt,
            $trialStart,
            $trialEnd,
            $existing['id']
        ]);
    } else {
        // Crear
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (
                advisory_id, stripe_customer_id, stripe_subscription_id, stripe_price_id,
                plan, status, current_period_start, current_period_end,
                cancel_at_period_end, canceled_at, trial_start, trial_end
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $advisoryId,
            $customerId,
            $stripeSubId,
            $priceId,
            $plan ?? 'basic',
            $status,
            $periodStart,
            $periodEnd,
            $subscription['cancel_at_period_end'] ? 1 : 0,
            $canceledAt,
            $trialStart,
            $trialEnd
        ]);
    }

    // Actualizar plan en advisories si está activo
    if ($status === 'active' || $status === 'trialing') {
        if ($plan) {
            $stmt = $pdo->prepare("UPDATE advisories SET plan = ? WHERE id = ?");
            $stmt->execute([$plan, $advisoryId]);
        }
    }
}

/**
 * Suscripción eliminada/cancelada
 */
function handleSubscriptionDeleted($pdo, $subscription)
{
    $stripeSubId = $subscription['id'];
    $customerId = $subscription['customer'];

    // Actualizar estado
    $stmt = $pdo->prepare("
        UPDATE subscriptions
        SET status = 'canceled', canceled_at = NOW()
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$stripeSubId]);

    // Degradar a plan gratuito
    $stmt = $pdo->prepare("
        UPDATE advisories SET plan = 'gratuito'
        WHERE stripe_customer_id = ?
    ");
    $stmt->execute([$customerId]);
}

/**
 * Factura pagada
 */
function handleInvoicePaid($pdo, $invoice)
{
    $invoiceId = $invoice['id'];
    $subscriptionId = $invoice['subscription'];
    $customerId = $invoice['customer'];

    // Buscar subscription local
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE stripe_subscription_id = ?");
    $stmt->execute([$subscriptionId]);
    $sub = $stmt->fetch();

    if (!$sub) {
        // Intentar por customer_id
        $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE stripe_customer_id = ?");
        $stmt->execute([$customerId]);
        $sub = $stmt->fetch();
    }

    if (!$sub) {
        return; // No tenemos la suscripción aún
    }

    // Verificar si ya existe la factura
    $stmt = $pdo->prepare("SELECT id FROM subscription_invoices WHERE stripe_invoice_id = ?");
    $stmt->execute([$invoiceId]);
    if ($stmt->fetch()) {
        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE subscription_invoices
            SET status = 'paid', paid_at = NOW()
            WHERE stripe_invoice_id = ?
        ");
        $stmt->execute([$invoiceId]);
        return;
    }

    // Crear registro de factura
    $stmt = $pdo->prepare("
        INSERT INTO subscription_invoices (
            subscription_id, stripe_invoice_id, stripe_payment_intent_id,
            amount, currency, status, invoice_pdf, hosted_invoice_url,
            paid_at, period_start, period_end
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $sub['id'],
        $invoiceId,
        $invoice['payment_intent'],
        $invoice['amount_paid'] / 100,
        $invoice['currency'],
        'paid',
        $invoice['invoice_pdf'] ?? null,
        $invoice['hosted_invoice_url'] ?? null,
        date('Y-m-d H:i:s'),
        $invoice['period_start'] ? date('Y-m-d H:i:s', $invoice['period_start']) : null,
        $invoice['period_end'] ? date('Y-m-d H:i:s', $invoice['period_end']) : null
    ]);
}

/**
 * Pago de factura fallido
 */
function handleInvoicePaymentFailed($pdo, $invoice)
{
    $subscriptionId = $invoice['subscription'];

    if (!$subscriptionId) {
        return;
    }

    // Actualizar estado de suscripción a past_due
    $stmt = $pdo->prepare("
        UPDATE subscriptions
        SET status = 'past_due'
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$subscriptionId]);

    // TODO: Enviar email de notificación al usuario
}
