<?php
/**
 * users.php - Listado de Comerciales y Colaboradores (Admin)
 * Sistema unificado list-card
 * 
 * TYPE viene definido del controlador:
 *   - "sales-rep" = comerciales (rol 7)
 *   - "provider" = colaboradores (rol 2)
 */

$scripts = [];

$type_label = (TYPE === "sales-rep") ? "comerciales" : "colaboradores";
$type_singular = (TYPE === "sales-rep") ? "comercial" : "colaborador";
$card_variant = (TYPE === "sales-rep") ? "info" : "warning";
?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="users-results-count">Cargando...</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="pagination-size">
                    <label for="users-page-size">Mostrar:</label>
                    <select id="users-page-size" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-user">
                    <i class="ki-outline ki-plus me-1"></i>Añadir <?= $type_singular ?>
                </button>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="users-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando <?= $type_label ?>...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="users-pagination" style="display: none;">
            <div class="pagination-info" id="users-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="users-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="users-page-current">1 / 1</span>
                <button class="btn-pagination" id="users-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<!-- Modal Añadir Usuario -->
<div class="modal fade" id="modal-add-user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir <?= $type_singular ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="form-add-user">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Nombre</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Apellidos</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <?php if (TYPE === "sales-rep") : ?>
                        <div class="col-12">
                            <label class="form-label required">Código comercial</label>
                            <input type="text" class="form-control" name="code" required maxlength="10" placeholder="Ej: COM001">
                            <div class="form-text">Código único para identificar al comercial</div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                <small><i class="ki-outline ki-information-5 me-1"></i>El usuario recibirá un email para establecer su contraseña</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-add">
                        <i class="ki-outline ki-check me-1"></i>Crear <?= $type_singular ?>
                    </button>
                </div>
            </form>
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
                    <h3 class="fw-bold text-gray-900 mb-2">¿Eliminar <?= $type_singular ?>?</h3>
                    <p class="text-gray-600 mb-0">Se eliminará el usuario y perderá el acceso al sistema.</p>
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
    
    const CONFIG = {
        userType: '<?= TYPE ?>',
        typeLabel: '<?= $type_label ?>',
        typeSingular: '<?= $type_singular ?>',
        cardVariant: '<?= $card_variant ?>',
        apiList: '/api/users-paginated',
        apiAdd: '/api/users-add',
        apiDelete: '/api/users-delete'
    };
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('users-list');
    const resultsCount = document.getElementById('users-results-count');
    const pageInfo = document.getElementById('users-page-info');
    const pageCurrent = document.getElementById('users-page-current');
    const prevBtn = document.getElementById('users-prev');
    const nextBtn = document.getElementById('users-next');
    const pageSizeSelect = document.getElementById('users-page-size');
    const paginationContainer = document.getElementById('users-pagination');
    
    let searchTimeout = null;
    let deleteModalConfig = { id: null, name: null };
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        
        document.getElementById('form-add-user').addEventListener('submit', handleAddUser);
        document.getElementById('confirm-delete-btn').addEventListener('click', confirmDelete);
        
        loadData();
    }
    
    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            const params = new URLSearchParams({
                type: CONFIG.userType,
                page: state.currentPage,
                limit: state.pageSize,
                search: state.searchQuery
            });
            
            const response = await fetch(`${CONFIG.apiList}?${params}`);
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
            const icon = CONFIG.userType === 'provider' ? 'ki-user-tick' : 'ki-briefcase';
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ki-outline ${icon}"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay ' + CONFIG.typeLabel}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery 
                            ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` 
                            : 'Los ' + CONFIG.typeLabel + ' registrados aparecerán aquí'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(user => {
            const fullName = ((user.name || '') + ' ' + (user.lastname || '')).trim() || 'Sin nombre';
            const hasPhone = user.phone && user.phone.trim() !== '';
            
            return `
                <div class="list-card list-card-${CONFIG.cardVariant}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${user.id}</span>
                            <a href="/user?id=${user.id}" class="list-card-customer">
                                ${escapeHtml(fullName)}
                            </a>
                            ${user.is_active
                                ? '<span class="badge badge-light-success">Activo</span>'
                                : '<span class="badge badge-light-warning">Pendiente</span>'}
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                <a href="mailto:${escapeHtml(user.email)}" class="text-muted">${escapeHtml(user.email)}</a>
                            </span>
                            ${hasPhone ? `
                                <span>
                                    <i class="ki-outline ki-phone"></i>
                                    <a href="tel:${user.phone}" class="text-muted">${formatPhone(user.phone)}</a>
                                </span>
                            ` : ''}
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${formatDate(user.created_at)}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/user?id=${user.id}" class="btn-icon btn-icon-info" title="Ver perfil">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <button type="button" 
                                class="btn-icon btn-icon-danger" 
                                title="Eliminar"
                                onclick="deleteUser(${user.id}, '${escapeHtml(fullName).replace(/'/g, "\\'")}')">
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    // === Añadir usuario ===
    async function handleAddUser(e) {
        e.preventDefault();
        
        const form = e.target;
        const btn = document.getElementById('btn-submit-add');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';
        
        try {
            const formData = new FormData(form);
            formData.append('type', CONFIG.userType);
            
            const response = await fetch(CONFIG.apiAdd, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'ok') {
                bootstrap.Modal.getInstance(document.getElementById('modal-add-user')).hide();
                form.reset();
                state.currentPage = 1;
                loadData();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario creado',
                        text: result.message || 'Se ha enviado un email de activación',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'No se pudo crear el usuario',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            }
        } catch (error) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
    
    // === Modal eliminar ===
    window.deleteUser = function(id, name) {
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
            const formData = new FormData();
            formData.append('user_id', deleteModalConfig.id);
            
            const response = await fetch(CONFIG.apiDelete, { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.status === 'ok') {
                bootstrap.Modal.getInstance(document.getElementById('modal-confirm-delete')).hide();
                loadData();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario eliminado',
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
    
    // === Utilidades ===
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
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2">Cargando ${CONFIG.typeLabel}...</span>
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadUsers()">
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
    
    // === Funciones globales ===
    window.filterUsers = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadUsers = () => loadData();
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>