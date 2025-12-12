<?php
/**
 * API: Crear sesión de Checkout para suscripción
 * POST /api/subscription-checkout
 *
 * Parámetros:
 * - plan: El plan a contratar (basic, estandar, pro, premium, enterprise)
 *
 * Retorna la URL de Stripe Checkout
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

$plan = $_POST['plan'] ?? '';

$validPlans = ['basic', 'estandar', 'pro', 'premium', 'enterprise'];
if (!in_array($plan, $validPlans)) {
    json_response('ko', 'Plan no válido', 400);
}

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan as current_plan FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('ko', 'Asesoría no encontrada', 404);
}

// Verificar que no está intentando contratar el mismo plan
if ($advisory['current_plan'] === $plan) {
    json_response('ko', 'Ya tienes este plan activo', 400);
}

try {
    require_once ROOT_DIR . '/bold/classes/StripeClient.php';
    $stripe = new StripeClient();

    $successUrl = ROOT_URL . '/subscription/success';
    $cancelUrl = ROOT_URL . '/subscription/cancel';

    $session = $stripe->createCheckoutSession(
        $advisory['id'],
        $plan,
        $successUrl,
        $cancelUrl
    );

    json_response('ok', 'Sesión creada', 200, [
        'checkout_url' => $session['url'],
        'session_id' => $session['id']
    ]);

} catch (Exception $e) {
    json_response('ko', 'Error al crear sesión: ' . $e->getMessage(), 500);
}
