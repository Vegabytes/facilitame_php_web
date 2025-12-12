<?php
/**
 * API: Crear sesión del Portal de Cliente de Stripe
 * POST /api/subscription-portal
 *
 * Permite al usuario gestionar su suscripción, métodos de pago, facturas, etc.
 * Retorna la URL del portal
 */

if (!asesoria()) {
    json_response('ko', 'No autorizado', 403);
}

// Obtener advisory_id y stripe_customer_id
$stmt = $pdo->prepare("SELECT id, stripe_customer_id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    json_response('ko', 'Asesoría no encontrada', 404);
}

if (empty($advisory['stripe_customer_id'])) {
    json_response('ko', 'No tienes una suscripción activa', 400);
}

try {
    require_once ROOT_DIR . '/bold/classes/StripeClient.php';
    $stripe = new StripeClient();

    $returnUrl = ROOT_URL . '/subscription';

    $session = $stripe->createPortalSession(
        $advisory['stripe_customer_id'],
        $returnUrl
    );

    json_response('ok', 'Portal creado', 200, [
        'portal_url' => $session['url']
    ]);

} catch (Exception $e) {
    json_response('ko', 'Error al crear portal: ' . $e->getMessage(), 500);
}
