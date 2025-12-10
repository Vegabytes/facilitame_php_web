<!-- DASHBOARD COMERCIAL - OPTIMIZADO -->
<?php $scripts = []; ?>

<div class="dashboard-comercial-home">
    
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
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-success">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-people fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Clientes</div>
                        <div class="kpi-value" id="kpi-clientes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-info">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-briefcase fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Asesorías</div>
                        <div class="kpi-value" id="kpi-asesorias"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-warning">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-key fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Tu código</div>
                        <div class="kpi-value kpi-code"><?php secho($user["code"]) ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!--end::KPIs-->

    <!--begin::Card con Tabs-->
    <div class="card dashboard-tabs-card">
        
        <!--begin::Tabs-->
        <ul class="nav nav-tabs nav-line-tabs px-4 pt-3" id="comercial-tabs" role="tablist">
            
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-solicitudes" type="button" role="tab" data-search-target="solicitudes">
                    <i class="ki-duotone ki-folder fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span>Solicitudes</span>
                    <span class="badge badge-tab badge-light-primary ms-2" id="badge-solicitudes">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-aplazadas" type="button" role="tab" data-search-target="aplazadas">
                    <i class="ki-duotone ki-time fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span>Aplazadas</span>
                    <span class="badge badge-tab badge-light-warning ms-2" id="badge-aplazadas">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clientes" type="button" role="tab" data-search-target="clientes">
                    <i class="ki-duotone ki-people fs-4">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                    <span>Clientes</span>
                    <span class="badge badge-tab badge-light-success ms-2" id="badge-clientes">0</span>
                </button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asesorias" type="button" role="tab" data-search-target="asesorias">
                    <i class="ki-duotone ki-briefcase fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <span>Asesorías</span>
                    <span class="badge badge-tab badge-light-info ms-2" id="badge-asesorias">0</span>
                </button>
            </li>
            
        </ul>
        <!--end::Tabs-->

        <!--begin::Tab Content-->
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-solicitudes" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-solicitudes-comercial.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-aplazadas" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-aplazados-comercial.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-clientes" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-clientes-comercial.php"; ?>
            </div>
            
            <div class="tab-pane fade" id="tab-asesorias" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-asesorias-comercial.php"; ?>
            </div>
            
        </div>
        <!--end::Tab Content-->
        
    </div>
    <!--end::Card con Tabs-->

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    const KPI_IDS = ['solicitudes', 'clientes', 'aplazadas', 'asesorias'];
    
    // Cargar KPIs (1 sola llamada API)
    async function loadKPIs() {
        try {
            const response = await fetch('/api/dashboard-kpis-sales');
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                KPI_IDS.forEach(id => {
                    const value = result.data[id] ?? 0;
                    animateValue(`kpi-${id}`, value);
                    const badge = document.getElementById(`badge-${id}`);
                    if (badge) badge.textContent = value;
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
    
    document.querySelectorAll('#comercial-tabs [data-bs-toggle="tab"]').forEach(tab => {
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
    
    window.filterComercialDashboard = function(query) {
        const fn = window[`filter${activeTarget.charAt(0).toUpperCase() + activeTarget.slice(1)}`];
        if (typeof fn === 'function') fn(query);
    };
});
</script>