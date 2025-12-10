<!-- ============================================
     INCIDENCIAS ADMIN - PAGINACIÓN SERVER-SIDE
     Estructura unificada con provider/comercial
     ============================================ -->

<!-- Controles superiores -->
<div class="list-controls">
    <div class="results-info">
        <span id="incidencias-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="incidencias-page-size">Mostrar:</label>
        <select id="incidencias-page-size" class="form-select form-select-sm">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<!-- Container del listado con scroll -->
<div class="tab-list-container" id="incidencias-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
        <span class="ms-2">Cargando incidencias...</span>
    </div>
</div>

<!-- Paginador fijo abajo -->
<div class="pagination-container" id="incidencias-pagination" style="display: none;">
    <div class="pagination-info" id="incidencias-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="incidencias-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="incidencias-page-current">1 / 1</span>
        <button class="btn-pagination" id="incidencias-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/incidents-paginated-admin';
    
    // Mapeo status_id incidencias → clase CSS
    const STATUS_CLASS = {
        1: 'danger',    // Abierta
        2: 'warning',   // Gestionando
        3: 'info',      // Validada
        10: 'muted'     // Cerrada
    };
    
    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('incidencias-list');
    const resultsCount = document.getElementById('incidencias-results-count');
    const pageInfo = document.getElementById('incidencias-page-info');
    const pageCurrent = document.getElementById('incidencias-page-current');
    const prevBtn = document.getElementById('incidencias-prev');
    const nextBtn = document.getElementById('incidencias-next');
    const pageSizeSelect = document.getElementById('incidencias-page-size');
    const paginationContainer = document.getElementById('incidencias-pagination');
    
    let searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
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
                search: state.searchQuery
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
                    <div class="empty-state-icon"><i class="ki-outline ki-information-2"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay incidencias'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'Todas las incidencias están resueltas'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const statusClass = STATUS_CLASS[item.status_id] || 'muted';
            const newBadge = item.has_notification 
                ? '<span class="badge-status badge-status-danger ms-2">Nuevo</span>' 
                : '';
            const details = item.details ? item.details.substring(0, 100) + (item.details.length > 100 ? '...' : '') : '';
            
            return `
                <div class="list-card list-card-${statusClass}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/customer?id=${item.user_id}" class="list-card-customer">
                                ${escapeHtml(item.customer_name || 'Cliente')}
                            </a>
                            ${newBadge}
                            <span class="text-muted">›</span>
                            <span class="text-muted">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-sms"></i> ${escapeHtml(item.customer_email || '')}</span>
                            <span><i class="ki-outline ki-calendar"></i> ${item.created_at || '-'}</span>
                            <span class="badge-status badge-status-${statusClass}">${escapeHtml(item.status_name || '')}</span>
                        </div>
                        ${details ? `<div class="list-card-description text-muted mt-1" style="font-size: 0.8125rem;">${escapeHtml(details)}</div>` : ''}
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.request_id}" class="btn-icon btn-light-primary" title="Ver solicitud">
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
                <div class="spinner-border spinner-border-sm text-danger"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-danger mt-3" onclick="window.reloadIncidencias()">
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
    
    // Búsqueda desde header
    window.filterIncidencias = function(query) {
        if (!window._incidenciasInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadIncidencias = () => window._incidenciasInitialized && loadData();
    
    // Lazy loading
    window.initIncidenciasTab = function() {
        if (!window._incidenciasInitialized) {
            window._incidenciasInitialized = true;
            init();
        }
    };
    
    // Exponer manager para búsqueda desde header
    window.incidenciasManager = {
        get currentSearch() { return state.searchQuery; },
        set currentSearch(val) { state.searchQuery = val; },
        get currentPage() { return state.currentPage; },
        set currentPage(val) { state.currentPage = val; },
        loadData: loadData
    };
})();
</script>