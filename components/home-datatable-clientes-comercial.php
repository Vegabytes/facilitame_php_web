<!-- Componente: home-datatable-clientes-comercial.php -->
<?php $scripts = []; ?>

<!-- Controles superiores -->
<div class="list-controls">
    <div class="results-info">
        <span id="clientes-results-count">Cargando...</span>
    </div>
    <div class="pagination-size">
        <label for="clientes-page-size">Mostrar:</label>
        <select id="clientes-page-size" class="form-select form-select-sm">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
        </select>
    </div>
</div>

<!-- Container con scroll -->
<div class="tab-list-container" id="clientes-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span class="ms-2">Cargando...</span>
    </div>
</div>

<!-- Paginador fijo -->
<div class="pagination-container" id="clientes-pagination" style="display: none;">
    <div class="pagination-info" id="clientes-page-info">Página 1 de 1</div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="clientes-prev" disabled>
            <i class="ki-outline ki-left"></i>
        </button>
        <span class="pagination-current" id="clientes-page-current">1 / 1</span>
        <button class="btn-pagination" id="clientes-next" disabled>
            <i class="ki-outline ki-right"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/customers-paginated-sales';
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('clientes-list');
    const resultsCount = document.getElementById('clientes-results-count');
    const pageInfo = document.getElementById('clientes-page-info');
    const pageCurrent = document.getElementById('clientes-page-current');
    const prevBtn = document.getElementById('clientes-prev');
    const nextBtn = document.getElementById('clientes-next');
    const pageSizeSelect = document.getElementById('clientes-page-size');
    const paginationContainer = document.getElementById('clientes-pagination');
    
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
                    <div class="empty-state-icon"><i class="ki-outline ki-people"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay clientes'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'Comparte tu código de invitación para captar clientes'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(c => {
            const hasPhone = c.phone && c.phone.trim() !== '';
            const verifiedBadge = c.is_verified 
                ? '<span class="badge-status badge-status-success">Verificado</span>'
                : '<span class="badge-status badge-status-warning">Pendiente</span>';
            
            const activesBadge = c.active_requests > 0 
                ? `<span class="badge-status badge-status-primary">${c.active_requests} activa${c.active_requests !== 1 ? 's' : ''}</span>`
                : '';
            
            return `
                <div class="list-card list-card-success">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/customer?id=${c.id}" class="list-card-customer">
                                ${escapeHtml(c.full_name)}
                            </a>
                            ${verifiedBadge}
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                <a href="mailto:${escapeHtml(c.email)}" class="text-muted">${escapeHtml(c.email)}</a>
                            </span>
                            ${hasPhone ? `
                                <span>
                                    <i class="ki-outline ki-phone"></i>
                                    <a href="tel:${c.phone}" class="text-muted">${formatPhone(c.phone)}</a>
                                </span>
                            ` : ''}
                            <span><i class="ki-outline ki-calendar"></i> ${c.created_at || '-'}</span>
                            <span><i class="ki-outline ki-folder"></i> ${c.total_requests || 0} solicitud${c.total_requests !== 1 ? 'es' : ''}</span>
                            ${activesBadge}
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${c.id}" class="btn-icon" title="Ver perfil">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <button type="button" class="btn-icon btn-light-success" title="Copiar email" onclick="copyClienteEmail('${escapeHtml(c.email)}', this)">
                            <i class="ki-outline ki-copy"></i>
                        </button>
                        ${hasPhone ? `
                            <a href="tel:${c.phone}" class="btn-icon btn-light-primary" title="Llamar">
                                <i class="ki-outline ki-phone"></i>
                            </a>
                        ` : ''}
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function formatPhone(phone) {
        if (!phone) return '';
        return phone.replace(/\s/g, '').replace(/(.{3})/g, '$1 ').trim();
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
    
    // Lazy loading - se inicializa cuando se activa el tab
    window.initClientesTab = function() {
        if (!window._clientesInitialized) {
            window._clientesInitialized = true;
            init();
        }
    };
    
    // Búsqueda desde header
    window.filterClientes = function(query) {
        if (!window._clientesInitialized) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadClientes = () => loadData();
    
    window.copyClienteEmail = function(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const icon = btn.querySelector('i');
            icon.className = 'ki-outline ki-check';
            btn.classList.remove('btn-light-success');
            btn.classList.add('btn-success');
            setTimeout(() => {
                icon.className = 'ki-outline ki-copy';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-light-success');
            }, 1500);
        });
    };
})();
</script>