<!-- ============================================
     REVISIONES PROVEEDOR - PAGINACIÓN SERVER-SIDE
     ============================================ -->

<div class="list-controls" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border);">
    <div class="results-info">
        <span id="revisiones-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="revisiones-page-size">Mostrar:</label>
        <select id="revisiones-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="revisiones-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
        <span class="ms-2">Cargando revisiones...</span>
    </div>
</div>

<div class="pagination-container" id="revisiones-pagination" style="display: none;">
    <div class="pagination-info" id="revisiones-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="revisiones-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="revisiones-page-current">1 / 1</span>
        <button class="btn-pagination" id="revisiones-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/reviews-paginated-provider';
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('revisiones-list');
    const resultsCount = document.getElementById('revisiones-results-count');
    const pageInfo = document.getElementById('revisiones-page-info');
    const pageCurrent = document.getElementById('revisiones-page-current');
    const prevBtn = document.getElementById('revisiones-prev');
    const nextBtn = document.getElementById('revisiones-next');
    const pageSizeSelect = document.getElementById('revisiones-page-size');
    const paginationContainer = document.getElementById('revisiones-pagination');
    
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
                    <div class="empty-state-icon"><i class="ki-outline ki-search-list"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay revisiones'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'Todas las revisiones han sido completadas'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const newBadge = item.has_notification 
                ? '<span class="badge-status badge-status-danger ms-2">Nuevo</span>' 
                : '';
            
            return `
                <div class="list-card list-card-warning">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/customer?id=${item.user_id}" class="list-card-customer">
                                ${escapeHtml(item.customer_name || 'Sin nombre')}
                            </a>
                            ${newBadge}
                            <span class="text-muted">›</span>
                            <span class="text-muted">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-calendar"></i> ${item.created_at || '-'}</span>
                            <span class="badge-status badge-status-warning">Revisión</span>
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
                <div class="spinner-border spinner-border-sm text-info"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-info mt-3" onclick="window.reloadRevisiones()">
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
    
    window.filterRevisiones = function(query) {
        if (!window._revisionesInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadRevisiones = () => window._revisionesInitialized && loadData();
    
    window.initRevisionesTab = function() {
        if (!window._revisionesInitialized) {
            window._revisionesInitialized = true;
            init();
        }
    };
})();
</script>