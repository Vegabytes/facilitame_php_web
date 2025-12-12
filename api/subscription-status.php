<?php
/**
 * API: Obtener estado de suscripción
 * GET /api/subscription-status
 *
 * Retorna el estado actual de la suscripción de la asesoría
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

// Obtener advisory_id y datos de suscripción
$stmt = $pdo->prepare("
    SELECT
        a.id as advisory_id,
        a.plan,
        a.stripe_customer_id,
        s.id as subscription_id,
        s.stripe_subscription_id,
        s.status as subscription_status,
        s.current_period_start,
        s.current_period_end,
        s.cancel_at_period_end,
        s.canceled_at,
        s.trial_start,
        s.trial_end
    FROM advisories a
    LEFT JOIN subscriptions s ON a.id = s.advisory_id
    WHERE a.user_id = ?
");
$stmt->execute([USER['id']]);
$data = $stmt->fetch();

if (!$data) {
    json_response('ko', 'Asesoría no encontrada', 404);
}

// Determinar estado efectivo
$effectiveStatus = 'free';
$isActive = false;
$canUpgrade = true;
$canCancel = false;
$daysRemaining = null;

if ($data['subscription_status'] === 'active' || $data['subscription_status'] === 'trialing') {
    $effectiveStatus = $data['subscription_status'];
    $isActive = true;
    $canCancel = true;

    if ($data['current_period_end']) {
        $endDate = new DateTime($data['current_period_end']);
        $now = new DateTime();
        $interval = $now->diff($endDate);
        $daysRemaining = $interval->days;
        if ($now > $endDate) {
            $daysRemaining = 0;
        }
    }
} elseif ($data['subscription_status'] === 'past_due') {
    $effectiveStatus = 'past_due';
    $isActive = true; // Aún tiene acceso pero con advertencia
    $canCancel = true;
} elseif ($data['subscription_status'] === 'canceled') {
    $effectiveStatus = 'canceled';
    // Verificar si aún tiene acceso (hasta fin de periodo)
    if ($data['current_period_end']) {
        $endDate = new DateTime($data['current_period_end']);
        $now = new DateTime();
        if ($now < $endDate) {
            $isActive = true;
            $interval = $now->diff($endDate);
            $daysRemaining = $interval->days;
        }
    }
}

// Información del plan
$planInfo = [
    'gratuito' => ['name' => 'Gratuito', 'price' => 0, 'clients' => 10],
    'basic' => ['name' => 'Basic', 'price' => 300, 'clients' => 50],
    'estandar' => ['name' => 'Estándar', 'price' => 650, 'clients' => 150],
    'pro' => ['name' => 'Pro', 'price' => 1799, 'clients' => 500],
    'premium' => ['name' => 'Premium', 'price' => 2799, 'clients' => 1500],
    'enterprise' => ['name' => 'Enterprise', 'price' => 5799, 'clients' => -1] // -1 = ilimitado
];

$currentPlan = $data['plan'] ?? 'gratuito';
$currentPlanInfo = $planInfo[$currentPlan] ?? $planInfo['gratuito'];

json_response('ok', 'Estado obtenido', 200, [
    'plan' => $currentPlan,
    'plan_name' => $currentPlanInfo['name'],
    'plan_price' => $currentPlanInfo['price'],
    'plan_clients' => $currentPlanInfo['clients'],
    'subscription_status' => $effectiveStatus,
    'is_active' => $isActive,
    'can_upgrade' => $canUpgrade,
    'can_cancel' => $canCancel,
    'cancel_at_period_end' => (bool) $data['cancel_at_period_end'],
    'days_remaining' => $daysRemaining,
    'current_period_end' => $data['current_period_end'],
    'trial_end' => $data['trial_end'],
    'has_payment_method' => !empty($data['stripe_customer_id'])
]);
