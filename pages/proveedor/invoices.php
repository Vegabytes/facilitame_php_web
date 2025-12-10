<?php $scripts = []; ?>

<div class="invoices-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column; padding: 1.5rem;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border); border-radius: var(--f-radius) var(--f-radius) 0 0;">
            <div class="results-info">
                <span id="invoices-results-count">Cargando...</span>
            </div>
            <div class="pagination-size">
                <label for="invoices-page-size">Mostrar:</label>
                <select id="invoices-page-size" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="invoices-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando facturas...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="invoices-pagination" style="flex-shrink: 0; display: none;">
            <div class="pagination-info" id="invoices-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="invoices-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="invoices-page-current">1 / 1</span>
                <button class="btn-pagination" id="invoices-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<!-- Modal de carga -->
<div class="modal fade" tabindex="-1" id="modal-factura-cargar">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Cargar factura</h3>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="api/invoice-upload" id="form-upload-invoice">
                <input type="hidden" name="request_id" id="request-id-to-upload">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Archivo</label>
                        <input type="file" name="invoice_file" class="form-control" accept="image/*,application/pdf,.docx" required>
                        <small class="text-muted">PDF, imágenes, DOCX</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Fecha</label>
                        <input type="date" class="form-control" name="invoice_date" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ki-outline ki-cloud-upload me-1"></i> Cargar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/invoices-paginated-provider';
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('invoices-list');
    const resultsCount = document.getElementById('invoices-results-count');
    const pageInfo = document.getElementById('invoices-page-info');
    const pageCurrent = document.getElementById('invoices-page-current');
    const prevBtn = document.getElementById('invoices-prev');
    const nextBtn = document.getElementById('invoices-next');
    const pageSizeSelect = document.getElementById('invoices-page-size');
    const paginationContainer = document.getElementById('invoices-pagination');
    
    let searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        
        listContainer.addEventListener('click', handleListClick);
        document.getElementById('form-upload-invoice').addEventListener('submit', handleUpload);
        
        loadData();
    }
    
    function handleListClick(e) {
        // Toggle tabla de servicios
        if (e.target.closest('.toggle-requests-btn')) {
            const btn = e.target.closest('.toggle-requests-btn');
            const card = btn.closest('.invoice-card-wrapper');
            const table = card.querySelector('.requests-table-wrapper');
            const isVisible = table.style.display !== 'none';
            
            table.style.display = isVisible ? 'none' : 'block';
            btn.querySelector('.btn-text').textContent = isVisible ? 'Ver servicios' : 'Ocultar';
            btn.querySelector('.arrow-icon').style.transform = isVisible ? '' : 'rotate(180deg)';
        }
        
        // Abrir modal con request_id
        if (e.target.closest('.btn-invoice-upload')) {
            const requestId = e.target.closest('.btn-invoice-upload').dataset.requestId;
            document.getElementById('request-id-to-upload').value = requestId;
        }
    }
    
    async function handleUpload(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, { method: 'POST', body: formData });
            const result = JSON.parse(await response.text());
            
            if (result.status === 'ok') {
                Swal.fire({ icon: 'success', html: result.message_html, timer: 3000, showConfirmButton: false });
                bootstrap.Modal.getInstance(document.getElementById('modal-factura-cargar')).hide();
                form.reset();
                loadData();
            } else {
                Swal.fire({ icon: 'warning', html: result.message_html });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', html: 'Error al cargar la factura' });
        }
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
                    <div class="empty-state-icon"><i class="ki-outline ki-document"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay facturas pendientes'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` : 'No hay servicios activos que requieran carga de facturas'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(customer => {
            const isVerified = customer.verified === "1";
            const borderClass = isVerified ? 'list-card-success' : 'list-card-warning';
            const verifiedBadge = isVerified
                ? '<span class="badge-status badge-status-success">Verificado</span>'
                : '<span class="badge-status badge-status-warning">Pendiente</span>';
            
            const requestsRows = customer.requests.map(req => {
                const isPending = req.verified !== "1";
                const lastInvoice = req.last_invoice === '1970-01-01' 
                    ? '<span class="text-muted">Sin facturas</span>'
                    : formatDate(req.last_invoice);
                
                const uploadBtn = customer.allow_invoice_access === "1"
                    ? `<button class="btn btn-sm btn-success btn-invoice-upload" data-request-id="${req.id}" data-bs-toggle="modal" data-bs-target="#modal-factura-cargar">
                         <i class="ki-outline ki-cloud-upload me-1"></i> Cargar
                       </button>`
                    : '<span class="badge-status badge-status-danger">Sin consentimiento</span>';
                
                return `
                    <tr class="${isPending ? 'table-warning' : ''}">
                        <td><span class="badge-status badge-status-primary">#${req.id}</span></td>
                        <td class="fw-semibold">${escapeHtml(req.category_name)}</td>
                        <td>${getStatusBadge(req.status_name)}</td>
                        <td>${lastInvoice}</td>
                        <td class="text-end">${uploadBtn}</td>
                    </tr>`;
            }).join('');
            
            return `
                <div class="invoice-card-wrapper" style="margin-bottom: 0.75rem;">
                    <div class="list-card ${borderClass}">
                        <div class="list-card-content">
                            <div class="list-card-title">
                                <a href="/customer?id=${customer.id}" class="list-card-customer">
                                    ${escapeHtml((customer.name || '') + ' ' + (customer.lastname || ''))}
                                </a>
                                ${verifiedBadge}
                            </div>
                            <div class="list-card-meta">
                                <span>
                                    <i class="ki-outline ki-sms"></i>
                                    <a href="mailto:${escapeHtml(customer.email)}" class="text-muted">${escapeHtml(customer.email)}</a>
                                </span>
                                <span><i class="ki-outline ki-folder"></i> ${customer.total_requests} servicio${customer.total_requests !== 1 ? 's' : ''}</span>
                                ${customer.pending_requests > 0 ? `
                                    <span class="text-warning fw-semibold">
                                        <i class="ki-outline ki-time"></i> ${customer.pending_requests} pendiente${customer.pending_requests !== 1 ? 's' : ''}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="list-card-actions">
                            <button type="button" class="btn btn-sm btn-light-primary toggle-requests-btn">
                                <span class="btn-text">Ver servicios</span>
                                <i class="ki-outline ki-down fs-5 ms-1 arrow-icon" style="transition: transform 0.3s"></i>
                            </button>
                        </div>
                    </div>
                    <div class="requests-table-wrapper" style="display: none; background: var(--f-bg-light); border: 1px solid var(--f-border); border-top: 0; border-radius: 0 0 var(--f-radius) var(--f-radius); overflow: hidden;">
                        <table class="table table-sm align-middle mb-0" style="font-size: 0.8125rem;">
                            <thead>
                                <tr class="text-muted" style="background: rgba(0,0,0,0.02);">
                                    <th style="padding: 0.5rem 1rem;">ID</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Última factura</th>
                                    <th class="text-end" style="padding-right: 1rem;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>${requestsRows}</tbody>
                        </table>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function getStatusBadge(status) {
        const map = {
            'activa': 'success', 'activada': 'success', 
            'pendiente': 'warning', 'iniciado': 'primary',
            'en curso': 'info', 'en_progreso': 'info',
            'cancelada': 'danger', 'rechazada': 'danger'
        };
        const cls = map[status?.toLowerCase()] || 'muted';
        return `<span class="badge-status badge-status-${cls}">${escapeHtml(status || 'N/A')}</span>`;
    }
    
    function formatDate(dateStr) {
        if (!dateStr || dateStr === '1970-01-01') return 'Sin facturas';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadInvoices()">
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
    window.filterInvoices = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadInvoices = () => loadData();
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>