<div class="grafico-solicitudes-body grafico-solicitudes-wrapper">
    <div id="grafico-loading" class="loader-modern">
        <div class="loader-modern-spinner" role="status" aria-label="Cargando"></div>
        <p class="loader-modern-text">Cargando datos del resumen...</p>
    </div>

    <div id="grafico-empty" class="empty-state-modern" style="display:none;">
        <div class="empty-state-icon empty-state-icon-small">
            <i class="ki-outline ki-chart-pie-simple"></i>
        </div>
        <h4 class="empty-state-title">No hay datos</h4>
        <p class="empty-state-text">No se encontraron solicitudes para mostrar</p>
    </div>

    <!-- Solo el gráfico, sin título ni total -->
    <div id="kt_amcharts_3" class="grafico-amcharts" style="height: 280px; width: 100%; display:none;"></div>
</div>

<script>
(function() {
    let chartRoot = null;
    let initialized = false;

    async function loadChartData() {
        try {
            const response = await fetch('/api/dashboard-chart-provider');
            const result = await response.json();

            document.getElementById('grafico-loading').style.display = 'none';

            if (result.status === 'ok' && result.data) {
                const chartData = result.data.chart_data || [];
                // Ya no pintamos el total en ningún sitio

                if (chartData.length === 0) {
                    document.getElementById('grafico-empty').style.display = 'block';
                    return;
                }

                renderChart(chartData);
            } else {
                document.getElementById('grafico-empty').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading chart data:', error);
            document.getElementById('grafico-loading').style.display = 'none';
            document.getElementById('grafico-empty').style.display = 'block';
        }
    }

    function renderChart(data) {
        if (typeof am5 === 'undefined') {
            console.error("amCharts 5 library not loaded");
            return;
        }

        am5.ready(function() {
            if (chartRoot) {
                chartRoot.dispose();
            }

            const chartContainer = document.getElementById('kt_amcharts_3');
            chartContainer.style.display = 'block';

            chartRoot = am5.Root.new("kt_amcharts_3");
            chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

            var chart = chartRoot.container.children.push(am5percent.PieChart.new(chartRoot, {
                layout: chartRoot.verticalLayout
            }));

            var series = chart.series.push(am5percent.PieSeries.new(chartRoot, {
                alignLabels: true,
                valueField: "value",
                categoryField: "category"
            }));

            series.slices.template.setAll({
                strokeWidth: 3,
                stroke: am5.color(0xffffff)
            });

            series.labelsContainer.set("paddingTop", 24);
            series.labels.template.setAll({
                text: "{category}: {value}"
            });

            series.data.setAll(data);
            series.appear(1000, 100);
        });
    }

    // Se engancha al sistema de lazy loading de tabs
    window.initResumenTab = function() {
        if (initialized) return;
        initialized = true;
        loadChartData();
    };

    // Por si la pestaña Resumen ya viene activa al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const activeResumen = document.querySelector(
            '#proveedor-tabs .nav-link.active[data-bs-target="#tab-resumen"]'
        );
        if (activeResumen) {
            window.initResumenTab();
        }
    });
})();
</script>
