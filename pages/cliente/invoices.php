<?php $scripts = []; ?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
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
        <div class="pagination-container" id="invoices-pagination" style="display: none;">
            <div class="pagination-info" id="invoices-page-info">Pagina 1 de 1</div>
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

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/invoices-paginated-customer';
    
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
        loadData();
    }
    
    function handleListClick(e) {
        if (e.target.closest('.toggle-invoices-btn')) {
            const btn = e.target.closest('.toggle-invoices-btn');
            const card = btn.closest('.invoice-card-wrapper');
            const table = card.querySelector('.invoices-table-wrapper');
            const isVisible = table.style.display !== 'none';
            
            table.style.display = isVisible ? 'none' : 'block';
            btn.querySelector('.btn-text').textContent = isVisible ? 'Ver facturas' : 'Ocultar';
            btn.querySelector('.arrow-icon').style.transform = isVisible ? '' : 'rotate(180deg)';
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
                showError(result.message_plain || result.message || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexion');
        } finally {
            state.isLoading = false;
        }
    }
    
    function renderList(data) {
        if (!data.length) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-document"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay facturas'}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery 
                            ? `No se encontraron resultados para "${escapeHtml(state.searchQuery)}"` 
                            : 'Las facturas de tus servicios apareceran aqui'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(req => {
            const hasInvoices = req.total_invoices > 0;
            const isVerified = req.verified === "1";
            const borderClass = isVerified ? 'list-card-success' : 'list-card-warning';
            
            const verifiedBadge = isVerified
                ? '<span class="badge-status badge-status-success">Al dia</span>'
                : '<span class="badge-status badge-status-warning">Pendiente este mes</span>';
            
            const invoicesBadge = hasInvoices
                ? `<span class="badge-status badge-status-muted">${req.total_invoices} factura${req.total_invoices > 1 ? 's' : ''}</span>`
                : '';
            
            let invoicesRows = '';
            if (req.invoices && req.invoices.length > 0) {
                invoicesRows = req.invoices.map(inv => {
                    const fileExt = inv.filename ? inv.filename.split('.').pop().toLowerCase() : '';
                    const isPdf = fileExt === 'pdf';
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
                    const iconClass = isPdf ? 'ki-document' : (isImage ? 'ki-picture' : 'ki-file');
                    
                    return `
                        <tr>
                            <td style="padding: 0.5rem 1rem;">
                                <span class="text-muted">
                                    <i class="ki-outline ${iconClass} me-1"></i>
                                    ${inv.invoice_date_formatted}
                                </span>
                            </td>
                            <td class="fw-medium">${escapeHtml(inv.description || 'Sin descripci�n')}</td>
                            <td class="text-end" style="padding-right: 1rem;">
                                <a href="/uploads/invoices/${encodeURIComponent(inv.filename)}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-light-primary"
                                   title="Descargar factura">
                                    <i class="ki-outline ki-cloud-download me-1"></i> Descargar
                                </a>
                            </td>
                        </tr>`;
                }).join('');
            } else {
                invoicesRows = `
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="ki-outline ki-document fs-2 d-block mb-2 opacity-50"></i>
                            No hay facturas disponibles para este servicio
                        </td>
                    </tr>`;
            }
            
            return `
                <div class="invoice-card-wrapper" style="margin-bottom: 0.75rem;">
                    <div class="list-card ${borderClass}">
                        <div class="list-card-content">
                            <div class="list-card-title">
                                <a href="/request?id=${req.id}" class="list-card-customer">
                                    ${escapeHtml(req.category_name)}
                                </a>
                                <span class="badge-status badge-status-primary">#${req.id}</span>
                                ${verifiedBadge}
                                ${invoicesBadge}
                            </div>
                            <div class="list-card-meta">
                                ${req.details ? `
                                    <span>
                                        <i class="ki-outline ki-information"></i>
                                        ${escapeHtml(req.details)}
                                    </span>
                                ` : ''}
                                ${req.last_invoice_formatted ? `
                                    <span>
                                        <i class="ki-outline ki-calendar"></i>
                                        �ltima: ${req.last_invoice_formatted}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="list-card-actions">
                            <button type="button" class="btn btn-sm btn-light-primary toggle-invoices-btn" ${!hasInvoices ? 'disabled' : ''}>
                                <span class="btn-text">Ver facturas</span>
                                <i class="ki-outline ki-down fs-5 ms-1 arrow-icon" style="transition: transform 0.3s"></i>
                            </button>
                        </div>
                    </div>
                    <div class="invoices-table-wrapper" style="display: none; background: var(--f-bg-light); border: 1px solid var(--f-border); border-top: 0; border-radius: 0 0 var(--f-radius) var(--f-radius); overflow: hidden;">
                        <table class="table table-sm align-middle mb-0" style="font-size: 0.8125rem;">
                            <thead>
                                <tr class="text-muted" style="background: rgba(0,0,0,0.02);">
                                    <th style="padding: 0.5rem 1rem; width: 140px;">Fecha</th>
                                    <th>Descripci�n</th>
                                    <th class="text-end" style="padding-right: 1rem; width: 140px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>${invoicesRows}</tbody>
                        </table>
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
        pageInfo.textContent = `Pagina ${state.currentPage} de ${state.totalPages}`;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0 
            ? 'No hay resultados' 
            : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong> servicios`;
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando facturas...</span>
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
    
    window.filterInvoices = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadInvoices = () => loadData();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>