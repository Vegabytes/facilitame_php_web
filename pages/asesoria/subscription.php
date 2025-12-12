<?php
/**
 * Página de Gestión de Suscripción - Asesorías
 */
$currentPage = 'subscription';

// Obtener datos de la asesoría
$stmt = $pdo->prepare("
    SELECT a.*, s.status as subscription_status, s.current_period_end, s.cancel_at_period_end
    FROM advisories a
    LEFT JOIN subscriptions s ON a.id = s.advisory_id
    WHERE a.user_id = ?
");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    echo '<div class="alert alert-danger m-5">Asesoría no encontrada.</div>';
    return;
}

$currentPlan = $advisory['plan'] ?? 'gratuito';
$subscriptionStatus = $advisory['subscription_status'] ?? 'free';
$periodEnd = $advisory['current_period_end'];
$cancelAtPeriodEnd = $advisory['cancel_at_period_end'];

// Información de planes
$plans = [
    'gratuito' => [
        'name' => 'Gratuito',
        'price' => 0,
        'clients' => 10,
        'features' => [
            'Hasta 10 clientes',
            'Gestión básica de facturas',
            'Citas ilimitadas',
            'Soporte por email'
        ],
        'color' => 'secondary'
    ],
    'basic' => [
        'name' => 'Basic',
        'price' => 300,
        'clients' => 50,
        'features' => [
            'Hasta 50 clientes',
            'Gestión completa de facturas',
            'Chat con clientes',
            'Estadísticas básicas',
            'Soporte prioritario'
        ],
        'color' => 'info'
    ],
    'estandar' => [
        'name' => 'Estándar',
        'price' => 650,
        'clients' => 150,
        'features' => [
            'Hasta 150 clientes',
            'Todas las funciones de Basic',
            'Integración Google Calendar',
            'Exportación de datos',
            'Soporte telefónico'
        ],
        'color' => 'primary',
        'popular' => true
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 1799,
        'clients' => 500,
        'features' => [
            'Hasta 500 clientes',
            'Todas las funciones de Estándar',
            'Integración Inmatic',
            'API de integración',
            'Formación personalizada'
        ],
        'color' => 'warning'
    ],
    'premium' => [
        'name' => 'Premium',
        'price' => 2799,
        'clients' => 1500,
        'features' => [
            'Hasta 1500 clientes',
            'Todas las funciones de Pro',
            'Múltiples usuarios',
            'Personalización avanzada',
            'Account manager dedicado'
        ],
        'color' => 'danger'
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 5799,
        'clients' => -1,
        'features' => [
            'Clientes ilimitados',
            'Todas las funciones Premium',
            'SLA garantizado 99.9%',
            'Desarrollo a medida',
            'Soporte 24/7'
        ],
        'color' => 'dark'
    ]
];

$currentPlanInfo = $plans[$currentPlan] ?? $plans['gratuito'];
?>

<style>
.subscription-page {
    max-width: 1400px;
    margin: 0 auto;
}

.current-plan-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 30px;
    color: white;
    margin-bottom: 30px;
}

.current-plan-card .plan-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 15px;
}

.current-plan-card .plan-name {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.current-plan-card .plan-price {
    font-size: 18px;
    opacity: 0.9;
}

.current-plan-card .plan-price span {
    font-size: 42px;
    font-weight: 700;
}

.current-plan-card .plan-status {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(80, 205, 137, 0.2);
    color: #50cd89;
}

.status-badge.warning {
    background: rgba(255, 199, 0, 0.2);
    color: #ffc700;
}

.status-badge.danger {
    background: rgba(241, 65, 108, 0.2);
    color: #f1416c;
}

.plan-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 2px solid #e9ecef;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.plan-card.current {
    border-color: #9949FF;
    background: linear-gradient(180deg, rgba(153, 73, 255, 0.05) 0%, white 100%);
}

.plan-card.popular {
    border-color: #009ef7;
}

.plan-card .popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #009ef7;
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.plan-card .plan-header {
    text-align: center;
    margin-bottom: 20px;
}

.plan-card .plan-name {
    font-size: 20px;
    font-weight: 700;
    color: #181c32;
    margin-bottom: 8px;
}

.plan-card .plan-price {
    font-size: 14px;
    color: #a1a5b7;
}

