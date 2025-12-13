<?php $scripts = []; ?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border); border-radius: var(--f-radius) var(--f-radius) 0 0;">
            <div class="results-info">
                <span id="customers-results-count">Cargando...</span>
            </div>
            <div class="pagination-size">
                <label for="customers-page-size">Mostrar:</label>
                <select id="customers-page-size" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-light-success" id="btn-export-csv" onclick="exportCustomersCSV()">
                <i class="ki-outline ki-file-down me-1"></i>CSV
            </button>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="customers-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando clientes...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="customers-pagination" style="flex-shrink: 0; display: none;">
            <div class="pagination-info" id="customers-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="customers-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="customers-page-current">1 / 1</span>
                <button class="btn-pagination" id="customers-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/customers-paginated-provider';
    
    const ROLES = {
        'autonomo': 'Autónomo',
        'particular': 'Particular',
        'empresa': 'Empresa'
    };
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('customers-list');
    const resultsCount = document.getElementById('customers-results-count');
    const pageInfo = document.getElementById('customers-page-info');
    const pageCurrent = document.getElementById('customers-page-current');
    const prevBtn = document.getElementById('customers-prev');
    const nextBtn = document.getElementById('customers-next');
    const pageSizeSelect = document.getElementById('customers-page-size');
    const paginationContainer = document.getElementById('customers-pagination');
    
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
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'Los clientes aparecerán aquí'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(cus => {
            const fullName = ((cus.name || '') + ' ' + (cus.lastname || '')).trim();
            const roleName = ROLES[cus.role_name] || cus.role_name || '';
            
            return `
                <div class="list-card list-card-primary">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${cus.id}</span>
                            <a href="/customer?id=${cus.id}" class="list-card-customer">
                                ${escapeHtml(fullName || 'Sin nombre')}
                            </a>
                            <span class="badge-status badge-status-info">${escapeHtml(roleName)}</span>
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                <a href="mailto:${escapeHtml(cus.email)}" class="text-muted">${escapeHtml(cus.email)}</a>
                            </span>
                            ${cus.phone ? `
                                <span>
                                    <i class="ki-outline ki-phone"></i>
                                    <a href="tel:${cus.phone}" class="text-muted">${formatPhone(cus.phone)}</a>
                                </span>
                            ` : ''}
                            <span><i class="ki-outline ki-calendar"></i> ${cus.created_at || '-'}</span>
                            <span><i class="ki-outline ki-folder"></i> ${cus.services_number || 0} servicio${cus.services_number !== 1 ? 's' : ''}</span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${cus.id}" class="btn-icon btn-icon-info" title="Ver perfil">
                            <i class="ki-outline ki-eye"></i>
                        </a>
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadCustomers()">
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
    window.filterCustomers = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCustomers = () => loadData();

    window.exportCustomersCSV = async function() {
        const btn = document.getElementById('btn-export-csv');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const params = new URLSearchParams({ page: 1, limit: 10000, search: state.searchQuery });
            const response = await fetch(`${API_URL}?${params}`);
            const result = await response.json();
            if (result.status === 'ok' && result.data?.data?.length > 0) {
                const items = result.data.data;
                let csv = 'ID;Nombre;Email;Teléfono;NIF/CIF;Tipo;Solicitudes\n';
                items.forEach(c => {
                    csv += [c.id, '"'+(c.name||'')+' '+(c.lastname||'')+'"', '"'+(c.email||'')+'"', '"'+(c.phone||'')+'"', '"'+(c.nif_cif||'')+'"', '"'+(ROLES[c.role_name]||c.role_name||'')+'"', c.requests_count||0].join(';') + '\n';
                });
                const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'mis_clientes_' + new Date().toISOString().slice(0,10) + '.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'CSV exportado', text: items.length + ' registros', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } else {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay clientes para exportar' });
            }
        } catch (e) { console.error(e); }
        finally { btn.disabled = false; btn.innerHTML = originalHtml; }
    };

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>