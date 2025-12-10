<?php 
$scripts = []; 

// Obtener código del comercial
global $pdo;
$stmtCode = $pdo->prepare("SELECT code FROM sales_codes WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmtCode->execute([USER['id']]);
$salesCode = $stmtCode->fetchColumn() ?: 'N/A';
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content">
        <div class="row gx-4" style="height: calc(100vh - 160px);">
            
            <!-- Columna principal - Lista de clientes -->
            <div class="col-xl-8">
                <div class="card" style="height: 100%; display: flex; flex-direction: column;">
                    
                    <!-- Controles -->
                    <div class="list-controls" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border);">
                        <div class="results-info">
                            <span id="customers-results-count">Cargando...</span>
                        </div>
                        <div class="pagination-size">
                            <label for="customers-page-size">Mostrar:</label>
                            <select id="customers-page-size" class="form-select form-select-sm">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
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
            
            <!-- Columna lateral - Invitación -->
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-outline ki-send me-2 text-primary"></i>
                            Invitar cliente
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Código de invitación -->
                        <div class="mb-4">
                            <label class="form-label text-muted fs-7">Tu código</label>
                            <div class="invite-code-compact"><?php secho($salesCode) ?></div>
                        </div>
                        
                        <div class="separator separator-dashed my-4"></div>
                        
                        <!-- Enviar por email -->
                        <div class="mb-3">
                            <label class="form-label text-muted fs-7">Enviar invitación por email</label>
                            <input type="email" id="invite-email" class="form-control form-control-sm" placeholder="cliente@email.com">
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-primary w-100" id="btn-send-invite-sales">
                            <i class="ki-outline ki-send me-1"></i>
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/customers-paginated-sales';
    
    const state = {
        currentPage: 1,
        pageSize: 10,
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
        
        // Evento enviar invitación
        document.getElementById('btn-send-invite-sales')?.addEventListener('click', sendInvite);
        
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
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${c.id}" class="btn-icon" title="Ver perfil">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <button type="button" class="btn-icon btn-icon-success" title="Copiar email" onclick="copyEmail('${escapeHtml(c.email)}', this)">
                            <i class="ki-outline ki-copy"></i>
                        </button>
                        ${hasPhone ? `
                            <a href="tel:${c.phone}" class="btn-icon btn-icon-primary" title="Llamar">
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
    
    async function sendInvite() {
        const emailInput = document.getElementById('invite-email');
        const btn = document.getElementById('btn-send-invite-sales');
        const email = emailInput.value.trim();
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailInput.classList.add('is-invalid');
            return;
        }
        
        emailInput.classList.remove('is-invalid');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
        
        try {
            const formData = new FormData();
            formData.append('to', email);
            
            const response = await fetch('/api/invite-send', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'ok') {
                emailInput.value = '';
                btn.innerHTML = '<i class="ki-outline ki-check me-1"></i>¡Enviado!';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="ki-outline ki-send me-1"></i>Enviar';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                }, 2000);
            } else {
                throw new Error(result.message || 'Error al enviar');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo enviar la invitación',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary' }
            });
            btn.innerHTML = '<i class="ki-outline ki-send me-1"></i>Enviar';
        } finally {
            btn.disabled = false;
        }
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
    
    // Funciones globales
    window.filterCustomers = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCustomers = () => loadData();
    
    window.copyEmail = function(text, btn) {
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
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>