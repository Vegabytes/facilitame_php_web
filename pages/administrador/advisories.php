<?php
/**
 * Listado de Asesorías - Panel Admin
 * /pages/administrador/advisories.php
 */
$scripts = ["bold-submit"];
?>

<div class="advisories-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!--begin::KPIs-->
    <div class="row g-3 mb-3">
        
        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-primary">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-chart fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Total</div>
                        <div class="kpi-value" id="kpi-total"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Asesorías registradas</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-success">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-check-circle fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Activas</div>
                        <div class="kpi-value" id="kpi-activas"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">En funcionamiento</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-warning">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-time fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Pendientes</div>
                        <div class="kpi-value" id="kpi-pendientes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Sin activar</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card kpi-card-info">
                <div class="kpi-card-content">
                    <div class="kpi-icon">
                        <i class="ki-duotone ki-people fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Clientes</div>
                        <div class="kpi-value" id="kpi-clientes"><span class="skeleton-loader"></span></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span class="kpi-footer-text">Total gestionados</span>
                </div>
            </div>
        </div>

    </div>
    <!--end::KPIs-->
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="advisories-results-count">Cargando...</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="pagination-size">
                    <label for="advisories-page-size">Mostrar:</label>
                    <select id="advisories-page-size" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btnNewAdvisory">
                    <i class="ki-outline ki-plus me-1"></i>
                    Nueva Asesoría
                </button>
            </div>
        </div>
        
        <!-- Filtros inline -->
        <div class="list-filters">
            <div class="filter-search">
                <i class="ki-outline ki-magnifier"></i>
                <input type="text" class="form-control form-control-sm" id="searchAdvisories" placeholder="Buscar por nombre, CIF, email...">
            </div>
            <div class="filter-select">
                <select class="form-select form-select-sm" id="filterStatus">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="activo">Activo</option>
                    <option value="suspendido">Suspendido</option>
                </select>
            </div>
            <div class="filter-select">
                <select class="form-select form-select-sm" id="filterPlan">
                    <option value="">Todos los planes</option>
                    <option value="gratuito">Gratuito</option>
                    <option value="basic">Basic</option>
                    <option value="estandar">Estándar</option>
                    <option value="pro">Pro</option>
                    <option value="premium">Premium</option>
                </select>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="advisories-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando asesorías...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="advisories-pagination" style="display: none;">
            <div class="pagination-info" id="advisories-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="advisories-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="advisories-page-current">1 / 1</span>
                <button class="btn-pagination" id="advisories-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/advisories-paginated-admin';
    
    const STATUS_CONFIG = {
        'pendiente': { label: 'Pendiente', class: 'badge-status-warning' },
        'activo': { label: 'Activo', class: 'badge-status-success' },
        'suspendido': { label: 'Suspendido', class: 'badge-status-danger' }
    };

    const PLAN_CONFIG = {
        'gratuito': { label: 'Gratuito', class: 'badge-status-secondary' },
        'basic': { label: 'Basic', class: 'badge-status-info' },
        'estandar': { label: 'Estándar', class: 'badge-status-primary' },
        'pro': { label: 'Pro', class: 'badge-status-success' },
        'premium': { label: 'Premium', class: 'badge-status-warning' }
    };
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        statusFilter: '',
        planFilter: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('advisories-list');
    const resultsCount = document.getElementById('advisories-results-count');
    const pageInfo = document.getElementById('advisories-page-info');
    const pageCurrent = document.getElementById('advisories-page-current');
    const prevBtn = document.getElementById('advisories-prev');
    const nextBtn = document.getElementById('advisories-next');
    const pageSizeSelect = document.getElementById('advisories-page-size');
    const paginationContainer = document.getElementById('advisories-pagination');
    const searchInput = document.getElementById('searchAdvisories');
    const filterStatus = document.getElementById('filterStatus');
    const filterPlan = document.getElementById('filterPlan');
    const btnNewAdvisory = document.getElementById('btnNewAdvisory');
    const headerSearch = document.getElementById('header-search-input');
    
    let searchTimeout = null;
    
    function init() {
        if (prevBtn) prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        if (pageSizeSelect) pageSizeSelect.addEventListener('change', handlePageSizeChange);
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.searchQuery = this.value.trim();
                    state.currentPage = 1;
                    loadData();
                }, 300);
            });
        }
        
        if (filterStatus) {
            filterStatus.addEventListener('change', function() {
                state.statusFilter = this.value;
                state.currentPage = 1;
                loadData();
            });
        }
        
        if (filterPlan) {
            filterPlan.addEventListener('change', function() {
                state.planFilter = this.value;
                state.currentPage = 1;
                loadData();
            });
        }
        
        // Conectar buscador del header con el de la página
        if (headerSearch) {
            headerSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (searchInput) searchInput.value = this.value;
                    state.searchQuery = this.value;
                    state.currentPage = 1;
                    loadData();
                }, 300);
            });
        }

        if (btnNewAdvisory) {
            btnNewAdvisory.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('modal-new-advisory'));
                modal.show();
            });
        }
        
        loadData();
    }
    
    function showNotAvailable(action) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'No disponible',
                text: `La función "${action}" no está disponible todavía.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(`La función "${action}" no está disponible todavía.`);
        }
    }
    
    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.pageSize
            });
            
            if (state.searchQuery) params.append('search', state.searchQuery);
            if (state.statusFilter) params.append('status', state.statusFilter);
            if (state.planFilter) params.append('plan', state.planFilter);
            
            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                const { data: items, pagination, kpis } = result.data;
                state.totalPages = pagination.total_pages;
                state.totalRecords = pagination.total_records;
                
                renderList(items || []);
                updateResultsCount(pagination);
                updatePaginationControls();
                updateKPIs(kpis);
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
                        <i class="ki-outline ki-chart"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay asesorías'}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery 
                            ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` 
                            : 'Las asesorías registradas aparecerán aquí'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(adv => {
            const status = STATUS_CONFIG[adv.estado] || null;
            const plan = PLAN_CONFIG[adv.plan] || PLAN_CONFIG['gratuito'];

            return `
                <div class="list-card list-card-primary">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${adv.id}</span>
                            <a href="/advisory?id=${adv.id}" class="list-card-customer">
                                ${escapeHtml(adv.razon_social || 'Sin nombre')}
                            </a>
                            ${status ? `<span class="badge-status ${status.class}">${status.label}</span>` : ''}
                            <span class="badge-status ${plan.class}">${plan.label}</span>
                        </div>
                        <div class="list-card-meta">
                            ${adv.cif ? `
                                <span>
                                    <i class="ki-outline ki-document"></i>
                                    ${escapeHtml(adv.cif)}
                                </span>
                            ` : ''}
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                ${escapeHtml(adv.email_empresa || 'Sin email')}
                            </span>
                            ${adv.codigo_identificacion ? `
                                <span>
                                    <i class="ki-outline ki-key"></i>
                                    ${escapeHtml(adv.codigo_identificacion)}
                                </span>
                            ` : ''}
                            <span>
                                <i class="ki-outline ki-people"></i>
                                ${adv.total_customers || 0} cliente${adv.total_customers !== 1 ? 's' : ''}
                            </span>
                            ${adv.pending_appointments > 0 ? `
                                <span class="text-warning">
                                    <i class="ki-outline ki-calendar"></i>
                                    ${adv.pending_appointments} cita${adv.pending_appointments !== 1 ? 's' : ''} pend.
                                </span>
                            ` : ''}
                            ${adv.pending_invoices > 0 ? `
                                <span class="text-info">
                                    <i class="ki-outline ki-bill"></i>
                                    ${adv.pending_invoices} fact. pend.
                                </span>
                            ` : ''}
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${escapeHtml(adv.created_at || '-')}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/advisory?id=${adv.id}" class="btn-icon btn-icon-primary" title="Ver detalle">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <button type="button" 
                                class="btn-icon btn-icon-danger" 
                                title="Eliminar"
                                onclick="deleteAdvisory(${adv.id}, '${escapeHtml(adv.razon_social).replace(/'/g, "\\'")}')">
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function updateKPIs(kpis) {
        if (!kpis) return;
        
        animateValue('kpi-total', kpis.total || 0);
        animateValue('kpi-activas', kpis.activas || 0);
        animateValue('kpi-pendientes', kpis.pendientes || 0);
        animateValue('kpi-clientes', kpis.total_clientes || 0);
    }
    
    function animateValue(id, target) {
        const el = document.getElementById(id);
        if (!el) return;
        
        const duration = 600;
        const start = Date.now();
        
        (function update() {
            const progress = Math.min((Date.now() - start) / duration, 1);
            el.textContent = Math.floor(target * (1 - Math.pow(1 - progress, 3)));
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
        })();
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
        if (pageCurrent) pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        if (pageInfo) pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        if (prevBtn) prevBtn.disabled = state.currentPage <= 1;
        if (nextBtn) nextBtn.disabled = state.currentPage >= state.totalPages;
        if (paginationContainer) paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(pagination) {
        if (resultsCount) {
            resultsCount.innerHTML = pagination.total_records === 0 
                ? 'No hay resultados' 
                : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong>`;
        }
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando asesorías...</span>
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadAdvisories()">
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
    
    window.deleteAdvisory = function(id, name) {
        if (typeof Swal === 'undefined') {
            alert('Error: SweetAlert no disponible');
            return;
        }

        Swal.fire({
            title: '¿Eliminar asesoría?',
            html: `<p>Vas a eliminar la asesoría <strong>${escapeHtml(name)}</strong>.</p>
                   <p class="text-muted small">Esta acción desvinculará los clientes y marcará la asesoría como eliminada.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('id', id);

                    const response = await fetch('/api/advisories-delete', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.status === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminada',
                            text: data.message || 'Asesoría eliminada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadData(); // Recargar lista
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la asesoría'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión al eliminar'
                    });
                }
            }
        });
    };
    
    window.reloadAdvisories = () => loadData();

    // Función global para el buscador del header
    window.filterAdvisories = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (searchInput) searchInput.value = query;
            state.searchQuery = query;
            state.currentPage = 1;
            loadData();
        }, 300);
    };

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<!-- Modal: Nueva Asesoría -->
<div class="modal fade" id="modal-new-advisory" tabindex="-1" aria-labelledby="modal-new-advisory-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-new-advisory-label">
                    <i class="ki-outline ki-chart me-2"></i>
                    Nueva Asesoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="api/advisories-add" data-reload="1" data-modal-close="modal-new-advisory">
                <div class="modal-body">

                    <!-- Datos de la empresa -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">
                            <i class="ki-outline ki-briefcase me-1"></i>
                            Datos de la empresa
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label required">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control" required placeholder="Nombre de la empresa">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">CIF</label>
                                <input type="text" name="cif" class="form-control" required placeholder="B12345678" maxlength="9" style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Email Empresa</label>
                                <input type="email" name="email_empresa" class="form-control" required placeholder="contacto@empresa.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" placeholder="600 000 000">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control" placeholder="Calle, número, ciudad...">
                            </div>
                        </div>
                    </div>

                    <!-- Plan y estado -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">
                            <i class="ki-outline ki-setting-2 me-1"></i>
                            Configuración
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Plan</label>
                                <select name="plan" class="form-select">
                                    <option value="gratuito">Gratuito</option>
                                    <option value="basic">Basic</option>
                                    <option value="estandar" selected>Estándar</option>
                                    <option value="pro">Pro</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado inicial</label>
                                <select name="estado" class="form-select">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="activo" selected>Activo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Usuario asociado (opcional) -->
                    <div class="mb-0">
                        <h6 class="text-muted mb-3">
                            <i class="ki-outline ki-user me-1"></i>
                            Usuario asociado <span class="text-muted fw-normal">(opcional)</span>
                        </h6>
                        <div class="alert alert-light-info mb-3">
                            <i class="ki-outline ki-information-2 me-2"></i>
                            Si proporcionas un email de usuario, se creará una cuenta y se enviará un email de activación.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del usuario</label>
                                <input type="text" name="user_name" class="form-control" placeholder="Nombre completo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email del usuario</label>
                                <input type="email" name="user_email" class="form-control" placeholder="usuario@empresa.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono del usuario</label>
                                <input type="text" name="user_phone" class="form-control" placeholder="600 000 000">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary bold-submit">
                        <i class="ki-outline ki-check me-1"></i>
                        Crear Asesoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>