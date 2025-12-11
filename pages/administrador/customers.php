<?php $scripts = []; ?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="customers-results-count">Cargando...</span>
            </div>
            <div class="pagination-size">
                <label for="customers-page-size">Mostrar:</label>
                <select id="customers-page-size" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
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
        <div class="pagination-container" id="customers-pagination" style="display: none;">
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

<!-- Modal de confirmación eliminar -->
<div class="modal fade" id="modal-confirm-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-400px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-8">
                <div class="mb-4">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="w-60px h-60px rounded-circle bg-light-danger d-flex align-items-center justify-content-center">
                            <i class="ki-outline ki-trash fs-2x text-danger"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-gray-900 mb-2">¿Eliminar cliente?</h3>
                    <p class="text-gray-600 mb-0">Se eliminarán todos sus servicios y datos asociados.</p>
                </div>
                <div class="bg-light rounded p-3 mb-5" id="delete-modal-info" style="display: none;">
                    <span class="fw-semibold text-gray-800" id="delete-modal-item-name"></span>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                        <i class="ki-outline ki-trash me-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/customers-paginated-admin';
    
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
    let deleteModalConfig = { id: null, name: null };
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        
        document.getElementById('confirm-delete-btn').addEventListener('click', confirmDelete);
        
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
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-profile-user"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay clientes'}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery 
                            ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` 
                            : 'Los clientes registrados aparecerán aquí'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(cus => {
            const fullName = ((cus.name || '') + ' ' + (cus.lastname || '')).trim() || 'Sin nombre';
            const roleName = ROLES[cus.role_name] || cus.role_name || '';
            const hasPhone = cus.phone && cus.phone.trim() !== '';
            
            return `
                <div class="list-card list-card-primary">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${cus.id}</span>
                            <a href="/customer?id=${cus.id}" class="list-card-customer">
                                ${escapeHtml(fullName)}
                            </a>
                            <span class="badge-status badge-status-info">${escapeHtml(roleName)}</span>
                            ${cus.is_active
                                ? '<span class="badge badge-light-success">Activo</span>'
                                : '<span class="badge badge-light-warning">Pendiente</span>'}
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                <a href="mailto:${escapeHtml(cus.email)}" class="text-muted">${escapeHtml(cus.email)}</a>
                            </span>
                            ${hasPhone ? `
                                <span>
                                    <i class="ki-outline ki-phone"></i>
                                    <a href="tel:${cus.phone}" class="text-muted">${formatPhone(cus.phone)}</a>
                                </span>
                            ` : ''}
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${formatDate(cus.created_at)}
                            </span>
                            <span>
                                <i class="ki-outline ki-folder"></i>
                                ${cus.services_number || 0} servicio${cus.services_number !== 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${cus.id}" class="btn-icon" title="Ver perfil">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <button type="button" 
                                class="btn-icon btn-icon-danger" 
                                title="Eliminar"
                                onclick="deleteCustomer(${cus.id}, '${escapeHtml(fullName).replace(/'/g, "\\'")}')">
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
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
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando clientes...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-disconnect text-danger"></i>
                </div>
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
    
    // === Modal eliminar ===
    window.deleteCustomer = function(id, name) {
        deleteModalConfig = { id, name };
        document.getElementById('delete-modal-item-name').textContent = name;
        document.getElementById('delete-modal-info').style.display = 'block';
        new bootstrap.Modal(document.getElementById('modal-confirm-delete')).show();
    };
    
    async function confirmDelete() {
        const btn = document.getElementById('confirm-delete-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';
        
        try {
            const response = await fetch(`/api/customer/${deleteModalConfig.id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (result.status === 'ok') {
                bootstrap.Modal.getInstance(document.getElementById('modal-confirm-delete')).hide();
                loadData();
                
                // Toast de confirmación
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cliente eliminado',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                throw new Error(result.message || 'Error al eliminar');
            }
        } catch (error) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ki-outline ki-trash me-1"></i>Eliminar';
        }
    }
    
    // === Funciones globales ===
    window.filterCustomers = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCustomers = () => loadData();
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>