<?php
/**
 * Página de éxito después del checkout de Stripe
 */
$currentPage = 'subscription';

$sessionId = $_GET['session_id'] ?? '';
?>

<style>
.success-container {
    max-width: 600px;
    margin: 60px auto;
    text-align: center;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #50cd89 0%, #47be7d 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    animation: scaleIn 0.5s ease-out;
}

.success-icon i {
    font-size: 48px;
    color: white;
}

@keyframes scaleIn {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.success-title {
    font-size: 28px;
    font-weight: 700;
    color: #181c32;
    margin-bottom: 16px;
}

.success-text {
    font-size: 16px;
    color: #5e6278;
    margin-bottom: 30px;
    line-height: 1.6;
}

.success-card {
    background: #f5f8fa;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 30px;
}

.success-card .label {
    font-size: 13px;
    color: #a1a5b7;
    margin-bottom: 4px;
}

.success-card .value {
    font-size: 18px;
    font-weight: 600;
    color: #181c32;
}
</style>

<div class="success-container">
    <div class="success-icon">
        <i class="ki-outline ki-check"></i>
    </div>

    <h1 class="success-title">¡Pago completado!</h1>
    <p class="success-text">
        Tu suscripción ha sido activada correctamente.<br>
        Ya puedes disfrutar de todas las funciones de tu nuevo plan.
    </p>

    <div class="success-card" id="subscription-info" style="display: none;">
        <div class="row">
            <div class="col-6">
                <div class="label">Plan</div>
                <div class="value" id="plan-name">-</div>
            </div>
            <div class="col-6">
                <div class="label">Próxima renovación</div>
                <div class="value" id="next-renewal">-</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 justify-content-center">
        <a href="/subscription" class="btn btn-light-primary">
            <i class="ki-outline ki-setting-2 me-1"></i>
            Ver mi suscripción
        </a>
        <a href="/home" class="btn btn-primary">
            <i class="ki-outline ki-home me-1"></i>
            Ir al inicio
        </a>
    </div>
</div>

<?php if ($sessionId): ?>
<script>
// Obtener info de la sesión de checkout
fetch('/api/subscription-status')
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok' && result.data) {
            document.getElementById('plan-name').textContent = result.data.plan_name || result.data.plan;
            if (result.data.current_period_end) {
                var date = new Date(result.data.current_period_end);
                document.getElementById('next-renewal').textContent = date.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
            }
            document.getElementById('subscription-info').style.display = 'block';
        }
    });
</script>
<?php endif; ?>
