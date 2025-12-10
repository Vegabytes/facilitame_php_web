<!-- ============================================
     APLAZADAS ADMIN - PAGINACIÓN SERVER-SIDE
     ============================================ -->

<div class="list-controls">
    <div class="results-info">
        <span id="aplazadas-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="aplazadas-page-size">Mostrar:</label>
        <select id="aplazadas-page-size" class="form-select form-select-sm">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="aplazadas-list">
    <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
    <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
</div>

<div class="pagination-container" id="aplazadas-pagination" style="display: none;">
    <div class="pagination-info" id="aplazadas-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="aplazadas-prev" disabled><i class="ki-outline ki-left"></i></button>
        <span class="pagination-current" id="aplazadas-page-current">1 / 1</span>
        <button class="btn-pagination" id="aplazadas-next" disabled><i class="ki-outline ki-right"></i></button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/postponed-paginated-admin';
    
    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('aplazadas-list');
    const resultsCount = document.getElementById('aplazadas-results-count');
    const pageInfo = document.getElementById('aplazadas-page-info');
    const pageCurrent = document.getElementById('aplazadas-page-current');
    const prevBtn = document.getElementById('aplazadas-prev');
    const nextBtn = document.getElementById('aplazadas-next');
    const pageSizeSelect = document.getElementById('aplazadas-page-size');
    const paginationContainer = document.getElementById('aplazadas-pagination');
    
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
                    <div class="empty-state-icon"><i class="ki-outline ki-time"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay aplazadas'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'Todas las solicitudes están al día'}</p>
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
                            <span><i class="ki-outline ki-sms"></i> ${escapeHtml(item.customer_email || '')}</span>
                            <span><i class="ki-outline ki-calendar"></i> ${item.created_at || '-'}</span>
                            <span class="badge-status badge-status-warning">Aplazada</span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.id}" class="btn-icon" title="Ver solicitud">
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
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-warning mt-3" onclick="window.reloadAplazadas()">
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
    
    window.filterAplazadas = function(query) {
        if (!window._aplazadasInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadAplazadas = () => window._aplazadasInitialized && loadData();
    
    window.initAplazadasTab = function() {
        if (!window._aplazadasInitialized) {
            window._aplazadasInitialized = true;
            init();
        }
    };
})();
</script>