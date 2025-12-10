<?php $scripts = []; ?>

<!-- DASHBOARD ADMIN - ESTRUCTURA UNIFICADA CON PROVIDER -->
<div class="dashboard-admin-home">
    
    <!--begin::KPIs-->
    <div class="row g-3 mb-3">
        
        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-primary">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-folder fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Solicitudes</div>
                        <div class="kpi-value" id="kpi-solicitudes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Total activas</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-danger">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-information-2 fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Incidencias</div>
                        <div class="kpi-value" id="kpi-incidencias"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Requieren atención</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-info">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-search-list fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Revisiones</div>
                        <div class="kpi-value" id="kpi-revisiones"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Pendientes</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-warning">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-time fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Aplazadas</div>
                        <div class="kpi-value" id="kpi-aplazadas"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">En espera</span>
                </div>
            </div>
        </div>

    </div>
    <!--end::KPIs-->

    <!--begin::Card con Tabs-->
    <div class="card dashboard-tabs-card">
        
        <!--begin::Tabs-->
        <ul class="nav nav-tabs nav-line-tabs px-4 pt-3" id="admin-tabs" role="tablist">
            
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-solicitudes" type="button" role="tab" data-search-target="solicitudes">
                    <i class="ki-duotone ki-folder fs-4"><span class="path1"></span><span class="path2"></span></i>
                    <span>Solicitudes</span>
                    <span class="badge badge-tab badge-light-primary ms-2" id="badge-solicitudes">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-incidencias" type="button" role="tab" data-search-target="incidencias">
                    <i class="ki-duotone ki-information-2 fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span>Incidencias</span>
                    <span class="badge badge-tab badge-light-danger ms-2" id="badge-incidencias">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-revisiones" type="button" role="tab" data-search-target="revisiones">
                    <i class="ki-duotone ki-search-list fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span>Revisiones</span>
                    <span class="badge badge-tab badge-light-info ms-2" id="badge-revisiones">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-aplazadas" type="button" role="tab" data-search-target="aplazadas">
                    <i class="ki-duotone ki-time fs-4"><span class="path1"></span><span class="path2"></span></i>
                    <span>Aplazadas</span>
                    <span class="badge badge-tab badge-light-warning ms-2" id="badge-aplazadas">0</span>
                </button>
            </li>
            
        </ul>
        <!--end::Tabs-->
        
        <!--begin::Tab Content-->
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-solicitudes" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-solicitudes-admin.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-incidencias" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-incidencias-admin.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-revisiones" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-revisiones-admin.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-aplazadas" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-aplazados-admin.php"; ?>
            </div>
            
        </div>
        <!--end::Tab Content-->
        
    </div>
    <!--end::Card con Tabs-->

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    const KPI_IDS = ['solicitudes', 'incidencias', 'revisiones', 'aplazadas'];
    
    // Cargar KPIs (1 sola llamada)
    async function loadKPIs() {
        try {
            const response = await fetch('/api/dashboard-kpis-admin');
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
    let activeTarget = 'solicitudes';
    
    document.querySelectorAll('#admin-tabs [data-bs-toggle="tab"]').forEach(tab => {
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
    
    window.filterAdminDashboard = function(query) {
        const fn = window[`filter${activeTarget.charAt(0).toUpperCase() + activeTarget.slice(1)}`];
        if (typeof fn === 'function') fn(query);
    };
});
</script>