<!-- Componente: home-datatable-facturas-asesoria.php -->

<?php
$DOCUMENTS_URL = ROOT_URL . '/' . DOCUMENTS_DIR;
?>

<div class="list-controls">
    <div class="results-info"><span id="facturas-results-count">Cargando...</span></div>
    <div class="d-flex align-items-center gap-3">
        <div class="filter-buttons">
            <button class="btn btn-sm btn-filter active" data-filter="pending">Sin procesar</button>
            <button class="btn btn-sm btn-filter" data-filter="all">Todas</button>
        </div>
        <div class="pagination-size">
            <label for="facturas-page-size">Mostrar:</label>
            <select id="facturas-page-size" class="form-select form-select-sm">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
</div>

<div class="tab-list-container" id="facturas-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span class="ms-2">Cargando...</span>
    </div>
</div>

<div class="pagination-container" id="facturas-pagination" style="display: none;">
    <div class="pagination-info" id="facturas-page-info"></div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="facturas-prev" disabled><i class="ki-outline ki-left"></i></button>
        <span class="pagination-current" id="facturas-page-current">1 / 1</span>
        <button class="btn-pagination" id="facturas-next" disabled><i class="ki-outline ki-right"></i></button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api-advisory-invoices-paginated';
    const DOCUMENTS_URL = '<?php echo $DOCUMENTS_URL; ?>';
    const state = { currentPage: 1, pageSize: 25, searchQuery: '', filter: 'pending', totalPages: 1, totalRecords: 0, isLoading: false };
    
    const els = {
        list: document.getElementById('facturas-list'),
        resultsCount: document.getElementById('facturas-results-count'),
        pageInfo: document.getElementById('facturas-page-info'),
        pageCurrent: document.getElementById('facturas-page-current'),
        prevBtn: document.getElementById('facturas-prev'),
        nextBtn: document.getElementById('facturas-next'),
        pageSizeSelect: document.getElementById('facturas-page-size'),
        pagination: document.getElementById('facturas-pagination'),
        filterBtns: document.querySelectorAll('#tab-facturas .btn-filter')
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
        
        els.filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                els.filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                state.filter = this.dataset.filter;
                state.currentPage = 1;
                loadData();
            });
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
                search: state.searchQuery,
                status: state.filter === 'pending' ? 'pending' : ''
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
                    <div class="empty-state-icon"><i class="ki-outline ki-document"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay facturas'}</div>
                    <p class="empty-state-text">${state.searchQuery ? 'No se encontraron resultados' : 'No tienes facturas pendientes de procesar'}</p>
                </div>`;
            els.pagination.style.display = 'none';
            return;
        }
        
        els.list.innerHTML = data.map(f => {
            const typeBadge = f.type === 'ingreso' 
                ? '<span class="badge-status badge-status-success">Ingreso</span>'
                : '<span class="badge-status badge-status-danger">Gasto</span>';
            
            const processedBadge = f.is_processed
                ? '<span class="badge-status badge-status-light">Procesada</span>'
                : '<span class="badge-status badge-status-warning">Pendiente</span>';
            
            const periodStr = getPeriodStr(f);
            const fileIcon = getFileIcon(f.filename);
            const fileSize = f.filesize_formatted || formatFileSize(f.filesize);
            
            // URL correcta usando DOCUMENTS_URL + url del archivo
            const downloadUrl = DOCUMENTS_URL + '/' + f.url;
            
            return `
                <div class="list-card list-card-${f.is_processed ? 'success' : 'danger'}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="d-flex align-items-center gap-2">
                                <i class="ki-outline ${fileIcon} text-muted"></i>
                                ${escapeHtml(f.filename)}
                            </span>
                            ${typeBadge}
                            ${processedBadge}
                        </div>
                        <div class="list-card-meta">
                            <span><i class="ki-outline ki-profile-user"></i> ${escapeHtml(f.customer_name)}</span>
                            ${f.tag ? `<span><i class="ki-outline ki-tag"></i> ${escapeHtml(f.tag)}</span>` : ''}
                            ${periodStr ? `<span><i class="ki-outline ki-calendar"></i> ${periodStr}</span>` : ''}
                            <span><i class="ki-outline ki-file"></i> ${fileSize}</span>
                            <span><i class="ki-outline ki-time"></i> ${f.created_at_formatted || formatDate(f.created_at)}</span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="${downloadUrl}" class="btn-icon btn-icon-info" title="Ver" target="_blank">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        ${!f.is_processed ? `
                            <button class="btn-icon btn-icon-success" title="Marcar procesada" data-id="${f.id}" onclick="window.markProcessed(this)">
                                <i class="ki-outline ki-check"></i>
                            </button>
                        ` : ''}
                        <a href="/customer?id=${f.customer_id}" class="btn-icon btn-icon-primary" title="Ver cliente">
                            <i class="ki-outline ki-profile-user"></i>
                        </a>
                    </div>
                </div>`;
        }).join('');
        
        els.pagination.style.display = 'flex';
        els.list.scrollTop = 0;
    }
    
    function getFileIcon(filename) {
        if (!filename) return 'ki-document';
        const ext = filename.split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'ki-file-added';
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'ki-picture';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'ki-chart';
        return 'ki-document';
    }
    
    function formatFileSize(bytes) {
        if (!bytes) return '—';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    function getPeriodStr(f) {
        if (f.quarter && f.year) return `T${f.quarter} ${f.year}`;
        if (f.month && f.year) {
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            return `${months[f.month - 1]} ${f.year}`;
        }
        if (f.year) return f.year;
        return '';
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadFacturas()">
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
    
    // Exports
    window.initFacturasTab = function() {
        if (!window._facturasInit) {
            window._facturasInit = true;
            init();
        }
    };
    
    window.filterFacturas = function(query) {
        if (!window._facturasInit) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadFacturas = () => loadData();
    
    window.markProcessed = async function(btn) {
        const id = btn.dataset.id;
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        
        icon.className = 'spinner-border spinner-border-sm';
        btn.disabled = true;
        
        try {
            const response = await fetch('/api/advisory-mark-invoice-processed', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            
            const result = await response.json();
            
            if (result.status === 'ok') {
                Swal.fire({ icon: 'success', title: 'Factura marcada como procesada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                loadData();
                
                const badge = document.getElementById('badge-facturas');
                if (badge) {
                    const current = parseInt(badge.textContent) || 0;
                    badge.textContent = Math.max(0, current - 1);
                }
                
                const kpi = document.getElementById('kpi-facturas');
                if (kpi) {
                    const current = parseInt(kpi.textContent) || 0;
                    kpi.textContent = Math.max(0, current - 1);
                }
            } else {
                Swal.fire({ icon: 'error', title: result.message || 'Error al procesar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                icon.className = originalClass;
                btn.disabled = false;
            }
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            icon.className = originalClass;
            btn.disabled = false;
        }
    };
})();
</script>