.plan-card .plan-price .amount {
    font-size: 36px;
    font-weight: 700;
    color: #181c32;
}

.plan-card .plan-price .period {
    font-size: 13px;
}

.plan-card .plan-clients {
    text-align: center;
    padding: 12px;
    background: #f5f8fa;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #5e6278;
}

.plan-card .plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
    flex-grow: 1;
}

.plan-card .plan-features li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    color: #5e6278;
}

.plan-card .plan-features li i {
    color: #50cd89;
    font-size: 16px;
}

.plan-card .plan-action {
    margin-top: auto;
}

.plan-card .btn-select-plan {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
}

.quick-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.quick-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #5e6278;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: #f5f8fa;
    border-color: #d0d5dd;
}

.quick-action-btn i {
    font-size: 18px;
}

.invoices-section {
    margin-top: 30px;
}

.invoice-card {
    display: flex;
    align-items: center;
    padding: 16px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 10px;
}

.invoice-card .invoice-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    font-size: 20px;
}

.invoice-card .invoice-icon.paid {
    background: rgba(80, 205, 137, 0.1);
    color: #50cd89;
}

.invoice-card .invoice-icon.pending {
    background: rgba(255, 199, 0, 0.1);
    color: #ffc700;
}

.invoice-card .invoice-info {
    flex-grow: 1;
}

.invoice-card .invoice-amount {
    font-size: 18px;
    font-weight: 700;
    color: #181c32;
}

.invoice-card .invoice-date {
    font-size: 13px;
    color: #a1a5b7;
}

.invoice-card .invoice-actions a {
    color: #009ef7;
    font-size: 14px;
    text-decoration: none;
}

.invoice-card .invoice-actions a:hover {
    text-decoration: underline;
}

