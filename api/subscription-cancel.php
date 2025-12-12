<?php
/**
 * API: Cancelar suscripción
 * POST /api/subscription-cancel
 *
 * Parámetros opcionales:
 * - immediate: boolean (default false) - Si es true, cancela inmediatamente. Si no, al final del periodo.
 *
 * Retorna el estado actualizado
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

$immediate = filter_var($_POST['immediate'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Obtener suscripción activa
$stmt = $pdo->prepare("
    SELECT s.*, a.stripe_customer_id
    FROM subscriptions s
    JOIN advisories a ON s.advisory_id = a.id
    WHERE a.user_id = ? AND s.status IN ('active', 'trialing', 'past_due')
");
$stmt->execute([USER['id']]);
$subscription = $stmt->fetch();

if (!$subscription) {
    json_response('ko', 'No tienes una suscripción activa', 400);
}

if (empty($subscription['stripe_subscription_id'])) {
    json_response('ko', 'Suscripción no válida', 400);
}

try {
    require_once ROOT_DIR . '/bold/classes/StripeClient.php';
    $stripe = new StripeClient();

    $result = $stripe->cancelSubscription(
        $subscription['stripe_subscription_id'],
        !$immediate // at_period_end = true si no es inmediato
    );

    // Actualizar en base de datos
    if ($immediate) {
        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET status = 'canceled', canceled_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subscription['id']]);

        // También actualizar el plan de la asesoría a gratuito
        $stmt = $pdo->prepare("UPDATE advisories SET plan = 'gratuito' WHERE id = ?");
        $stmt->execute([$subscription['advisory_id']]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET cancel_at_period_end = 1
            WHERE id = ?
        ");
        $stmt->execute([$subscription['id']]);
    }

    $message = $immediate
        ? 'Suscripción cancelada inmediatamente'
        : 'Suscripción cancelada. Tendrás acceso hasta el final del periodo actual.';

    json_response('ok', $message, 200, [
        'canceled' => true,
        'immediate' => $immediate,
        'access_until' => $immediate ? null : $subscription['current_period_end']
    ]);

} catch (Exception $e) {
    json_response('ko', 'Error al cancelar: ' . $e->getMessage(), 500);
}
