<!-- Componente: home-datatable-clientes-asesoria.php -->

<div class="list-controls">
    <div class="results-info"><span id="clientes-results-count">Cargando...</span></div>
    <div class="pagination-size">
        <label for="clientes-page-size">Mostrar:</label>
        <select id="clientes-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="clientes-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span class="ms-2">Cargando...</span>
    </div>
</div>

<div class="pagination-container" id="clientes-pagination" style="display: none;">
    <div class="pagination-info" id="clientes-page-info"></div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="clientes-prev" disabled><i class="ki-outline ki-left"></i></button>
        <span class="pagination-current" id="clientes-page-current">1 / 1</span>
        <button class="btn-pagination" id="clientes-next" disabled><i class="ki-outline ki-right"></i></button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api-advisory-clients-paginated';
    const state = { currentPage: 1, pageSize: 25, searchQuery: '', totalPages: 1, totalRecords: 0, isLoading: false };
    
    const els = {
        list: document.getElementById('clientes-list'),
        resultsCount: document.getElementById('clientes-results-count'),
        pageInfo: document.getElementById('clientes-page-info'),
        pageCurrent: document.getElementById('clientes-page-current'),
        prevBtn: document.getElementById('clientes-prev'),
        nextBtn: document.getElementById('clientes-next'),
        pageSizeSelect: document.getElementById('clientes-page-size'),
        pagination: document.getElementById('clientes-pagination')
    };
    
    let searchTimeout = null;
    
    function init() {
        els.prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        els.nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        els.pageSizeSelect.addEventListener('change', e => {
            state.pageSize = +e.target.value;
            state.currentPage = 1;
            loadData();
        });
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
            els.list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-people"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay clientes'}</div>
                    <p class="empty-state-text">${state.searchQuery ? 'No se encontraron resultados para tu búsqueda' : 'Comparte tu código de asesoría para captar clientes'}</p>
                </div>`;
            els.pagination.style.display = 'none';
            return;
        }
        
        els.list.innerHTML = data.map(c => {
            const fullName = `${c.name || ''} ${c.lastname || ''}`.trim();
            const typeLabel = getTypeLabel(c.client_type);
            const hasPhone = c.phone && c.phone.trim();
            const createdAt = formatDate(c.created_at);
            
            return `
                <div class="list-card list-card-primary">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/customer?id=${c.id}" class="list-card-link">${escapeHtml(fullName)}</a>
                            ${typeLabel ? `<span class="badge-status badge-status-light">${typeLabel}</span>` : ''}
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-sms"></i> ${escapeHtml(c.email)}</span>
                            ${hasPhone ? `<span><i class="ki-outline ki-phone"></i> ${escapeHtml(c.phone)}</span>` : ''}
                            <span><i class="ki-outline ki-calendar"></i> ${createdAt}</span>
                            ${c.services_number > 0 ? `<span><i class="ki-outline ki-folder"></i> ${c.services_number} solicitud${c.services_number !== 1 ? 'es' : ''}</span>` : ''}
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${c.id}" class="btn-icon" title="Ver cliente">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <a href="/chat?customer=${c.id}" class="btn-icon btn-light-info" title="Enviar mensaje">
                            <i class="ki-outline ki-message-text"></i>
                        </a>
                        ${hasPhone ? `
                            <a href="tel:${c.phone}" class="btn-icon btn-light-success" title="Llamar">
                                <i class="ki-outline ki-phone"></i>
                            </a>
                        ` : ''}
                    </div>
                </div>`;
        }).join('');
        
        els.pagination.style.display = 'flex';
        els.list.scrollTop = 0;
    }
    
    function getTypeLabel(type) {
        const labels = {
            'autonomo': 'Autónomo',
            'empresa': 'Empresa',
            'comunidad': 'Comunidad',
            'asociacion': 'Asociación',
            'particular': 'Particular'
        };
        return labels[type] || '';
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        els.pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        els.pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        els.prevBtn.disabled = state.currentPage <= 1;
        els.nextBtn.disabled = state.currentPage >= state.totalPages;
        els.pagination.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(p) {
        els.resultsCount.innerHTML = p.total_records === 0
            ? 'No hay resultados'
            : `Mostrando <strong>${p.from}-${p.to}</strong> de <strong>${p.total_records}</strong>`;
    }
    
    function showLoading() {
        els.list.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(msg) {
        els.list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(msg)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadClientes()">
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
    
    window.initClientesTab = function() {
        if (!window._clientesInit) {
            window._clientesInit = true;
            init();
        }
    };
    
    window.filterClientes = function(query) {
        if (!window._clientesInit) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadClientes = () => loadData();
})();
</script>