<?php
$currentPage = 'advisory-commissions';
?>

<style>
/* ============================================
   ADVISORY COMMISSIONS PAGE - COMPACTO
   ============================================ */

.advisory-commissions-page {
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* KPI Row */
.advisory-commissions-page .kpi-row {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.advisory-commissions-page .kpi-card {
    flex: 1;
    background: white;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s ease;
}

.advisory-commissions-page .kpi-card:hover {
    border-color: var(--color-main-facilitame);
    box-shadow: 0 2px 8px rgba(0, 194, 203, 0.1);
}

.advisory-commissions-page .kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.advisory-commissions-page .kpi-icon i { font-size: 1.125rem; }

.advisory-commissions-page .kpi-card-success .kpi-icon { background: rgba(16, 185, 129, 0.1); color: #059669; }
.advisory-commissions-page .kpi-card-warning .kpi-icon { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.advisory-commissions-page .kpi-card-info .kpi-icon { background: rgba(6, 182, 212, 0.1); color: #0891b2; }

.advisory-commissions-page .kpi-value {
    font-size: 1.375rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.advisory-commissions-page .kpi-label {
    font-size: 0.6875rem;
    color: #64748b;
    margin-top: 0.0625rem;
}

/* Card principal */
.advisory-commissions-page .commissions-card {
    background: white;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.advisory-commissions-page .commissions-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    gap: 0.75rem;
}

.advisory-commissions-page .header-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.advisory-commissions-page .header-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.advisory-commissions-page .header-title i {
    color: var(--color-main-facilitame);
    font-size: 1.125rem;
}

.advisory-commissions-page .results-info {
    font-size: 0.75rem;
    color: #64748b;
}

.advisory-commissions-page .results-info strong {
    color: var(--color-main-facilitame);
    font-weight: 600;
}

.advisory-commissions-page .header-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Search Input */
.advisory-commissions-page .search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.advisory-commissions-page .search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8125rem;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
    transition: color 0.2s ease;
}

.advisory-commissions-page .search-input {
    width: 140px;
    padding-left: 30px !important;
    padding-right: 8px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.75rem;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.advisory-commissions-page .search-input::placeholder { color: #94a3b8; }

.advisory-commissions-page .search-input:focus {
    background: white;
    border-color: var(--color-main-facilitame) !important;
    box-shadow: 0 0 0 2px rgba(0, 194, 203, 0.1) !important;
    outline: none;
}

.advisory-commissions-page .search-input-wrapper:focus-within .search-icon {
    color: var(--color-main-facilitame);
}

.advisory-commissions-page .form-select-compact {
    height: 32px;
    font-size: 0.75rem;
    padding: 0.25rem 1.75rem 0.25rem 0.625rem;
    border-radius: 6px;
}

.advisory-commissions-page .form-select:focus {
    border-color: var(--color-main-facilitame) !important;
    box-shadow: 0 0 0 2px rgba(0, 194, 203, 0.1) !important;
}

/* Body */
.advisory-commissions-page .commissions-card-body {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 280px);
    min-height: 320px;
    max-height: 600px;
}

.advisory-commissions-page .commissions-scroll-container {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem 1.25rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 194, 203, 0.2) transparent;
}

/* List Cards - Una línea horizontal */
.advisory-commissions-page .list-card {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.4375rem 0.75rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left: 2px solid #10b981;
    border-radius: 6px;
    transition: all 0.2s ease;
    margin-top: 0.3125rem;
}

.advisory-commissions-page .list-card:first-child { margin-top: 0; }

.advisory-commissions-page .list-card:hover {
    border-color: var(--color-main-facilitame);
    border-left-color: #10b981;
    box-shadow: 0 2px 8px rgba(0, 194, 203, 0.1);
}

/* Cliente nombre */
.advisory-commissions-page .customer-name {
    width: 140px;
    flex-shrink: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Amount badge */
.advisory-commissions-page .amount-badge {
    padding: 0.1875rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border-radius: 4px;
    flex-shrink: 0;
}

/* Request info */
.advisory-commissions-page .request-info {
    font-size: 0.6875rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-shrink: 0;
    width: 140px;
}

.advisory-commissions-page .request-info i {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Meta */
.advisory-commissions-page .commission-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.advisory-commissions-page .meta-item {
    font-size: 0.625rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 0.125rem;
    white-space: nowrap;
}

.advisory-commissions-page .meta-item i { font-size: 0.6875rem; }

/* Badges */
.advisory-commissions-page .badge-facilitame {
    padding: 0.125rem 0.3125rem;
    font-size: 0.5625rem;
    font-weight: 600;
    border-radius: 3px;
}

.advisory-commissions-page .badge-success-facilitame { background: rgba(16, 185, 129, 0.1); color: #059669; }
.advisory-commissions-page .badge-warning-facilitame { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.advisory-commissions-page .badge-danger-facilitame { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

/* Pagination */
.advisory-commissions-page .pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #f1f5f9;
}

.advisory-commissions-page .pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.3125rem 0.625rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.advisory-commissions-page .pagination-btn:hover:not(:disabled) {
    background: rgba(0, 194, 203, 0.05);
    border-color: var(--color-main-facilitame);
    color: var(--color-main-facilitame);
}

.advisory-commissions-page .pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.advisory-commissions-page .pagination-info { font-size: 0.75rem; color: #64748b; }

/* Empty State */
.advisory-commissions-page .empty-state-modern {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.25rem;
    text-align: center;
}

.advisory-commissions-page .empty-state-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.875rem;
}

.advisory-commissions-page .empty-state-icon i { font-size: 1.5rem; color: var(--color-main-facilitame); }
.advisory-commissions-page .empty-state-title { font-size: 0.875rem; font-weight: 600; color: #1e293b; margin: 0 0 0.25rem 0; }
.advisory-commissions-page .empty-state-text { font-size: 0.75rem; color: #64748b; margin: 0; }

/* Skeletons */
.advisory-commissions-page .skeleton-card {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.4375rem 0.75rem;
    background: #fafbfc;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-top: 0.3125rem;
    animation: skeleton-pulse 1.5s ease-in-out infinite;
}

.advisory-commissions-page .skeleton-card:first-child { margin-top: 0; }

@keyframes skeleton-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.advisory-commissions-page .skeleton-line {
    height: 8px;
    background: linear-gradient(90deg, #e5e7eb 25%, #d1d5db 50%, #e5e7eb 75%);
    background-size: 200% 100%;
    border-radius: 3px;
    animation: skeleton-shimmer 1.5s infinite;
}

@keyframes skeleton-shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.advisory-commissions-page .skeleton-name { width: 100px; height: 10px; }
.advisory-commissions-page .skeleton-amount { width: 50px; height: 16px; border-radius: 4px; }
.advisory-commissions-page .skeleton-info { width: 120px; }
.advisory-commissions-page .skeleton-badge { width: 40px; height: 14px; border-radius: 3px; }

/* Responsive */
@media (max-width: 1199px) {
    .advisory-commissions-page .request-info { width: 110px; }
    .advisory-commissions-page .customer-name { width: 120px; }
}

@media (max-width: 991px) {
    .advisory-commissions-page .list-card { flex-wrap: wrap; padding: 0.5rem 0.75rem; }
    .advisory-commissions-page .commission-meta { order: 10; width: 100%; padding-top: 0.375rem; margin-top: 0.375rem; border-top: 1px solid #f1f5f9; }
    .advisory-commissions-page .header-right { width: 100%; }
    .advisory-commissions-page .header-right > * { flex: 1; }
    .advisory-commissions-page .kpi-row { gap: 0.5rem; }
}

@media (max-width: 767px) {
    .advisory-commissions-page .commissions-card-header { flex-direction: column; align-items: stretch; gap: 0.625rem; }
    .advisory-commissions-page .header-left { width: 100%; justify-content: space-between; }
    .advisory-commissions-page .commissions-card-body { height: calc(100vh - 260px); min-height: 260px; }
    .advisory-commissions-page .commissions-scroll-container { padding: 0.5rem 0.75rem; }
    .advisory-commissions-page .kpi-row { flex-direction: column; gap: 0.375rem; }
    .advisory-commissions-page .request-info { display: none; }
}
</style>

<div class="advisory-commissions-page mt-6">
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content">
            <div class="container-fluid">
                
                <!-- KPIs Compactos -->
                <div class="kpi-row">
                    <div class="kpi-card kpi-card-success">
                        <div class="kpi-icon"><i class="ki-outline ki-check-circle"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-value" id="kpi-active">0</div>
                            <div class="kpi-label">Activas</div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-card-warning">
                        <div class="kpi-icon"><i class="ki-outline ki-time"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-value" id="kpi-pending">0</div>
                            <div class="kpi-label">Pendientes</div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-card-info">
                        <div class="kpi-icon"><i class="ki-outline ki-dollar"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-value" id="kpi-total">0 €</div>
                            <div class="kpi-label">Total</div>
                        </div>
                    </div>
                </div>
                
                <!-- Card único -->
                <div class="commissions-card">
                    <div class="commissions-card-header">
                        <div class="header-left">
                            <h3 class="header-title">
                                <i class="ki-outline ki-dollar"></i>
                                Comisiones
                            </h3>
                            <span class="results-info" id="commissions-results-count"></span>
                        </div>
                        <div class="header-right">
                            <select class="form-select form-select-sm form-select-compact" id="commissions-page-size">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="commissions-card-body">
                        <div class="commissions-scroll-container" id="commissions-scroll-container">
                            <div id="commissions-list"></div>
                        </div>
                        
                        <div class="pagination-controls" id="commissions-pagination">
                            <button class="pagination-btn" id="commissions-prev" disabled>
                                <i class="ki-outline ki-arrow-left"></i> Ant
                            </button>
                            <span class="pagination-info" id="commissions-page-info">1 / 1</span>
                            <button class="pagination-btn" id="commissions-next" disabled>
                                Sig <i class="ki-outline ki-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var API_URL = '/api/advisory-commissions-paginated';
    var state = { currentPage: 1, pageSize: 25, searchQuery: '', totalPages: 1, totalRecords: 0, isLoading: false };
    
    var listContainer = document.getElementById('commissions-list');
    var scrollContainer = document.getElementById('commissions-scroll-container');
    var resultsCount = document.getElementById('commissions-results-count');
    var pageInfo = document.getElementById('commissions-page-info');
    var prevBtn = document.getElementById('commissions-prev');
    var nextBtn = document.getElementById('commissions-next');
    var pageSizeSelect = document.getElementById('commissions-page-size');
    var paginationControls = document.getElementById('commissions-pagination');
    var searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        loadData();
    }
    
    function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        var params = new URLSearchParams({ page: state.currentPage, limit: state.pageSize, search: state.searchQuery });
        
        fetch(API_URL + '?' + params)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    var pagination = result.data.pagination;
                    var kpis = result.data.kpis || {};
                    state.totalPages = pagination.total_pages;
                    state.totalRecords = pagination.total_records;
                    updateKPIs(kpis);
                    renderList(result.data.data || []);
                    updateResultsCount(pagination);
                    updatePaginationControls();
                } else {
                    showError(result.message || 'Error al cargar datos');
                }
            })
            .catch(function(err) { showError('Error de conexión'); })
            .finally(function() { state.isLoading = false; });
    }
    
    function updateKPIs(kpis) {
        document.getElementById('kpi-active').textContent = kpis.active || 0;
        document.getElementById('kpi-pending').textContent = kpis.pending || 0;
        document.getElementById('kpi-total').textContent = formatCurrency(kpis.total || 0);
    }
    
    function renderList(data) {
        if (!data || data.length === 0) {
            listContainer.innerHTML = state.searchQuery
                ? '<div class="empty-state-modern"><div class="empty-state-icon"><i class="ki-outline ki-magnifier"></i></div><h4 class="empty-state-title">Sin resultados</h4><p class="empty-state-text">No se encontraron comisiones</p></div>'
                : '<div class="empty-state-modern"><div class="empty-state-icon"><i class="ki-outline ki-dollar"></i></div><h4 class="empty-state-title">No hay comisiones</h4><p class="empty-state-text">Las comisiones de tus clientes aparecerán aquí</p></div>';
            paginationControls.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(item) {
            var statusBadge = getStatusBadge(item.status);
            
            html += '<div class="list-card">' +
                '<div class="customer-name" title="' + escapeHtml(item.customer_name) + '">' + escapeHtml(item.customer_name) + '</div>' +
                '<span class="amount-badge">' + formatCurrency(item.amount) + '</span>' +
                '<div class="request-info"><i class="ki-outline ki-document"></i>#' + item.request_id + ' - ' + escapeHtml(truncate(item.category_name, 15)) + '</div>' +
                '<div class="commission-meta">' +
                    '<span class="meta-item"><i class="ki-outline ki-calendar"></i>' + item.activated_at + '</span>' +
                    statusBadge +
                    '<span class="meta-item"><i class="ki-outline ki-arrows-circle"></i>' + item.recurring + '</span>' +
                '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationControls.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
        scrollContainer.scrollTop = 0;
    }
    
    function getStatusBadge(status) {
        var classes = { 'pendiente': 'badge-warning-facilitame', 'activa': 'badge-success-facilitame', 'desactivada': 'badge-danger-facilitame' };
        return '<span class="badge badge-facilitame ' + (classes[status] || 'badge-warning-facilitame') + '">' + escapeHtml(status) + '</span>';
    }
    
    function formatCurrency(amount) { return parseFloat(amount).toFixed(2) + ' €'; }
    function truncate(str, len) { return !str ? '' : str.length > len ? str.substring(0, len) + '...' : str; }
    
    window.filterAdvisoryCommissions = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
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
        pageInfo.textContent = state.currentPage + ' / ' + state.totalPages;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationControls.style.display = state.totalRecords <= state.pageSize ? 'none' : 'flex';
    }
    
    function updateResultsCount(p) {
        resultsCount.innerHTML = p.total_records === 0 ? '' : '<strong>' + p.from + '-' + p.to + '</strong> de <strong>' + p.total_records + '</strong>';
    }
    
    function showLoading() {
        var html = '';
        for (var i = 0; i < 10; i++) html += '<div class="skeleton-card"><div class="skeleton-line skeleton-name"></div><div class="skeleton-line skeleton-amount"></div><div class="skeleton-line skeleton-info"></div><div style="flex:1;display:flex;gap:0.5rem;"><div class="skeleton-line skeleton-badge"></div><div class="skeleton-line skeleton-badge"></div></div></div>';
        listContainer.innerHTML = html;
    }
    
    function showError(msg) {
        listContainer.innerHTML = '<div class="empty-state-modern"><div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div><h4 class="empty-state-title">Error</h4><p class="empty-state-text">' + escapeHtml(msg) + '</p></div>';
    }
    
    function escapeHtml(text) { if (!text) return ''; var div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
    
    window.reloadAdvisoryCommissions = function() { loadData(); };
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
</script>