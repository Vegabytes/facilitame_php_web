<?php
$scripts = [];

// Obtener datos de la asesoría
$advisory_id = 0;
$advisory_code = '';
$advisory_plan = 'gratuito';

$stmt = $pdo->prepare("SELECT id, codigo_identificacion, plan FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory_data = $stmt->fetch();

if ($advisory_data) {
    $advisory_id = $advisory_data['id'];
    $advisory_code = $advisory_data['codigo_identificacion'];
    $advisory_plan = $advisory_data['plan'];
}

$monthNames = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$currentMonth = date('n');
$currentYear = date('Y');
?>

<style>
.metric-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e9ecef;
    height: 100%;
}
.metric-card .metric-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.metric-card .metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.metric-card .metric-icon.primary { background: rgba(153, 73, 255, 0.1); color: #9949FF; }
.metric-card .metric-icon.success { background: rgba(80, 205, 137, 0.1); color: #50cd89; }
.metric-card .metric-icon.warning { background: rgba(255, 199, 0, 0.1); color: #ffc700; }
.metric-card .metric-icon.danger { background: rgba(241, 65, 108, 0.1); color: #f1416c; }
.metric-card .metric-icon.info { background: rgba(0, 158, 247, 0.1); color: #009ef7; }
.metric-card .metric-value {
    font-size: 28px;
    font-weight: 700;
    color: #181c32;
    line-height: 1;
}
.metric-card .metric-label {
    font-size: 13px;
    color: #a1a5b7;
    margin-top: 4px;
}
.metric-card .metric-trend {
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 4px;
}
.metric-card .metric-trend.up { background: rgba(80, 205, 137, 0.1); color: #50cd89; }
.metric-card .metric-trend.down { background: rgba(241, 65, 108, 0.1); color: #f1416c; }
.metric-card .metric-trend.neutral { background: #f5f5f5; color: #a1a5b7; }

.chart-container {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    padding: 20px;
}
.chart-container .chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.chart-container .chart-title {
    font-size: 15px;
    font-weight: 600;
    color: #181c32;
}

.quick-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.quick-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #e9ecef;
    background: #fff;
    color: #5e6278;
    cursor: pointer;
    transition: all 0.2s;
}
.quick-action-btn:hover {
    border-color: #9949FF;
    color: #9949FF;
    background: rgba(153, 73, 255, 0.05);
}
.quick-action-btn i {
    font-size: 16px;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
}
.upcoming-item:last-child {
    border-bottom: none;
}
.upcoming-item .date-badge {
    min-width: 50px;
    text-align: center;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 8px;
}
.upcoming-item .date-badge .day {
    font-size: 18px;
    font-weight: 700;
    color: #181c32;
    line-height: 1;
}
.upcoming-item .date-badge .month {
    font-size: 11px;
    color: #a1a5b7;
    text-transform: uppercase;
}
.upcoming-item .item-info {
    flex: 1;
}
.upcoming-item .item-title {
    font-weight: 600;
    color: #181c32;
    font-size: 13px;
}
.upcoming-item .item-meta {
    font-size: 12px;
    color: #a1a5b7;
}
</style>

<div class="dashboard-asesoria-home">

    <!--begin::Code Card-->
    <div class="card code-card-compact mb-4">
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

    <!--begin::KPIs principales-->
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon primary">
                        <i class="ki-outline ki-people fs-2"></i>
                    </div>
                    <span class="metric-trend neutral" id="trend-clientes">—</span>
                </div>
                <div class="metric-value" id="kpi-clientes"><span class="skeleton-loader"></span></div>
                <div class="metric-label">Clientes totales</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon warning">
                        <i class="ki-outline ki-calendar fs-2"></i>
                    </div>
                </div>
                <div class="metric-value" id="kpi-citas"><span class="skeleton-loader"></span></div>
                <div class="metric-label">Citas pendientes</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon danger">
                        <i class="ki-outline ki-document fs-2"></i>
                    </div>
                    <span class="metric-trend neutral" id="trend-facturas">—</span>
                </div>
                <div class="metric-value" id="kpi-facturas"><span class="skeleton-loader"></span></div>
                <div class="metric-label">Facturas por procesar</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon info">
                        <i class="ki-outline ki-message-text fs-2"></i>
                    </div>
                </div>
                <div class="metric-value" id="kpi-mensajes"><span class="skeleton-loader"></span></div>
                <div class="metric-label">Mensajes sin leer</div>
            </div>
        </div>
    </div>
    <!--end::KPIs principales-->

    <div class="row g-4 mb-4">
        <!--begin::Gráfico facturas-->
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Facturas últimos 6 meses</div>
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="exportarFacturas('all')">
                            <i class="ki-outline ki-file-down"></i>
                            Exportar todo
                        </button>
                        <button class="quick-action-btn" onclick="exportarFacturas('quarter')">
                            <i class="ki-outline ki-calendar"></i>
                            Trimestre
                        </button>
                    </div>
                </div>
                <div id="chart-facturas" style="height: 280px;"></div>
            </div>
        </div>
        <!--end::Gráfico facturas-->

        <!--begin::Próximas citas-->
        <div class="col-lg-4">
            <div class="chart-container h-100">
                <div class="chart-header">
                    <div class="chart-title">Próximas citas</div>
                    <a href="/appointments" class="btn btn-sm btn-light">Ver todas</a>
                </div>
                <div id="proximas-citas">
                    <div class="text-center py-5 text-muted">
                        <span class="spinner-border spinner-border-sm"></span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Próximas citas-->
    </div>

    <!--begin::Acciones rápidas y resumen-->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Clientes por tipo</div>
                    <button class="quick-action-btn" onclick="exportarClientes()">
                        <i class="ki-outline ki-file-down"></i>
                        Exportar
                    </button>
                </div>
                <div id="chart-clientes-tipo" style="height: 200px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Facturas por trimestre <?php echo $currentYear; ?></div>
                </div>
                <div id="chart-trimestres" style="height: 200px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Resumen del mes</div>
                </div>
                <div class="py-2">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Clientes nuevos</span>
                        <span class="fw-bold" id="mes-clientes-nuevos">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Facturas recibidas</span>
                        <span class="fw-bold" id="mes-facturas">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Citas realizadas</span>
                        <span class="fw-bold" id="mes-citas">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Facturas procesadas</span>
                        <span class="fw-bold text-success" id="mes-procesadas">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Acciones rápidas-->

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

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const monthNames = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    let dashboardData = null;

    // Cargar KPIs y métricas
    async function loadDashboard() {
        try {
            const response = await fetch('/api/dashboard-kpis-advisory');
            const result = await response.json();

            if (result.status === 'ok' && result.data) {
                dashboardData = result.data;
                updateKPIs(result.data);
                updateCharts(result.data);
                updateProximasCitas(result.data.proximas_citas);
                updateResumenMes(result.data);
            } else {
                showError();
            }
        } catch (e) {
            console.error('Error:', e);
            showError();
        }
    }

    function updateKPIs(data) {
        animateValue('kpi-clientes', data.clientes);
        animateValue('kpi-citas', data.citas);
        animateValue('kpi-facturas', data.facturas);
        animateValue('kpi-mensajes', data.mensajes);

        document.getElementById('badge-clientes').textContent = data.clientes;
        document.getElementById('badge-citas').textContent = data.citas;
        document.getElementById('badge-facturas').textContent = data.facturas;

        // Tendencias
        if (data.mes_actual) {
            updateTrend('trend-clientes', data.mes_actual.variacion_clientes, '+' + data.mes_actual.clientes_nuevos + ' mes');
            updateTrend('trend-facturas', data.mes_actual.variacion_facturas, data.mes_actual.facturas_recibidas + ' mes');
        }
    }

    function updateTrend(id, variation, label) {
        const el = document.getElementById(id);
        if (!el) return;

        let cls = 'neutral';
        let icon = '';
        if (variation > 0) {
            cls = 'up';
            icon = '<i class="ki-outline ki-arrow-up fs-7"></i>';
        } else if (variation < 0) {
            cls = 'down';
            icon = '<i class="ki-outline ki-arrow-down fs-7"></i>';
        }

        el.className = 'metric-trend ' + cls;
        el.innerHTML = icon + label;
    }

    function updateCharts(data) {
        // Gráfico de facturas por mes
        if (data.facturas_por_mes && data.facturas_por_mes.length > 0) {
            const categories = data.facturas_por_mes.map(m => {
                const parts = m.mes.split('-');
                return monthNames[parseInt(parts[1])] + ' ' + parts[0].slice(2);
            });

            new ApexCharts(document.getElementById('chart-facturas'), {
                chart: { type: 'bar', height: 280, toolbar: { show: false } },
                series: [
                    { name: 'Gastos', data: data.facturas_por_mes.map(m => m.gastos || 0) },
                    { name: 'Ingresos', data: data.facturas_por_mes.map(m => m.ingresos || 0) }
                ],
                colors: ['#f1416c', '#50cd89'],
                xaxis: { categories: categories },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
                legend: { position: 'top' },
                dataLabels: { enabled: false }
            }).render();
        } else {
            document.getElementById('chart-facturas').innerHTML = '<div class="text-center text-muted py-10">Sin datos</div>';
        }

        // Gráfico clientes por tipo
        if (data.clientes_por_tipo && Object.keys(data.clientes_por_tipo).length > 0) {
            const labels = { autonomo: 'Autónomos', empresa: 'Empresas', comunidad: 'Comunidades', asociacion: 'Asociaciones' };
            new ApexCharts(document.getElementById('chart-clientes-tipo'), {
                chart: { type: 'donut', height: 200 },
                series: Object.values(data.clientes_por_tipo).map(v => parseInt(v)),
                labels: Object.keys(data.clientes_por_tipo).map(k => labels[k] || k),
                colors: ['#9949FF', '#009ef7', '#ffc700', '#50cd89'],
                legend: { position: 'bottom', fontSize: '12px' },
                dataLabels: { enabled: false }
            }).render();
        } else {
            document.getElementById('chart-clientes-tipo').innerHTML = '<div class="text-center text-muted py-5">Sin datos</div>';
        }

        // Gráfico por trimestre
        if (data.facturas_por_trimestre && data.facturas_por_trimestre.length > 0) {
            const trimestres = [0, 0, 0, 0];
            data.facturas_por_trimestre.forEach(t => {
                trimestres[t.trimestre - 1] = parseInt(t.total);
            });

            new ApexCharts(document.getElementById('chart-trimestres'), {
                chart: { type: 'bar', height: 200, toolbar: { show: false } },
                series: [{ name: 'Facturas', data: trimestres }],
                colors: ['#9949FF'],
                xaxis: { categories: ['T1', 'T2', 'T3', 'T4'] },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
                dataLabels: { enabled: true, style: { fontSize: '12px' } }
            }).render();
        } else {
            document.getElementById('chart-trimestres').innerHTML = '<div class="text-center text-muted py-5">Sin datos</div>';
        }
    }

    function updateProximasCitas(citas) {
        const container = document.getElementById('proximas-citas');
        if (!citas || citas.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4"><i class="ki-outline ki-calendar fs-3x mb-2 d-block opacity-50"></i>No hay citas próximas</div>';
            return;
        }

        let html = '';
        citas.forEach(cita => {
            let day = '--';
            let month = '';
            let timeStr = '';

            if (cita.scheduled_date) {
                const date = new Date(cita.scheduled_date);
                day = date.getDate();
                month = monthNames[date.getMonth() + 1] || '';
                timeStr = date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
            } else {
                month = 'Pend.';
            }

            const typeLabels = {
                'llamada': 'Llamada',
                'reunion_presencial': 'Presencial',
                'reunion_virtual': 'Virtual'
            };
            const typeLabel = typeLabels[cita.type] || cita.type || '';

            html += `
                <div class="upcoming-item">
                    <div class="date-badge">
                        <div class="day">${day}</div>
                        <div class="month">${month}</div>
                    </div>
                    <div class="item-info">
                        <div class="item-title">${cita.customer_name || 'Cliente'}</div>
                        <div class="item-meta">${timeStr ? timeStr + ' - ' : ''}${typeLabel}${cita.subject ? ' - ' + cita.subject : ''}</div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function updateResumenMes(data) {
        if (data.mes_actual) {
            document.getElementById('mes-clientes-nuevos').textContent = data.mes_actual.clientes_nuevos;
            document.getElementById('mes-facturas').textContent = data.mes_actual.facturas_recibidas;
            document.getElementById('mes-citas').textContent = data.mes_actual.citas_realizadas;
        }
        if (data.facturas_detalle) {
            document.getElementById('mes-procesadas').textContent = data.facturas_detalle.total_procesadas + '/' + data.facturas_detalle.total;
        }
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
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
        }
        requestAnimationFrame(update);
    }

    function showError() {
        ['kpi-clientes', 'kpi-citas', 'kpi-facturas', 'kpi-mensajes'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '—';
        });
    }

    loadDashboard();

    // Exportar
    window.exportarFacturas = function(tipo) {
        let url = '/api/advisory-export-invoices?';
        if (tipo === 'quarter') {
            const q = Math.ceil(new Date().getMonth() / 3) || 1;
            url += 'quarter=' + q + '&year=' + new Date().getFullYear();
        }
        window.location.href = url;
    };

    window.exportarClientes = function() {
        window.location.href = '/api/advisory-export-customers';
    };

    // Lazy load tabs
    const tabsLoaded = { clientes: false, citas: false, facturas: false };
    let activeTab = 'clientes';

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
        });
    });

    window.filterAsesoriaDashboard = function(query) {
        const filterFn = window[`filter${activeTab.charAt(0).toUpperCase() + activeTab.slice(1)}`];
        if (typeof filterFn === 'function') filterFn(query);
    };

    // Copiar código
    document.getElementById('btn-copy-code').addEventListener('click', function() {
        const code = document.getElementById('advisory-code-input').value;
        navigator.clipboard.writeText(code).then(() => Swal.fire({ icon: 'success', title: 'Código copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }));
    });

    document.getElementById('btn-copy-link').addEventListener('click', function() {
        const link = document.getElementById('advisory-link').value;
        navigator.clipboard.writeText(link).then(() => Swal.fire({ icon: 'success', title: 'Link de registro copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }));
    });
});
</script>
