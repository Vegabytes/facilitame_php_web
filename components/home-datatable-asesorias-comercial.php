<!-- Componente: home-datatable-asesorias-comercial.php -->

<div class="list-controls">
    <div class="results-info"><span id="asesorias-results-count">Cargando...</span></div>
    <div class="pagination-size">
        <label for="asesorias-page-size">Mostrar:</label>
        <select id="asesorias-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<div class="tab-list-container" id="asesorias-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span class="ms-2">Cargando...</span>
    </div>
</div>

<div class="pagination-container" id="asesorias-pagination" style="display: none;">
    <div class="pagination-info" id="asesorias-page-info"></div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="asesorias-prev" disabled><i class="ki-outline ki-left"></i></button>
        <span class="pagination-current" id="asesorias-page-current">1 / 1</span>
        <button class="btn-pagination" id="asesorias-next" disabled><i class="ki-outline ki-right"></i></button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api-salesrep-advisories-paginated';
    const state = { currentPage: 1, pageSize: 25, searchQuery: '', totalPages: 1, totalRecords: 0, isLoading: false };
    
    const els = {
        list: document.getElementById('asesorias-list'),
        resultsCount: document.getElementById('asesorias-results-count'),
        pageInfo: document.getElementById('asesorias-page-info'),
        pageCurrent: document.getElementById('asesorias-page-current'),
        prevBtn: document.getElementById('asesorias-prev'),
        nextBtn: document.getElementById('asesorias-next'),
        pageSizeSelect: document.getElementById('asesorias-page-size'),
        pagination: document.getElementById('asesorias-pagination')
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
                    <div class="empty-state-icon"><i class="ki-outline ki-briefcase"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay asesorías'}</div>
                    <p class="empty-state-text">${state.searchQuery ? 'No se encontraron resultados para tu búsqueda' : 'Las asesorías que se registren con tu código aparecerán aquí'}</p>
                </div>`;
            els.pagination.style.display = 'none';
            return;
        }
        
        els.list.innerHTML = data.map(a => {
            const statusConfig = getStatusConfig(a.estado);
            const planConfig = getPlanConfig(a.plan);
            const createdAt = formatDate(a.created_at);
            
            return `
                <div class="list-card list-card-info">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/advisory?id=${a.id}" class="list-card-link">${escapeHtml(a.razon_social)}</a>
                            <span class="badge-status badge-status-${statusConfig.class}">${statusConfig.label}</span>
                            <span class="badge-status badge-status-${planConfig.class}">${planConfig.label}</span>
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-document"></i> ${escapeHtml(a.cif)}</span>
                            <span><i class="ki-outline ki-sms"></i> ${escapeHtml(a.email_empresa)}</span>
                            <span><i class="ki-outline ki-calendar"></i> ${createdAt}</span>
                            ${a.clients_count > 0 ? `<span><i class="ki-outline ki-people"></i> ${a.clients_count} cliente${a.clients_count !== 1 ? 's' : ''}</span>` : ''}
                        </div>
                        ${a.direccion ? `<div class="list-card-address"><i class="ki-outline ki-geolocation"></i> ${escapeHtml(a.direccion)}</div>` : ''}
                    </div>
                    <div class="list-card-actions">
                        <a href="/advisory?id=${a.id}" class="btn-icon btn-icon-info" title="Ver asesoría">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                    </div>
                </div>`;
        }).join('');
        
        els.pagination.style.display = 'flex';
        els.list.scrollTop = 0;
    }
    
    function getStatusConfig(status) {
        const configs = {
            'activo': { label: 'Activo', class: 'success' },
            'pendiente': { label: 'Pendiente', class: 'warning' },
            'suspendido': { label: 'Suspendido', class: 'danger' }
        };
        return configs[status] || { label: status, class: 'light' };
    }
    
    function getPlanConfig(plan) {
        const configs = {
            'gratuito': { label: 'Gratuito', class: 'light' },
            'basic': { label: 'Basic', class: 'info' },
            'estandar': { label: 'Estándar', class: 'primary' },
            'pro': { label: 'Pro', class: 'success' },
            'premium': { label: 'Premium', class: 'warning' },
            'enterprise': { label: 'Enterprise', class: 'danger' }
        };
        return configs[plan] || { label: plan, class: 'light' };
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadAsesorias()">
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
    
    window.initAsesoriasTab = function() {
        if (!window._asesoriasInit) {
            window._asesoriasInit = true;
            init();
        }
    };
    
    window.filterAsesorias = function(query) {
        if (!window._asesoriasInit) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadAsesorias = () => loadData();
})();
</script>