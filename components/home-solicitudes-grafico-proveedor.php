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

    <!-- Gráfico mejorado con leyenda lateral -->
    <div id="grafico-container" class="grafico-container-enhanced" style="display:none;">
        <div class="grafico-layout">
            <div class="grafico-chart-wrapper">
                <div id="kt_amcharts_3" class="grafico-amcharts"></div>
                <div class="grafico-center-label" id="grafico-total">
                    <span class="grafico-total-value">0</span>
                    <span class="grafico-total-label">Total</span>
                </div>
            </div>
            <div class="grafico-legend" id="grafico-legend"></div>
        </div>
    </div>
</div>

<style>
.grafico-container-enhanced {
    padding: 1.5rem;
}
.grafico-layout {
    display: flex;
    align-items: center;
    gap: 2rem;
    min-height: 280px;
}
.grafico-chart-wrapper {
    position: relative;
    flex: 0 0 280px;
    height: 280px;
}
.grafico-amcharts {
    width: 100%;
    height: 100%;
}
.grafico-center-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}
.grafico-total-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--f-text-dark, #1e293b);
    line-height: 1;
}
.grafico-total-label {
    display: block;
    font-size: 0.8125rem;
    color: var(--f-text-medium, #64748b);
    margin-top: 0.25rem;
}
.grafico-legend {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}
.grafico-legend-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--f-bg-light, #f8fafc);
    border-radius: 10px;
    transition: all 0.2s ease;
    cursor: default;
}
.grafico-legend-item:hover {
    background: var(--f-bg-medium, #f1f5f9);
    transform: translateX(4px);
}
.grafico-legend-color {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
}
.grafico-legend-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.grafico-legend-name {
    font-weight: 500;
    color: var(--f-text-dark, #1e293b);
    font-size: 0.875rem;
}
.grafico-legend-stats {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.grafico-legend-value {
    font-weight: 700;
    color: var(--f-text-dark, #1e293b);
    font-size: 1rem;
}
.grafico-legend-percent {
    font-size: 0.75rem;
    color: var(--f-text-medium, #64748b);
    background: rgba(0,0,0,0.05);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .grafico-layout {
        flex-direction: column;
        gap: 1.5rem;
    }
    .grafico-chart-wrapper {
        flex: none;
        width: 100%;
        max-width: 260px;
        height: 260px;
    }
    .grafico-legend {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }
    .grafico-legend-item {
        flex: 0 1 auto;
        padding: 0.5rem 0.75rem;
    }
}
</style>

<script>
(function() {
    let chartRoot = null;
    let initialized = false;

    // Paleta de colores Facilitame
    const CHART_COLORS = [
        '#00c2cb', // Turquesa principal
        '#3b82f6', // Azul
        '#f59e0b', // Naranja/Amarillo
        '#ef4444', // Rojo
        '#10b981', // Verde
        '#8b5cf6', // Morado
        '#ec4899', // Rosa
        '#6366f1'  // Índigo
    ];

    async function loadChartData() {
        try {
            const response = await fetch('/api/dashboard-chart-provider');
            const result = await response.json();

            document.getElementById('grafico-loading').style.display = 'none';

            if (result.status === 'ok' && result.data) {
                const chartData = result.data.chart_data || [];

                if (chartData.length === 0) {
                    document.getElementById('grafico-empty').style.display = 'block';
                    return;
                }

                // Asignar colores a los datos
                chartData.forEach((item, index) => {
                    item.color = am5.color(CHART_COLORS[index % CHART_COLORS.length]);
                    item.colorHex = CHART_COLORS[index % CHART_COLORS.length];
                });

                renderChart(chartData);
                renderLegend(chartData);
            } else {
                document.getElementById('grafico-empty').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading chart data:', error);
            document.getElementById('grafico-loading').style.display = 'none';
            document.getElementById('grafico-empty').style.display = 'block';
        }
    }

    function renderLegend(data) {
        const total = data.reduce((sum, item) => sum + item.value, 0);
        const legendContainer = document.getElementById('grafico-legend');
        const totalEl = document.querySelector('.grafico-total-value');

        // Animar el total
        if (totalEl) {
            animateValue(totalEl, total);
        }

        legendContainer.innerHTML = data.map((item) => {
            const percentage = total > 0 ? Math.round((item.value / total) * 100) : 0;
            return `
                <div class="grafico-legend-item">
                    <span class="grafico-legend-color" style="background: ${item.colorHex}"></span>
                    <div class="grafico-legend-info">
                        <span class="grafico-legend-name">${item.category}</span>
                        <div class="grafico-legend-stats">
                            <span class="grafico-legend-value">${item.value}</span>
                            <span class="grafico-legend-percent">${percentage}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function animateValue(el, target) {
        const duration = 800;
        const start = performance.now();
        const startVal = 0;

        function update(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(startVal + (target - startVal) * eased);
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
        }
        requestAnimationFrame(update);
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

            const container = document.getElementById('grafico-container');
            container.style.display = 'block';

            chartRoot = am5.Root.new("kt_amcharts_3");
            chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

            var chart = chartRoot.container.children.push(am5percent.PieChart.new(chartRoot, {
                innerRadius: am5.percent(55),
                radius: am5.percent(95)
            }));

            var series = chart.series.push(am5percent.PieSeries.new(chartRoot, {
                valueField: "value",
                categoryField: "category",
                fillField: "color"
            }));

            // Estilos de las porciones
            series.slices.template.setAll({
                strokeWidth: 3,
                stroke: am5.color(0xffffff),
                cornerRadius: 8,
                toggleKey: "none",
                cursorOverStyle: "pointer"
            });

            // Hover en las porciones
            series.slices.template.states.create("hover", {
                scale: 1.05,
                shadowOpacity: 0.2,
                shadowBlur: 10,
                shadowColor: am5.color(0x000000)
            });

            // Ocultar labels externos
            series.labels.template.set("forceHidden", true);
            series.ticks.template.set("forceHidden", true);

            // Tooltip limpio
            series.slices.template.set("tooltipText", "{category}: {value}");

            var tooltip = am5.Tooltip.new(chartRoot, {
                getFillFromSprite: false
            });
            tooltip.get("background").setAll({
                fill: am5.color(0xffffff),
                fillOpacity: 1,
                strokeOpacity: 0,
                shadowColor: am5.color(0x000000),
                shadowBlur: 12,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowOpacity: 0.15
            });
            tooltip.label.setAll({
                fill: am5.color(0x1e293b),
                fontSize: 13,
                fontWeight: "500"
            });
            series.slices.template.set("tooltip", tooltip);

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
