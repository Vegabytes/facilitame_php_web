<?php
$scripts = [];
$statuses = get_statuses_names();
?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="services-results-count">Cargando...</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="pagination-size">
                    <label for="filter-status">Estado:</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($statuses as $status) : ?>
                            <option value="<?php echo htmlspecialchars($status) ?>"><?php echo htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pagination-size">
                    <label for="services-page-size">Mostrar:</label>
                    <select id="services-page-size" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="services-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando servicios...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="services-pagination" style="display: none;">
            <div class="pagination-info" id="services-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="services-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="services-page-current">1 / 1</span>
                <button class="btn-pagination" id="services-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/services-paginated-customer';
    
    const STATUS_CLASS = {
        1: 'primary',
        2: 'info',
        3: 'success',
        4: 'info',
        5: 'danger',
        6: 'warning',
        7: 'success',
        8: 'warning',
        9: 'muted',
        10: 'warning',
        11: 'muted'
    };
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        statusFilter: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('services-list');
    const resultsCount = document.getElementById('services-results-count');
    const pageInfo = document.getElementById('services-page-info');
    const pageCurrent = document.getElementById('services-page-current');
    const prevBtn = document.getElementById('services-prev');
    const nextBtn = document.getElementById('services-next');
    const pageSizeSelect = document.getElementById('services-page-size');
    const statusSelect = document.getElementById('filter-status');
    const paginationContainer = document.getElementById('services-pagination');
    
    let searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        statusSelect.addEventListener('change', handleStatusChange);
        loadData();
    }
    
    function handleStatusChange(e) {
        state.statusFilter = e.target.value;
        state.currentPage = 1;
        loadData();
    }
    
    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.pageSize,
                search: state.searchQuery,
                status: state.statusFilter
            });
            
            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                const { data: items, pagination } = result.data;
                state.totalPages = pagination.total_pages;
                state.totalRecords = pagination.total_records;
                
                renderList(items || []);
                updateResultsCount(pagination);
                updatePaginationControls();
            } else {
                showError(result.message || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        } finally {
            state.isLoading = false;
        }
    }
    
    function renderList(data) {
        if (!data.length) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-abstract-26"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery || state.statusFilter ? 'Sin resultados' : 'Sin servicios'}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery || state.statusFilter 
                            ? 'No se encontraron resultados para los filtros aplicados' 
                            : 'Cuando contrates un servicio aparecerá aquí'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const statusClass = STATUS_CLASS[item.status_id] || 'muted';
            const showExtraButtons = item.status_id === 7;
            
            return `
                <div class="list-card list-card-${statusClass}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${item.id}</span>
                            <a href="/request?id=${item.id}" class="list-card-customer">
                                ${escapeHtml(item.category_name || 'Servicio')}
                            </a>
                            <span class="badge-status badge-status-${statusClass}">${escapeHtml(item.status || '')}</span>
                        </div>
                        ${item.request_info ? `
                            <div class="list-card-subtitle text-muted" style="font-size: 0.8125rem; margin-bottom: 0.25rem;">
                                <i class="ki-outline ki-information-2" style="font-size: 0.875rem; margin-right: 0.25rem;"></i>
                                ${escapeHtml(item.request_info)}
                            </div>
                        ` : ''}
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                Solicitado: ${item.request_date || '-'}
                            </span>
                            <span>
                                <i class="ki-outline ki-time"></i>
                                Actualizado: ${item.updated_at || '-'}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        ${showExtraButtons ? `
                            <a href="/request?id=${item.id}&review" class="btn-icon" title="Solicitar revisión">
                                <i class="ki-outline ki-magnifier"></i>
                            </a>
                            <a href="/request?id=${item.id}&incident" class="btn-icon" title="Reportar incidencia">
                                <i class="ki-outline ki-information-2"></i>
                            </a>
                        ` : ''}
                        <a href="/request?id=${item.id}" class="btn-icon" title="Ver detalle">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
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
        pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
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
                <span class="ms-2">Cargando servicios...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-disconnect text-danger"></i>
                </div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadMyServices()">
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
    
    window.filterMyServices = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadMyServices = () => loadData();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>