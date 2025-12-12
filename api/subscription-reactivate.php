<?php
/**
 * API: Reactivar suscripción cancelada
 * POST /api/subscription-reactivate
 *
 * Solo funciona si la suscripción está marcada para cancelar al final del periodo
 * pero aún no ha expirado
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

// Obtener suscripción
$stmt = $pdo->prepare("
    SELECT s.*, a.stripe_customer_id
    FROM subscriptions s
    JOIN advisories a ON s.advisory_id = a.id
    WHERE a.user_id = ?
");
$stmt->execute([USER['id']]);
$subscription = $stmt->fetch();

if (!$subscription) {
    json_response('ko', 'No tienes una suscripción', 400);
}

if (!$subscription['cancel_at_period_end']) {
    json_response('ko', 'La suscripción no está pendiente de cancelación', 400);
}

if (empty($subscription['stripe_subscription_id'])) {
    json_response('ko', 'Suscripción no válida', 400);
}

// Verificar que no ha expirado
if ($subscription['current_period_end']) {
    $endDate = new DateTime($subscription['current_period_end']);
    $now = new DateTime();
    if ($now > $endDate) {
        json_response('ko', 'La suscripción ya ha expirado. Debes crear una nueva suscripción.', 400);
    }
}

try {
    require_once ROOT_DIR . '/bold/classes/StripeClient.php';
    $stripe = new StripeClient();

    $result = $stripe->reactivateSubscription($subscription['stripe_subscription_id']);

    // Actualizar en base de datos
    $stmt = $pdo->prepare("
        UPDATE subscriptions
        SET cancel_at_period_end = 0, canceled_at = NULL
        WHERE id = ?
    ");
    $stmt->execute([$subscription['id']]);

    json_response('ok', 'Suscripción reactivada correctamente', 200, [
        'reactivated' => true
    ]);

} catch (Exception $e) {
    json_response('ko', 'Error al reactivar: ' . $e->getMessage(), 500);
}