.cancel-warning {
    background: rgba(255, 199, 0, 0.1);
    border: 1px solid rgba(255, 199, 0, 0.3);
    border-radius: 10px;
    padding: 16px;
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.cancel-warning i {
    font-size: 24px;
    color: #ffc700;
}

.cancel-warning .warning-text {
    flex-grow: 1;
}

.cancel-warning .warning-title {
    font-weight: 600;
    color: #181c32;
    margin-bottom: 4px;
}

.cancel-warning .warning-desc {
    font-size: 13px;
    color: #5e6278;
}
</style>

<div id="facilita-app">
    <div class="subscription-page">

        <!-- Plan Actual -->
        <div class="current-plan-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="plan-badge">Tu plan actual</div>
                    <div class="plan-name"><?php echo $currentPlanInfo['name']; ?></div>
                    <div class="plan-price">
                        <?php if ($currentPlanInfo['price'] > 0): ?>
                            <span><?php echo number_format($currentPlanInfo['price'], 0, ',', '.'); ?>€</span> / año
                        <?php else: ?>
                            <span>Gratis</span>
                        <?php endif; ?>
                    </div>

                    <div class="plan-status">
                        <?php if ($currentPlan === 'gratuito'): ?>
                            <span class="status-badge active">
                                <i class="ki-outline ki-check-circle"></i>
                                Plan gratuito activo
                            </span>
                        <?php elseif ($subscriptionStatus === 'active'): ?>
                            <?php if ($cancelAtPeriodEnd): ?>
                                <span class="status-badge warning">
                                    <i class="ki-outline ki-information-2"></i>
                                    Cancelado - Acceso hasta <?php echo date('d/m/Y', strtotime($periodEnd)); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge active">
                                    <i class="ki-outline ki-check-circle"></i>
                                    Activo - Renovación <?php echo date('d/m/Y', strtotime($periodEnd)); ?>
                                </span>
                            <?php endif; ?>
                        <?php elseif ($subscriptionStatus === 'past_due'): ?>
                            <span class="status-badge danger">
                                <i class="ki-outline ki-information-2"></i>
                                Pago pendiente
                            </span>
                        <?php elseif ($subscriptionStatus === 'trialing'): ?>
                            <span class="status-badge active">
                                <i class="ki-outline ki-time"></i>
                                Periodo de prueba
                            </span>
                        <?php else: ?>
                            <span class="status-badge warning">
                                <i class="ki-outline ki-information-2"></i>
                                Sin suscripción activa
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <div class="quick-actions justify-content-md-end">
                        <?php if ($advisory['stripe_customer_id']): ?>
                        <button type="button" class="quick-action-btn" onclick="openPortal()">
                            <i class="ki-outline ki-setting-2"></i>
                            Gestionar
                        </button>
                        <?php endif; ?>
                        <?php if ($cancelAtPeriodEnd && $subscriptionStatus === 'active'): ?>
                        <button type="button" class="quick-action-btn" onclick="reactivateSubscription()">
                            <i class="ki-outline ki-arrows-circle"></i>
                            Reactivar
                        </button>
                        <?php elseif ($subscriptionStatus === 'active' && $currentPlan !== 'gratuito'): ?>
                        <button type="button" class="quick-action-btn" onclick="cancelSubscription()">
                            <i class="ki-outline ki-cross-circle"></i>
                            Cancelar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($cancelAtPeriodEnd): ?>
            <div class="cancel-warning">
                <i class="ki-outline ki-information-2"></i>
                <div class="warning-text">
                    <div class="warning-title">Tu suscripción está cancelada</div>
                    <div class="warning-desc">Tendrás acceso a todas las funciones hasta el <?php echo date('d/m/Y', strtotime($periodEnd)); ?>. Después pasarás al plan gratuito automáticamente.</div>
                </div>
                <button type="button" class="btn btn-sm btn-warning" onclick="reactivateSubscription()">
                    Reactivar ahora
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Planes Disponibles -->
        <h4 class="mb-4">Elige tu plan</h4>
        <div class="row g-4 mb-5">
            <?php foreach ($plans as $planKey => $plan): ?>
            <div class="col-lg-4 col-md-6">
                <div class="plan-card <?php echo $planKey === $currentPlan ? 'current' : ''; ?> <?php echo !empty($plan['popular']) ? 'popular' : ''; ?>">

                    <?php if (!empty($plan['popular'])): ?>
                    <div class="popular-badge">Más popular</div>
                    <?php endif; ?>

                    <div class="plan-header">
                        <div class="plan-name"><?php echo $plan['name']; ?></div>
                        <div class="plan-price">
                            <?php if ($plan['price'] > 0): ?>
                                <span class="amount"><?php echo number_format($plan['price'], 0, ',', '.'); ?>€</span>
                                <span class="period">/ año</span>
                            <?php else: ?>
                                <span class="amount">Gratis</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="plan-clients">
                        <i class="ki-outline ki-profile-user me-1"></i>
                        <?php echo $plan['clients'] == -1 ? 'Clientes ilimitados' : 'Hasta ' . $plan['clients'] . ' clientes'; ?>
                    </div>

                    <ul class="plan-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                        <li>
                            <i class="ki-outline ki-check"></i>
                            <span><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="plan-action">
                        <?php if ($planKey === $currentPlan): ?>
                            <button class="btn btn-light-primary btn-select-plan" disabled>
                                <i class="ki-outline ki-check me-1"></i> Plan actual
                            </button>
                        <?php elseif ($planKey === 'gratuito'): ?>
                            <button class="btn btn-light btn-select-plan" disabled>
                                Plan gratuito
                            </button>
                        <?php else: ?>
                            <button class="btn btn-<?php echo $plan['color']; ?> btn-select-plan" onclick="selectPlan('<?php echo $planKey; ?>')">
                                <?php if (array_search($planKey, array_keys($plans)) > array_search($currentPlan, array_keys($plans))): ?>
                                    Mejorar a <?php echo $plan['name']; ?>
                                <?php else: ?>
                                    Cambiar a <?php echo $plan['name']; ?>
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Facturas Recientes -->
        <div class="invoices-section">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="mb-0">Historial de pagos</h4>
                <?php if ($advisory['stripe_customer_id']): ?>
                <button type="button" class="btn btn-sm btn-light" onclick="openPortal()">
                    Ver todas las facturas
                </button>
                <?php endif; ?>
            </div>

            <div id="invoices-list">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span class="ms-2">Cargando facturas...</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    'use strict';

    // Cargar facturas al inicio
    loadInvoices();

    function loadInvoices() {
        fetch('/api/subscription-invoices?limit=5')
            .then(function(r) { return r.json(); })
            .then(function(result) {
                var container = document.getElementById('invoices-list');

                if (result.status === 'ok' && result.data && result.data.invoices && result.data.invoices.length > 0) {
                    var html = '';
                    result.data.invoices.forEach(function(inv) {
                        var iconClass = inv.status === 'paid' ? 'paid' : 'pending';
                        var iconName = inv.status === 'paid' ? 'ki-check-circle' : 'ki-time';

                        html += '<div class="invoice-card">' +
                            '<div class="invoice-icon ' + iconClass + '">' +
                                '<i class="ki-outline ' + iconName + '"></i>' +
                            '</div>' +
                            '<div class="invoice-info">' +
                                '<div class="invoice-amount">' + inv.amount.toFixed(2) + ' ' + inv.currency + '</div>' +
                                '<div class="invoice-date">' + formatDate(inv.created_at) + '</div>' +
                            '</div>' +
                            '<div class="invoice-actions">';

                        if (inv.invoice_pdf) {
                            html += '<a href="' + inv.invoice_pdf + '" target="_blank"><i class="ki-outline ki-document me-1"></i>Descargar PDF</a>';
                        } else if (inv.hosted_url) {
                            html += '<a href="' + inv.hosted_url + '" target="_blank"><i class="ki-outline ki-eye me-1"></i>Ver factura</a>';
                        }

                        html += '</div></div>';
                    });

                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-center py-4 text-muted">' +
                        '<i class="ki-outline ki-document fs-2x d-block mb-2"></i>' +
                        'No hay facturas disponibles' +
                    '</div>';
                }
            })
            .catch(function() {
                document.getElementById('invoices-list').innerHTML =
                    '<div class="text-center py-4 text-muted">Error al cargar facturas</div>';
            });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // Seleccionar plan
    window.selectPlan = function(plan) {
        Swal.fire({
            title: 'Cambiar de plan',
            text: '¿Deseas cambiar al plan ' + plan.charAt(0).toUpperCase() + plan.slice(1) + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                var formData = new FormData();
                formData.append('plan', plan);

                return fetch('/api/subscription-checkout', {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.status === 'ok' && result.data && result.data.checkout_url) {
                        window.location.href = result.data.checkout_url;
                    } else {
                        throw new Error(result.message || 'Error al crear sesión de pago');
                    }
                })
                .catch(function(err) {
                    Swal.showValidationMessage(err.message);
                });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        });
    };

    // Abrir portal de Stripe
    window.openPortal = function() {
        Swal.fire({
            title: 'Abriendo portal...',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        fetch('/api/subscription-portal', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data && result.data.portal_url) {
                    window.location.href = result.data.portal_url;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'No se pudo abrir el portal'
                    });
                }
            })
            .catch(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            });
    };

    // Cancelar suscripción
    window.cancelSubscription = function() {
        Swal.fire({
            title: '¿Cancelar suscripción?',
            html: '<p>Tu suscripción se cancelará al final del periodo actual.</p>' +
                  '<p class="text-muted fs-7">Seguirás teniendo acceso a todas las funciones hasta entonces.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener',
            confirmButtonColor: '#f1416c',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return fetch('/api/subscription-cancel', { method: 'POST' })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        if (result.status !== 'ok') {
                            throw new Error(result.message || 'Error al cancelar');
                        }
                        return result;
                    })
                    .catch(function(err) {
                        Swal.showValidationMessage(err.message);
                    });
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Suscripción cancelada',
                    text: 'Tu suscripción ha sido cancelada. Tendrás acceso hasta el final del periodo actual.',
                    confirmButtonText: 'Entendido'
                }).then(function() {
                    window.location.reload();
                });
            }
        });
    };

    // Reactivar suscripción
    window.reactivateSubscription = function() {
        Swal.fire({
            title: '¿Reactivar suscripción?',
            text: 'Tu suscripción se reactivará y continuará renovándose automáticamente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reactivar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return fetch('/api/subscription-reactivate', { method: 'POST' })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        if (result.status !== 'ok') {
                            throw new Error(result.message || 'Error al reactivar');
                        }
                        return result;
                    })
                    .catch(function(err) {
                        Swal.showValidationMessage(err.message);
                    });
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Suscripción reactivada',
                    text: 'Tu suscripción está activa de nuevo.',
                    confirmButtonText: 'Genial'
                }).then(function() {
                    window.location.reload();
                });
            }
        });
    };

})();
</script>
