<?php
$scripts = [];

// Obtener datos de la asesoría
$advisory_id = 0;
$advisory_code = '';

$stmt = $pdo->prepare("SELECT id, codigo_identificacion FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_data = $stmt->fetch();

if ($advisory_data) {
    $advisory_id = $advisory_data['id'];
    $advisory_code = $advisory_data['codigo_identificacion'];
}
?>

<div class="dashboard-asesoria-home">

    <!--begin::Code Card-->
    <div class="card code-card-compact mb-3">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="code-icon">
                        <i class="ki-duotone ki-share fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-gray-800">Tu Código de Asesoría</div>
                        <div class="text-gray-500 fs-7">Comparte este código con tus clientes</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" class="form-control form-control-solid code-input" value="<?php echo e($advisory_code); ?>" id="advisory-code-input" readonly>
                    <button class="btn btn-sm btn-primary-facilitame" type="button" id="btn-copy-code" title="Copiar código">
                        <i class="ki-duotone ki-copy fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button class="btn btn-sm btn-outline-facilitame" type="button" id="btn-copy-link" title="Copiar link de registro">
                        <i class="ki-duotone ki-exit-right-corner fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Code Card-->

    <!--begin::KPIs-->
    <div class="row g-3 mb-3">
        
        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-primary">
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
            <div class="kpi-card kpi-card-warning">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-calendar fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Citas pendientes</div>
                        <div class="kpi-value" id="kpi-citas"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-danger">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-document fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Facturas por procesar</div>
                        <div class="kpi-value" id="kpi-facturas"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-info">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-message-text fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Mensajes sin leer</div>
                        <div class="kpi-value" id="kpi-mensajes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!--end::KPIs-->

    <!--begin::Card con Tabs-->
    <div class="card dashboard-tabs-card">
        
        <ul class="nav nav-tabs nav-line-tabs px-4 pt-3" id="asesoria-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-clientes" type="button" role="tab" data-tab-id="clientes">
                    <i class="ki-duotone ki-people fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    <span>Clientes</span>
                    <span class="badge badge-tab badge-light-primary ms-2" id="badge-clientes">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-citas" type="button" role="tab" data-tab-id="citas">
                    <i class="ki-duotone ki-calendar fs-4"><span class="path1"></span><span class="path2"></span></i>
                    <span>Citas</span>
                    <span class="badge badge-tab badge-light-warning ms-2" id="badge-citas">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-facturas" type="button" role="tab" data-tab-id="facturas">
                    <i class="ki-duotone ki-document fs-4"><span class="path1"></span><span class="path2"></span></i>
                    <span>Facturas</span>
                    <span class="badge badge-tab badge-light-danger ms-2" id="badge-facturas">0</span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-clientes" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-clientes-asesoria.php"; ?>
            </div>
            <div class="tab-pane fade" id="tab-citas" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-citas-asesoria.php"; ?>
            </div>
            <div class="tab-pane fade" id="tab-facturas" role="tabpanel">
                <?php require COMPONENTS_DIR . "/home-datatable-facturas-asesoria.php"; ?>
            </div>
        </div>
        
    </div>
    <!--end::Card con Tabs-->

</div>

<input type="hidden" id="advisory-link" value="<?php echo ROOT_URL; ?>/sign-up?advisory=<?php echo e($advisory_code); ?>">

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    const KPI_MAP = {
        clientes: 'kpi-clientes',
        citas: 'kpi-citas',
        facturas: 'kpi-facturas',
        mensajes: 'kpi-mensajes'
    };
    
    // Cargar KPIs
    async function loadKPIs() {
        try {
            const response = await fetch('/api/dashboard-kpis-advisory');
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                Object.keys(KPI_MAP).forEach(key => {
                    const value = result.data[key] || 0;
                    animateValue(KPI_MAP[key], value);
                    
                    const badge = document.getElementById(`badge-${key}`);
                    if (badge) badge.textContent = value;
                });
            } else {
                showKPIError();
            }
        } catch (e) {
            console.error('Error KPIs:', e);
            showKPIError();
        }
    }
    
    function showKPIError() {
        Object.values(KPI_MAP).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '—';
        });
    }
    
    function animateValue(id, target) {
        const el = document.getElementById(id);
        if (!el) return;
        
        const duration = 600;
        const start = performance.now();
        
        function update(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(target * eased);
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                el.textContent = target;
            }
        }
        
        requestAnimationFrame(update);
    }
    
    loadKPIs();
    
    // Lazy load tabs
    const tabsLoaded = { clientes: false, citas: false, facturas: false };
    let activeTab = 'clientes';
    
    // Inicializar primer tab
    setTimeout(() => {
        if (typeof window.initClientesTab === 'function') {
            window.initClientesTab();
            tabsLoaded.clientes = true;
        }
    }, 100);
    
    document.querySelectorAll('#asesoria-tabs [data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            activeTab = e.target.dataset.tabId;
            
            if (!tabsLoaded[activeTab]) {
                const initFn = window[`init${activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}Tab`];
                if (typeof initFn === 'function') {
                    initFn();
                    tabsLoaded[activeTab] = true;
                }
            }
            
            // Actualizar placeholder de búsqueda
            const searchInput = document.getElementById('header-search-input');
            if (searchInput) {
                const labels = { clientes: 'clientes', citas: 'citas', facturas: 'facturas' };
                searchInput.placeholder = `Buscar ${labels[activeTab]}...`;
                searchInput.value = '';
            }
        });
    });
    
    // Búsqueda global desde header
    window.filterAsesoriaDashboard = function(query) {
        const filterFn = window[`filter${activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}`];
        if (typeof filterFn === 'function') filterFn(query);
    };
    
    // Copiar código
    document.getElementById('btn-copy-code').addEventListener('click', function() {
        const code = document.getElementById('advisory-code-input').value;
        navigator.clipboard.writeText(code).then(() => Swal.fire({ icon: 'success', title: 'Código copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }));
    });

    // Copiar link
    document.getElementById('btn-copy-link').addEventListener('click', function() {
        const link = document.getElementById('advisory-link').value;
        navigator.clipboard.writeText(link).then(() => Swal.fire({ icon: 'success', title: 'Link de registro copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }));
    });
});
</script>