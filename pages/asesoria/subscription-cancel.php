<?php
/**
 * Página de cancelación de checkout de Stripe
 * (El usuario canceló el proceso de pago, no la suscripción)
 */
$currentPage = 'subscription';
?>

<style>
.cancel-container {
    max-width: 600px;
    margin: 60px auto;
    text-align: center;
}

.cancel-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #f5f8fa 0%, #e9ecef 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
}

.cancel-icon i {
    font-size: 48px;
    color: #a1a5b7;
}

.cancel-title {
    font-size: 28px;
    font-weight: 700;
    color: #181c32;
    margin-bottom: 16px;
}

.cancel-text {
    font-size: 16px;
    color: #5e6278;
    margin-bottom: 30px;
    line-height: 1.6;
}

.info-box {
    background: rgba(0, 158, 247, 0.1);
    border: 1px solid rgba(0, 158, 247, 0.2);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
}

.info-box i {
    color: #009ef7;
    margin-right: 10px;
}

.info-box p {
    color: #5e6278;
    margin: 0;
    font-size: 14px;
}
</style>

<div class="cancel-container">
    <div class="cancel-icon">
        <i class="ki-outline ki-arrow-left"></i>
    </div>

    <h1 class="cancel-title">Pago cancelado</h1>
    <p class="cancel-text">
        Has cancelado el proceso de pago.<br>
        No se ha realizado ningún cargo a tu tarjeta.
    </p>

    <div class="info-box">
        <p>
            <i class="ki-outline ki-information-2"></i>
            <strong>¿Tienes dudas?</strong> Si tienes alguna pregunta sobre nuestros planes o necesitas ayuda, no dudes en contactarnos.
        </p>
    </div>

    <div class="d-flex gap-3 justify-content-center">
        <a href="/subscription" class="btn btn-primary">
            <i class="ki-outline ki-arrow-left me-1"></i>
            Ver planes
        </a>
        <a href="/home" class="btn btn-light">
            <i class="ki-outline ki-home me-1"></i>
            Ir al inicio
        </a>
    </div>
</div>
