<!-- ============================================
     OFERTAS CLIENTE - PAGINACIÓN SERVER-SIDE
     ============================================ -->

<div class="list-controls">
    <div class="results-info">
        <span id="ofertas-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="ofertas-page-size">Mostrar:</label>
        <select id="ofertas-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="ofertas-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
        <span class="ms-2">Cargando ofertas...</span>
    </div>
</div>

<div class="pagination-container" id="ofertas-pagination" style="display: none;">
    <div class="pagination-info" id="ofertas-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="ofertas-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="ofertas-page-current">1 / 1</span>
        <button class="btn-pagination" id="ofertas-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/ofertas-client-paginated';
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('ofertas-list');
    const resultsCount = document.getElementById('ofertas-results-count');
    const pageInfo = document.getElementById('ofertas-page-info');
    const pageCurrent = document.getElementById('ofertas-page-current');
    const prevBtn = document.getElementById('ofertas-prev');
    const nextBtn = document.getElementById('ofertas-next');
    const pageSizeSelect = document.getElementById('ofertas-page-size');
    const paginationContainer = document.getElementById('ofertas-pagination');
    
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
                    <div class="empty-state-icon"><i class="ki-outline ki-gift"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'Sin ofertas'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'No hay ofertas disponibles en este momento'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const ahorroFormatted = item.ahorro ? `€${parseFloat(item.ahorro).toFixed(2)}` : '';
            
            return `
                <div class="list-card list-card-success">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/request?id=${item.request_id}">${escapeHtml(item.titulo || 'Oferta')}</a>
                            <span class="text-muted">›</span>
                            <span class="text-muted">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        <div class="list-card-meta">
                            <span class="badge-status badge-status-${item.badge_color || 'success'}">${escapeHtml(item.badge_text || 'Disponible')}</span>
                            <span><i class="ki-outline ki-shop"></i> ${escapeHtml(item.proveedor_name || '')}</span>
                            ${ahorroFormatted ? `<span class="oferta-promo-badge"><i class="ki-outline ki-discount"></i> Ahorro: ${ahorroFormatted}</span>` : ''}
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.request_id}" class="btn-icon btn-icon-success" title="Ver oferta">
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
        paginationContainer.style.display = state.totalRecords > 0 ? 'flex' : 'none';
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0 
            ? 'No hay resultados' 
            : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong>`;
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-success"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-success mt-3" onclick="window.reloadOfertas()">
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
    window.filterOfertas = function(query) {
        if (!window._ofertasInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadOfertas = () => window._ofertasInitialized && loadData();
    
    // Exponer manager para compatibilidad
    window.ofertasManager = {
        get currentSearch() { return state.searchQuery; },
        set currentSearch(val) { state.searchQuery = val; },
        get currentPage() { return state.currentPage; },
        set currentPage(val) { state.currentPage = val; },
        loadData: () => loadData()
    };
    
    // Lazy loading
    window.initOfertasTab = function() {
        if (!window._ofertasInitialized) {
            window._ofertasInitialized = true;
            init();
        }
    };
})();
</script>