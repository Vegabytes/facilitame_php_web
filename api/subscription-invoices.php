<?php
/**
 * API: Listar facturas de suscripción
 * GET /api/subscription-invoices
 *
 * Parámetros opcionales:
 * - limit: número de facturas a obtener (default 10, max 100)
 *
 * Retorna lista de facturas
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

$limit = min(100, max(1, intval($_GET['limit'] ?? 10)));

// Obtener advisory y stripe_customer_id
$stmt = $pdo->prepare("SELECT id, stripe_customer_id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('ko', 'Asesoría no encontrada', 404);
}

// Primero intentar obtener de la base de datos local
$stmt = $pdo->prepare("
    SELECT
        si.stripe_invoice_id,
        si.amount,
        si.currency,
        si.status,
        si.invoice_pdf,
        si.hosted_invoice_url,
        si.paid_at,
        si.period_start,
        si.period_end,
        si.created_at
    FROM subscription_invoices si
    JOIN subscriptions s ON si.subscription_id = s.id
    WHERE s.advisory_id = ?
    ORDER BY si.created_at DESC
    LIMIT ?
");
$stmt->execute([$advisory['id'], $limit]);
$localInvoices = $stmt->fetchAll();

// Si tiene stripe_customer_id, obtener también de Stripe (más actualizado)
$invoices = [];

if (!empty($advisory['stripe_customer_id'])) {
    try {
        require_once ROOT_DIR . '/bold/classes/StripeClient.php';
        $stripe = new StripeClient();

        $stripeInvoices = $stripe->listInvoices($advisory['stripe_customer_id'], $limit);

        if (!empty($stripeInvoices['data'])) {
            foreach ($stripeInvoices['data'] as $inv) {
                $invoices[] = [
                    'id' => $inv['id'],
                    'number' => $inv['number'] ?? null,
                    'amount' => $inv['amount_paid'] / 100, // Convertir de céntimos
                    'currency' => strtoupper($inv['currency']),
                    'status' => $inv['status'],
                    'invoice_pdf' => $inv['invoice_pdf'] ?? null,
                    'hosted_url' => $inv['hosted_invoice_url'] ?? null,
                    'paid_at' => $inv['status_transitions']['paid_at']
                        ? date('Y-m-d H:i:s', $inv['status_transitions']['paid_at'])
                        : null,
                    'period_start' => $inv['period_start']
                        ? date('Y-m-d H:i:s', $inv['period_start'])
                        : null,
                    'period_end' => $inv['period_end']
                        ? date('Y-m-d H:i:s', $inv['period_end'])
                        : null,
                    'created_at' => date('Y-m-d H:i:s', $inv['created'])
                ];
            }
        }
    } catch (Exception $e) {
        // Si falla Stripe, usar datos locales
        foreach ($localInvoices as $inv) {
            $invoices[] = [
                'id' => $inv['stripe_invoice_id'],
                'number' => null,
                'amount' => floatval($inv['amount']),
                'currency' => strtoupper($inv['currency']),
                'status' => $inv['status'],
                'invoice_pdf' => $inv['invoice_pdf'],
                'hosted_url' => $inv['hosted_invoice_url'],
                'paid_at' => $inv['paid_at'],
                'period_start' => $inv['period_start'],
                'period_end' => $inv['period_end'],
                'created_at' => $inv['created_at']
            ];
        }
    }
} else {
    // Sin Stripe, usar datos locales
    foreach ($localInvoices as $inv) {
        $invoices[] = [
            'id' => $inv['stripe_invoice_id'],
            'number' => null,
            'amount' => floatval($inv['amount']),
            'currency' => strtoupper($inv['currency']),
            'status' => $inv['status'],
            'invoice_pdf' => $inv['invoice_pdf'],
            'hosted_url' => $inv['hosted_invoice_url'],
            'paid_at' => $inv['paid_at'],
            'period_start' => $inv['period_start'],
            'period_end' => $inv['period_end'],
            'created_at' => $inv['created_at']
        ];
    }
}

json_response('ok', 'Facturas obtenidas', 200, [
    'invoices' => $invoices,
    'count' => count($invoices)
]);
