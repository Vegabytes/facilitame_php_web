<?php
// No hay script externo - todo el JS está inline en este archivo
$scripts = [];
?>
<script>
window.commissionTypes = <?php echo json_encode($commission_types ?? []); ?>;
</script>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content">
        <div class="row gx-5 gx-xl-10">
            <div class="col-xl-12 py-6">
                <div class="card card-flush">
                    <!-- Controles -->
                    <div class="list-controls">
                        <div class="results-info">
                            <span id="commissions-results-count">Cargando...</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="pagination-size">
                                <label for="filter-year">Año:</label>
                                <select id="filter-year" class="form-select form-select-sm">
                                    <?php for ($y = 2024; $y <= intval(date("Y") + 1); $y++) : ?>
                                        <option value="<?php echo $y ?>" <?php echo $y == date("Y") ? "selected" : "" ?>><?php echo $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="pagination-size">
                                <label for="filter-month">Mes:</label>
                                <select id="filter-month" class="form-select form-select-sm">
                                    <?php
                                    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    for ($m = 1; $m <= 12; $m++) : ?>
                                        <option value="<?php echo $m ?>" <?php echo $m == intval(date("m")) ? "selected" : "" ?>><?php echo $meses[$m-1] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="pagination-size">
                                <label for="commissions-limit">Mostrar:</label>
                                <select id="commissions-limit" class="form-select form-select-sm">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body py-4">
                        <div class="commissions-scroll-container" id="commissions-scroll-container">
                            <div class="d-flex flex-column gap-2" id="commissions-list">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                        
                        <div class="pagination-controls" id="commissions-pagination">
                            <button class="pagination-btn" id="commissions-prev" disabled>
                                <i class="ki-outline ki-arrow-left"></i> Anterior
                            </button>
                            <div class="pagination-info">
                                <span id="commissions-page-info">Página 1 de 1</span>
                            </div>
                            <button class="pagination-btn" id="commissions-next" disabled>
                                Siguiente <i class="ki-outline ki-arrow-right"></i>
                            </button>
                        </div>
                        
                        <!-- Totales -->
                        <div class="commission-summary mt-4" id="commissions-summary" style="display:none;">
                            <div class="summary-card summary-card-success">
                                <div class="summary-card-icon">
                                    <i class="ki-outline ki-dollar fs-2x text-success"></i>
                                </div>
                                <div class="summary-card-content">
                                    <div class="summary-card-label">Total Comisiones</div>
                                    <div class="summary-card-value" id="total-sales-rep">0,00 €</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/commissions-paginated';
    
    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('commissions-list');
    const scrollContainer = document.getElementById('commissions-scroll-container');
    const resultsCount = document.getElementById('commissions-results-count');
    const pageInfo = document.getElementById('commissions-page-info');
    const prevBtn = document.getElementById('commissions-prev');
    const nextBtn = document.getElementById('commissions-next');
    const pageSizeSelect = document.getElementById('commissions-limit');
    const yearSelect = document.getElementById('filter-year');
    const monthSelect = document.getElementById('filter-month');
    const summaryDiv = document.getElementById('commissions-summary');
    const paginationControls = document.getElementById('commissions-pagination');
    
    let searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        yearSelect.addEventListener('change', () => { state.currentPage = 1; loadData(); });
        monthSelect.addEventListener('change', () => { state.currentPage = 1; loadData(); });
        loadData();
    }
    
    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            const year = yearSelect.value;
            const month = monthSelect.value;
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.pageSize,
                search: state.searchQuery,
                year: year,
                month: month
            });
            
            const response = await fetch(`${API_URL}?${params}`);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                const items = result.data.data || [];
                const pagination = result.data.pagination;
                
                state.totalPages = pagination.total_pages;
                state.totalRecords = pagination.total_records;
                
                renderList(items);
                updateResultsCount(pagination);
                updatePaginationControls();
                updateTotals(result.data.totals, pagination.total_records);
            } else {
                showError(result.message || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión: ' + error.message);
        } finally {
            state.isLoading = false;
        }
    }
    
    function renderList(data) {
        if (!data || data.length === 0) {
            listContainer.innerHTML = state.searchQuery 
                ? `<div class="empty-state">
                     <div class="empty-state-icon"><i class="ki-outline ki-magnifier"></i></div>
                     <div class="empty-state-title">Sin resultados</div>
                     <p class="empty-state-text">No se encontraron comisiones para "${escapeHtml(state.searchQuery)}"</p>
                   </div>`
                : `<div class="empty-state">
                     <div class="empty-state-icon"><i class="ki-outline ki-chart-line-up"></i></div>
                     <div class="empty-state-title">No hay comisiones</div>
                     <p class="empty-state-text">No hay comisiones para el período seleccionado</p>
                   </div>`;
            paginationControls.style.display = 'none';
            return;
        }
        
        let html = '';
        data.forEach(c => {
            const totalAmount = c.total_amount ? formatNumber(c.total_amount) + ' €' : '-';
            const commissionType = window.commissionTypes[c.commission_type_id] || '-';
            
            html += `
                <div class="list-card list-card-success">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${c.id}</span>
                            <span class="fw-semibold">Solicitud</span>
                            <span class="badge-status badge-status-primary">${escapeHtml(c.category_display)}</span>
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-profile-user"></i>
                                <a href="/customer?id=${c.customer_id}" class="text-muted">${escapeHtml(c.customer_name)}</a>
                            </span>
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${c.activated_at} → ${c.expires_at}
                            </span>
                            <span>
                                <i class="ki-outline ki-tag"></i>
                                ${escapeHtml(commissionType)}
                            </span>
                            <span>
                                <i class="ki-outline ki-percentage"></i>
                                ${escapeHtml(c.commission_value)}
                            </span>
                            <span class="text-primary fw-bold">
                                <i class="ki-outline ki-basket"></i>
                                ${totalAmount}
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-card-actions">
                        <div class="commission-amount-box">
                            <div class="commission-amount-label">Tu comisión</div>
                            <div class="commission-amount-value">${formatNumber(c.sales_rep_commission)} €</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
        paginationControls.style.display = 'flex';
        scrollContainer.scrollTop = 0;
    }
    
    function updateTotals(totals, totalRecords) {
        if (totalRecords > 0) {
            document.getElementById('total-sales-rep').textContent = formatNumber(totals.sales_rep_total) + ' €';
            summaryDiv.style.display = 'flex';
        } else {
            summaryDiv.style.display = 'none';
        }
    }
    
    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    window.filterCommissions = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function handlePageSizeChange(e) {
        state.pageSize = parseInt(e.target.value, 10);
        state.currentPage = 1;
        loadData();
    }
    
    function updatePaginationControls() {
        pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationControls.style.display = state.totalRecords <= state.pageSize ? 'none' : 'flex';
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0
            ? 'No hay resultados'
            : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong>`;
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando comisiones...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadCommissions()">
                    <i class="ki-outline ki-arrows-circle me-1"></i>Reintentar
                </button>
            </div>`;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.reloadCommissions = function() { loadData(); };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>