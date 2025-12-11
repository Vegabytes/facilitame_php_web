<!-- ============================================
     SOLICITUDES COMERCIAL - PAGINACIÓN SERVER-SIDE
     ============================================ -->

<!-- Controles superiores -->
<div class="list-controls" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border);">
    <div class="results-info">
        <span id="solicitudes-results-count">Cargando...</span>
    </div>
    <div class="list-filters" style="display: flex; gap: 0.75rem; align-items: center;">
        <select id="solicitudes-status-filter" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
            <option value="">Todos los estados</option>
            <option value="1">Iniciado</option>
            <option value="2">Oferta disponible</option>
            <option value="3">Aceptada</option>
            <option value="4">En curso</option>
            <option value="5">Rechazada</option>
            <option value="6">Llamada sin respuesta</option>
            <option value="7">Activada</option>
            <option value="8">Revisión solicitada</option>
            <option value="9">Eliminada</option>
            <option value="10">Aplazada</option>
            <option value="11">Desactivada</option>
        </select>
        <div class="pagination-size">
            <label for="solicitudes-page-size">Mostrar:</label>
            <select id="solicitudes-page-size" class="form-select form-select-sm">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
</div>

<!-- Container del listado con scroll -->
<div class="tab-list-container" id="solicitudes-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        <span class="ms-2">Cargando solicitudes...</span>
    </div>
</div>

<!-- Paginador fijo abajo -->
<div class="pagination-container" id="solicitudes-pagination" style="display: none;">
    <div class="pagination-info" id="solicitudes-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="solicitudes-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="solicitudes-page-current">1 / 1</span>
        <button class="btn-pagination" id="solicitudes-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/requests-paginated-sales';
    
    // Mapeo status_id → clase CSS
    const STATUS_CLASS = {
        1: 'primary',   // Iniciado
        2: 'info',      // Oferta disponible
        3: 'success',   // Aceptada
        4: 'info',      // En curso
        5: 'danger',    // Rechazada
        6: 'warning',   // Sin respuesta
        7: 'success',   // Activada
        8: 'warning',   // Revisión
        9: 'muted',     // Eliminada
        10: 'warning',  // Aplazada
        11: 'muted'     // Desactivada
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
    
    const listContainer = document.getElementById('solicitudes-list');
    const resultsCount = document.getElementById('solicitudes-results-count');
    const pageInfo = document.getElementById('solicitudes-page-info');
    const pageCurrent = document.getElementById('solicitudes-page-current');
    const prevBtn = document.getElementById('solicitudes-prev');
    const nextBtn = document.getElementById('solicitudes-next');
    const pageSizeSelect = document.getElementById('solicitudes-page-size');
    const statusFilterSelect = document.getElementById('solicitudes-status-filter');
    const paginationContainer = document.getElementById('solicitudes-pagination');
    
    let searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        statusFilterSelect.addEventListener('change', handleStatusFilterChange);
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
            
            // Añadir filtro de estado solo si está seleccionado
            if (state.statusFilter) {
                params.append('status', state.statusFilter);
            }
            
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
            const filterActive = state.searchQuery || state.statusFilter;
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-folder"></i></div>
                    <div class="empty-state-title">${filterActive ? 'Sin resultados' : 'No hay solicitudes'}</div>
                    <p class="empty-state-text">${filterActive ? 'No se encontraron solicitudes con los filtros aplicados' : 'Las solicitudes de tus clientes aparecerán aquí'}</p>
                    ${filterActive ? '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.clearSolicitudesFilters()"><i class="ki-outline ki-arrows-circle me-1"></i>Limpiar filtros</button>' : ''}
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const statusClass = STATUS_CLASS[item.status_id] || 'muted';
            const newBadge = item.has_notification 
                ? '<span class="badge-status badge-status-danger ms-2">Nuevo</span>' 
                : '';
            
            return `
                <div class="list-card list-card-${statusClass}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${item.id}</span>
                            <a href="/customer?id=${item.user_id}" class="list-card-customer">
                                ${escapeHtml(item.customer_full_name || 'Sin nombre')}
                            </a>
                            ${newBadge}
                            <span class="text-muted">›</span>
                            <span class="text-muted">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-sms"></i> ${escapeHtml(item.customer_email || '')}</span>
                            <span><i class="ki-outline ki-calendar"></i> ${item.request_date || '-'}</span>
                            <span class="badge-status badge-status-${statusClass}">${escapeHtml(item.status || '')}</span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.id}" class="btn-icon btn-icon-info" title="Ver solicitud">
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
    
    function handleStatusFilterChange(e) {
        state.statusFilter = e.target.value;
        state.currentPage = 1;
        loadData();
    }
    
    function updatePaginationControls() {
        pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        pageInfo.innerHTML = `Mostrando página ${state.currentPage} de ${state.totalPages}`;
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
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadSolicitudes()">
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
    
    // Limpiar filtros
    window.clearSolicitudesFilters = function() {
        state.searchQuery = '';
        state.statusFilter = '';
        state.currentPage = 1;
        statusFilterSelect.value = '';
        const headerSearch = document.getElementById('header-search-input');
        if (headerSearch) headerSearch.value = '';
        loadData();
    };
    
    // Búsqueda desde header
    window.filterSolicitudes = function(query) {
        if (!window._solicitudesInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadSolicitudes = () => window._solicitudesInitialized && loadData();
    
    // Lazy loading - se activa cuando el tab es visible
    window.initSolicitudesTab = function() {
        if (!window._solicitudesInitialized) {
            window._solicitudesInitialized = true;
            init();
        }
    };
    
    // Auto-init si es el tab activo por defecto
    if (document.querySelector('#tab-solicitudes.active')) {
        window.initSolicitudesTab();
    }
})();
</script>