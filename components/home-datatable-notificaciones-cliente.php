<!-- ============================================
     NOTIFICACIONES CLIENTE - PAGINACIÓN SERVER-SIDE
     ============================================ -->

<div class="list-controls">
    <div class="results-info">
        <span id="notif-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="notif-page-size">Mostrar:</label>
        <select id="notif-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="notif-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        <span class="ms-2">Cargando notificaciones...</span>
    </div>
</div>

<div class="pagination-container" id="notif-pagination" style="display: none;">
    <div class="pagination-info" id="notif-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="notif-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="notif-page-current">1 / 1</span>
        <button class="btn-pagination" id="notif-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/notifications-client-paginated';
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('notif-list');
    const resultsCount = document.getElementById('notif-results-count');
    const pageInfo = document.getElementById('notif-page-info');
    const pageCurrent = document.getElementById('notif-page-current');
    const prevBtn = document.getElementById('notif-prev');
    const nextBtn = document.getElementById('notif-next');
    const pageSizeSelect = document.getElementById('notif-page-size');
    const paginationContainer = document.getElementById('notif-pagination');
    
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
                    <div class="empty-state-icon"><i class="ki-outline ki-notification-bing"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'Sin notificaciones'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'No hay notificaciones que mostrar'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const isUnread = item.is_unread || item.notification_status == 0;
            const unreadDot = isUnread ? '<span class="notification-unread-dot"></span>' : '';
            const cardClass = isUnread ? 'list-card-primary' : '';
            
            return `
                <div class="list-card ${cardClass}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            ${unreadDot}
                            <a href="/request?id=${item.id}">Solicitud #${item.id}</a>
                            <span class="text-muted">›</span>
                            <span class="text-muted">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-time"></i> ${escapeHtml(item.time_from || item.created_at || '-')}</span>
                            ${item.status_badge || ''}
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.id}" class="btn-icon btn-icon-primary" title="Ver solicitud">
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadNotificaciones()">
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
    window.filterNotificaciones = function(query) {
        if (!window._notificacionesInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadNotificaciones = () => window._notificacionesInitialized && loadData();
    
    // Exponer manager para compatibilidad
    window.notificacionesManager = {
        get currentSearch() { return state.searchQuery; },
        set currentSearch(val) { state.searchQuery = val; },
        get currentPage() { return state.currentPage; },
        set currentPage(val) { state.currentPage = val; },
        loadData: () => loadData()
    };
    
    // Lazy loading - se inicializa automáticamente porque es la tab activa por defecto
    window.initNotificacionesTab = function() {
        if (!window._notificacionesInitialized) {
            window._notificacionesInitialized = true;
            init();
        }
    };
    
    // Auto-init porque es la primera tab
    document.addEventListener('DOMContentLoaded', () => {
        window._notificacionesInitialized = true;
        init();
    });
})();
</script>