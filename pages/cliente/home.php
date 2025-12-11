<!-- DASHBOARD CLIENTE - OPTIMIZADO -->
<?php
$scripts = [

];

// Obtener próximas citas del cliente
$upcoming_appointments = [];
$customer_advisory_id = get_customer_advisory_id(USER['id']);

if ($customer_advisory_id) {
    $stmt = $pdo->prepare("
        SELECT aa.*, a.razon_social as advisory_name
        FROM advisory_appointments aa
        INNER JOIN advisories a ON aa.advisory_id = a.id
        WHERE aa.customer_id = ? AND aa.status IN ('solicitado', 'agendado')
        ORDER BY CASE WHEN aa.scheduled_date IS NOT NULL THEN aa.scheduled_date ELSE aa.created_at END ASC
        LIMIT 2
    ");
    $stmt->execute([USER['id']]);
    $upcoming_appointments = $stmt->fetchAll();
}
?>

<div class="dashboard-cliente-home">

    <!--begin::Perfil Usuario-->
    <div class="card profile-card-compact">
        <div class="card-body">
            <div class="profile-content">
                <div class="profile-avatar">
                    <img src="<?php secho(MEDIA_DIR . "/" . USER["profile_picture"]) ?>" alt="avatar" class="profile-avatar-img" />
                    <div class="profile-status"></div>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name">
                        <?php secho(USER["name"] . " " . USER["lastname"]) ?>
                        <?php if ($sales_rep_code != ""): ?>
                            <span class="badge-sales-code">
                                <i class="ki-duotone ki-badge"><span class="path1"></span><span class="path2"></span></i>
                                <?php echo $sales_rep_code ?>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <div class="profile-role"><?php secho(display_role()) ?></div>
                </div>
                <div class="profile-actions">
                    <button data-bs-toggle="modal" data-bs-target="#modal-invita-amigo" class="btn btn-success-facilitame btn-sm">
                        <i class="ki-duotone ki-users"><span class="path1"></span><span class="path2"></span></i>
                        ¡Invita a un amigo!
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Perfil-->

    <!--begin::KPIs-->
    <div class="row g-3 mb-3">
        
        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-primary">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-notification-bing fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Notificaciones</div>
                        <div class="kpi-value" id="kpi-notificaciones"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Total</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-danger">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-calendar fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Vencimientos</div>
                        <div class="kpi-value" id="kpi-vencimientos"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Próximos 90 días</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-success">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-gift fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Ofertas</div>
                        <div class="kpi-value" id="kpi-ofertas"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Disponibles</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-info">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-folder fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Mis Solicitudes</div>
                        <div class="kpi-value" id="kpi-solicitudes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Activas</span>
                </div>
            </div>
        </div>

    </div>
    <!--end::KPIs-->

    <?php if ($customer_advisory_id && !empty($upcoming_appointments)): ?>
    <!--begin::Próximas Citas Alert-->
    <div class="appointments-alert">
        <i class="ki-duotone ki-calendar-tick appointments-alert-icon"><span class="path1"></span><span class="path2"></span></i>
        <div class="appointments-alert-content">
            <h4 class="appointments-alert-title">Próximas Citas</h4>
            <?php foreach ($upcoming_appointments as $apt): ?>
            <div class="appointment-item">
                <?php if ($apt['status'] === 'agendado' && $apt['scheduled_date']): ?>
                <span class="appointment-badge appointment-badge-scheduled">
                    <?php echo !empty($apt['scheduled_date']) ? date('d M H:i', strtotime($apt['scheduled_date'])) . 'h' : '-'; ?>
                </span>
                <?php else: ?>
                <span class="appointment-badge appointment-badge-pending">Pendiente</span>
                <?php endif; ?>
                <span>
                    <?php 
                    $types = ['llamada' => 'Llamada', 'reunion_presencial' => 'Presencial', 'reunion_virtual' => 'Virtual'];
                    echo $types[$apt['type']] ?? $apt['type'];
                    ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="/appointments" class="btn btn-sm btn-light-warning">Ver todas</a>
    </div>
    <!--end::Próximas Citas-->
    <?php endif; ?>

    <!--begin::Card con Tabs-->
    <div class="card dashboard-tabs-card">
        
        <!--begin::Tabs-->
        <ul class="nav nav-tabs nav-line-tabs px-4 pt-3" id="cliente-tabs" role="tablist">
            
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-notificaciones" type="button" role="tab" data-search-target="notificaciones">
                    <i class="ki-duotone ki-notification-bing fs-4">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <span class="tab-dot tab-dot-notificaciones"></span>
                    <span>Notificaciones</span>
                    <span class="badge badge-tab badge-light-primary ms-2" id="badge-notificaciones">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vencimientos" type="button" role="tab" data-search-target="vencimientos">
                    <i class="ki-duotone ki-calendar fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span class="tab-dot tab-dot-vencimientos"></span>
                    <span>Vencimientos</span>
                    <span class="badge badge-tab badge-light-danger ms-2" id="badge-vencimientos">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ofertas" type="button" role="tab" data-search-target="ofertas">
                    <i class="ki-duotone ki-gift fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span class="tab-dot tab-dot-ofertas"></span>
                    <span>Ofertas</span>
                    <span class="badge badge-tab badge-light-success ms-2" id="badge-ofertas">0</span>
                </button>
            </li>
            
        </ul>
        <!--end::Tabs-->
        
        <!--begin::Tab Content-->
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-notificaciones" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-notificaciones-cliente.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-vencimientos" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-vencimientos-cliente.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-ofertas" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-ofertas-cliente.php"; ?>
            </div>
            
        </div>
        <!--end::Tab Content-->
        
    </div>
    <!--end::Card con Tabs-->

</div>

<!--begin::Modal invita amigo-->
<div class="modal fade" tabindex="-1" id="modal-invita-amigo">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="ki-duotone ki-user-tick fs-2 text-success me-2"><span class="path1"></span><span class="path2"></span></i>
                    Invita a un amigo
                </h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <form action="api/user-friend-invite" data-reload="0" class="bold-submit">
                <div class="modal-body">
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-4">
                        <i class="ki-duotone ki-gift fs-2tx text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                        <div class="fw-semibold fs-6 text-gray-700">
                            Comparte Facilítame con tus amigos y ambos recibiréis recompensas especiales
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="email" name="email_friend" class="form-control" id="email_friend" placeholder="amigo@email.com" required>
                        <label for="email_friend">Email de tu amigo</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-facilitame" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success-facilitame">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-send fs-5"><span class="path1"></span><span class="path2"></span></i>
                            Enviar invitación
                        </span>
                        <span class="indicator-progress">
                            Enviando...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    const KPI_IDS = ['notificaciones', 'vencimientos', 'ofertas', 'solicitudes'];
    
    // Cargar KPIs (1 sola llamada API)
    async function loadKPIs() {
        try {
            const response = await fetch('/api/dashboard-kpis-client');
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                KPI_IDS.forEach(id => {
                    animateValue(`kpi-${id}`, result.data[id]);
                    const badge = document.getElementById(`badge-${id}`);
                    if (badge) badge.textContent = result.data[id];
                });
            } else {
                showError();
            }
        } catch (e) {
            console.error('Error KPIs:', e);
            showError();
        }
    }

    function showError() {
        KPI_IDS.forEach(id => {
            const kpi = document.getElementById(`kpi-${id}`);
            const badge = document.getElementById(`badge-${id}`);
            if (kpi) kpi.textContent = '—';
            if (badge) badge.textContent = '—';
        });
    }
    
    function animateValue(id, target) {
        const el = document.getElementById(id);
        if (!el) return;
        
        const duration = 600;
        const start = Date.now();
        
        (function update() {
            const progress = Math.min((Date.now() - start) / duration, 1);
            el.textContent = Math.floor(target * (1 - Math.pow(1 - progress, 3)));
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
        })();
    }

    loadKPIs();
    
    // Lazy loading de tabs
    let activeTarget = 'notificaciones';
    
    document.querySelectorAll('#cliente-tabs [data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            activeTarget = e.target.dataset.searchTarget;
            
            const initFn = window[`init${activeTarget.charAt(0).toUpperCase() + activeTarget.slice(1)}Tab`];
            if (typeof initFn === 'function') initFn();
            
            if (window.FacilitameSearch) {
                window.FacilitameSearch.setTarget(activeTarget);
            }
            
            const search = document.getElementById('header-search-input');
            if (search) {
                search.placeholder = `Buscar ${activeTarget}...`;
                search.value = '';
            }
        });
    });
    
    window.filterClientDashboard = function(query) {
        const fn = window[`filter${activeTarget.charAt(0).toUpperCase() + activeTarget.slice(1)}`];
        if (typeof fn === 'function') fn(query);
    };
});
</script>