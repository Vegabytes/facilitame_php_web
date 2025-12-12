<?php
$currentPage = 'invoices';

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id, plan FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    echo '<div class="alert alert-danger m-5">Asesoría no encontrada.</div>';
    return;
}

$advisory_id = $advisory['id'];
$can_receive = ($advisory['plan'] !== 'gratuito');

$tags = ['restaurante', 'gasolina', 'proveedores', 'material_oficina', 'viajes', 'servicios', 'otros'];
$tagLabels = [
    'restaurante' => 'Restaurante',
    'gasolina' => 'Gasolina',
    'proveedores' => 'Proveedores',
    'material_oficina' => 'Mat. oficina',
    'viajes' => 'Viajes',
    'servicios' => 'Servicios',
    'otros' => 'Otros'
];
?>

<div id="facilita-app">
    <div class="customers-page">
        
        <?php if (!$can_receive): ?>
        <div class="info-box info-box-warning mb-4">
            <div class="info-box-icon">
                <i class="ki-outline ki-information-2"></i>
            </div>
            <div class="info-box-content">
                <span class="info-box-text">Plan gratuito: tus clientes no pueden enviarte facturas. <a href="/pricing" class="fw-semibold">Mejora tu plan</a></span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="kpi-card kpi-card-primary">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-calendar"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Este mes</div>
                            <div class="kpi-value" id="kpi-this-month">0</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-card-warning">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-time"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Pendientes</div>
                            <div class="kpi-value" id="kpi-pending">0</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-card-danger">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-arrow-down"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Gastos</div>
                            <div class="kpi-value" id="kpi-gastos">0</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-card-success">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-arrow-up"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Ingresos</div>
                            <div class="kpi-value" id="kpi-ingresos">0</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-card-info">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-check"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Total</div>
                            <div class="kpi-value" id="kpi-total">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card principal -->
        <div class="card dashboard-tabs-card">
            
            <!-- Controles -->
            <div class="list-controls">
                <div class="results-info">
                    <span id="inv-results-count">Cargando...</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" id="inv-filter-type">
                        <option value="">Tipo</option>
                        <option value="gasto">Gastos</option>
                        <option value="ingreso">Ingresos</option>
                    </select>
                    <select class="form-select form-select-sm" id="inv-filter-tag">
                        <option value="">Etiqueta</option>
                        <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo $tag; ?>"><?php echo $tagLabels[$tag] ?? ucfirst($tag); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select form-select-sm" id="inv-filter-status">
                        <option value="">Estado</option>
                        <option value="pending">Pendiente</option>
                        <option value="processed">Procesada</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary-facilitame" data-bs-toggle="modal" data-bs-target="#modal_upload_invoices">
                        <i class="ki-outline ki-plus fs-4 me-1"></i>Subir Facturas
                    </button>
                </div>
            </div>
            
            <!-- Listado -->
            <div class="card-body">
                <div class="tab-list-container" id="inv-list">
                    <div class="loading-state">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Cargando facturas...</span>
                    </div>
                </div>
            </div>
            
            <!-- Paginador -->
            <div class="pagination-container" id="inv-pagination">
                <div class="pagination-info" id="inv-page-info">Página 1 de 1</div>
                <div class="pagination-nav">
                    <button class="btn-pagination" id="inv-prev" disabled>
                        <i class="ki-outline ki-left"></i>
                    </button>
                    <span class="pagination-current" id="inv-page-current">1 / 1</span>
                    <button class="btn-pagination" id="inv-next" disabled>
                        <i class="ki-outline ki-right"></i>
                    </button>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- Modal: Subir Facturas de Cliente -->
<div class="modal fade" id="modal_upload_invoices" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-cloud-add"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Subir Facturas de Cliente</h5>
                        <p class="text-muted fs-7 mb-0">Sube facturas en nombre de un cliente</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_upload_invoices" enctype="multipart/form-data">
                <div class="modal-body pt-4">
                    
                    <!-- Selector de Cliente -->
                    <div class="mb-4" id="customer-select-wrapper">
                        <label class="form-label fw-semibold">
                            <i class="ki-outline ki-profile-circle text-primary me-1"></i>
                            Cliente <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" 
                                   class="form-control" 
                                   id="customer-search-input"
                                   placeholder="Buscar cliente por nombre o email..."
                                   autocomplete="off">
                            <div class="dropdown-menu w-100" id="customer-dropdown"></div>
                        </div>
                        <input type="hidden" name="customer_id" id="selected-customer-id" required>
                    </div>
                    
                    <!-- Cliente Seleccionado -->
                    <div class="selected-customer-card mb-4" id="selected-customer-badge" style="display: none;">
                        <div class="selected-customer-avatar" id="selected-customer-avatar">--</div>
                        <div class="selected-customer-info">
                            <div class="selected-customer-name" id="selected-customer-name">-</div>
                            <div class="selected-customer-email" id="selected-customer-email">-</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" id="btn-change-customer">
                            <i class="ki-outline ki-pencil"></i> Cambiar
                        </button>
                    </div>
                    
                    <!-- Upload zone -->
                    <div class="mb-4">
                        <div class="upload-drop-zone" id="upload-zone">
                            <input type="file" 
                                   name="invoice_files[]" 
                                   id="invoice-files-input"
                                   class="drop-zone-input" 
                                   multiple 
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <div class="upload-icon-circle">
                                <i class="ki-outline ki-folder-up"></i>
                            </div>
                            <div class="upload-text">
                                <span class="upload-title">Arrastra tus archivos aquí</span>
                                <span class="upload-subtitle">o <span class="upload-link">haz click para seleccionar</span></span>
                            </div>
                            <div class="upload-formats">
                                <span class="format-badge">PDF</span>
                                <span class="format-badge">JPG</span>
                                <span class="format-badge">PNG</span>
                                <span class="format-separator">•</span>
                                <span class="format-size">Máx. 10MB por archivo</span>
                            </div>
                        </div>
                        <div id="files-preview" class="files-preview-container"></div>
                    </div>
                    
                    <!-- Tipo y Etiqueta -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <div class="type-selector">
                                <label class="type-option type-gasto">
                                    <input type="radio" name="type" value="gasto" checked>
                                    <span class="type-content">
                                        <i class="ki-outline ki-arrow-down"></i>
                                        <span>Gasto</span>
                                    </span>
                                </label>
                                <label class="type-option type-ingreso">
                                    <input type="radio" name="type" value="ingreso">
                                    <span class="type-content">
                                        <i class="ki-outline ki-arrow-up"></i>
                                        <span>Ingreso</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Etiqueta <span class="text-danger">*</span></label>
                            <select name="tag" class="form-select" required>
                                <option value="">Selecciona una etiqueta...</option>
                                <?php foreach ($tags as $tag): ?>
                                <option value="<?php echo $tag; ?>"><?php echo $tagLabels[$tag] ?? ucfirst($tag); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Notas -->
                    <div>
                        <label class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Añadir notas sobre estas facturas..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-invoices" disabled>
                        <i class="ki-outline ki-cloud-add me-1"></i>
                        Subir Facturas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var API_URL = '/api-advisory-invoices-paginated';
    var tagLabels = <?php echo json_encode($tagLabels); ?>;
    
    var state = {
        currentPage: 1,
        pageSize: 30,
        searchQuery: '',
        tag: '',
        status: '',
        type: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    var listContainer = document.getElementById('inv-list');
    var resultsCount = document.getElementById('inv-results-count');
    var pageInfo = document.getElementById('inv-page-info');
    var pageCurrent = document.getElementById('inv-page-current');
    var prevBtn = document.getElementById('inv-prev');
    var nextBtn = document.getElementById('inv-next');
    var tagFilter = document.getElementById('inv-filter-tag');
    var statusFilter = document.getElementById('inv-filter-status');
    var typeFilter = document.getElementById('inv-filter-type');
    var paginationContainer = document.getElementById('inv-pagination');
    
    var searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        tagFilter.addEventListener('change', function(e) { state.tag = e.target.value; state.currentPage = 1; loadData(); });
        statusFilter.addEventListener('change', function(e) { state.status = e.target.value; state.currentPage = 1; loadData(); });
        typeFilter.addEventListener('change', function(e) { state.type = e.target.value; state.currentPage = 1; loadData(); });
        loadData();
        initUploadModal();
    }
    
    function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        var params = new URLSearchParams({ 
            page: state.currentPage, 
            limit: state.pageSize, 
            search: state.searchQuery, 
            tag: state.tag, 
            status: state.status,
            type: state.type
        });
        
        fetch(API_URL + '?' + params)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    var pagination = result.data.pagination;
                    var stats = result.data.stats;
                    state.totalPages = pagination.total_pages;
                    state.totalRecords = pagination.total_records;
                    
                    document.getElementById('kpi-this-month').textContent = stats.this_month || 0;
                    document.getElementById('kpi-pending').textContent = stats.pending || 0;
                    document.getElementById('kpi-total').textContent = stats.total || 0;
                    document.getElementById('kpi-gastos').textContent = stats.gastos || 0;
                    document.getElementById('kpi-ingresos').textContent = stats.ingresos || 0;
                    
                    renderList(result.data.data || []);
                    updateResultsCount(pagination);
                    updatePaginationControls();
                } else {
                    showError(result.message || 'Error al cargar datos');
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                showError('Error de conexión');
            })
            .finally(function() { state.isLoading = false; });
    }
    
    function renderList(data) {
        if (!data || data.length === 0) {
            var hasFilters = state.searchQuery || state.tag || state.status || state.type;
            listContainer.innerHTML = 
                '<div class="empty-state">' +
                    '<div class="empty-state-icon"><i class="ki-outline ki-document"></i></div>' +
                    '<div class="empty-state-title">' + (hasFilters ? 'Sin resultados' : 'No hay facturas') + '</div>' +
                    '<p class="empty-state-text">' + (hasFilters ? 'No se encontraron facturas con los filtros seleccionados' : 'Las facturas de tus clientes aparecerán aquí') + '</p>' +
                '</div>';
            paginationContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(inv) {
            var borderClass = inv.is_processed ? 'list-card-success' : 'list-card-warning';
            var statusBadge = inv.is_processed 
                ? '<span class="badge-status badge-status-success">Procesada</span>'
                : '<span class="badge-status badge-status-warning">Pendiente</span>';
            
            var typeBadge = inv.type === 'ingreso'
                ? '<span class="badge-status badge-status-success"><i class="ki-outline ki-arrow-up"></i> Ingreso</span>'
                : '<span class="badge-status badge-status-danger"><i class="ki-outline ki-arrow-down"></i> Gasto</span>';
            
            html += '<div class="list-card ' + borderClass + '">' +
                '<div class="list-card-content">' +
                    '<div class="list-card-title">' +
                        '<span class="badge-status badge-status-neutral">#' + inv.id + '</span>' +
                        '<a href="/customer?id=' + inv.customer_id + '" class="list-card-customer">' + escapeHtml(inv.customer_name) + '</a>' +
                        '<span class="badge-status badge-status-muted">' + escapeHtml(inv.nif_cif || '-') + '</span>' +
                        typeBadge +
                        '<span class="badge-status badge-status-info">' + (tagLabels[inv.tag] || inv.tag) + '</span>' +
                        statusBadge +
                    '</div>' +
                    '<div class="list-card-meta">' +
                        '<span><i class="ki-outline ki-document"></i> ' + escapeHtml(inv.filename) + '</span>' +
                        '<span><i class="ki-outline ki-calendar"></i> ' + inv.created_at_formatted + '</span>' +
                        '<span><i class="ki-outline ki-data"></i> ' + inv.filesize_formatted + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="list-card-actions">' +
                    '<a href="/api/file-download?type=advisory_invoice&id=' + inv.id + '" target="_blank" class="btn-icon btn-icon-info" title="Ver"><i class="ki-outline ki-eye"></i></a>' +
                    (!inv.is_processed 
                        ? '<button type="button" class="btn-icon btn-icon-success btn-mark-processed" data-id="' + inv.id + '" title="Marcar procesada"><i class="ki-outline ki-check"></i></button>'
                        : '') +
                '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
        listContainer.scrollTop = 0;
        
        document.querySelectorAll('.btn-mark-processed').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var invId = this.dataset.id;
                Swal.fire({
                    title: '¿Marcar como procesada?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, marcar',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var fd = new FormData();
                        fd.append('invoice_id', invId);
                        fetch('/api/advisory-mark-invoice-processed', { method: 'POST', body: fd })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.status === 'ok') {
                                    Swal.fire({ icon: 'success', title: 'Procesada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                                    loadData();
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al procesar' });
                                }
                            });
                    }
                });
            });
        });
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        pageCurrent.textContent = state.currentPage + ' / ' + state.totalPages;
        pageInfo.textContent = 'Página ' + state.currentPage + ' de ' + state.totalPages;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0 
            ? 'No hay resultados' 
            : 'Mostrando <strong>' + pagination.from + '-' + pagination.to + '</strong> de <strong>' + pagination.total_records + '</strong>';
    }
    
    function showLoading() {
        listContainer.innerHTML = 
            '<div class="loading-state">' +
                '<div class="spinner-border spinner-border-sm text-primary"></div>' +
                '<span class="ms-2">Cargando facturas...</span>' +
            '</div>';
    }
    
    function showError(msg) {
        listContainer.innerHTML = 
            '<div class="empty-state">' +
                '<div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>' +
                '<div class="empty-state-title">Error al cargar</div>' +
                '<p class="empty-state-text">' + escapeHtml(msg) + '</p>' +
                '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadInvoices()">' +
                    '<i class="ki-outline ki-arrows-circle me-1"></i>Reintentar' +
                '</button>' +
            '</div>';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.filterInvoices = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.filterAdvisoryInvoices = window.filterInvoices;
    window.reloadInvoices = function() { loadData(); };
    
    // ============================================
    // MODAL: SUBIR FACTURAS
    // ============================================
    
    var uploadModal = null;
    var selectedFiles = [];
    var selectedCustomer = null;
    
    function initUploadModal() {
        var modalEl = document.getElementById('modal_upload_invoices');
        if (!modalEl) return;
        
        uploadModal = new bootstrap.Modal(modalEl);
        
        var uploadZone = document.getElementById('upload-zone');
        var fileInput = document.getElementById('invoice-files-input');
        var customerSearchInput = document.getElementById('customer-search-input');
        var customerDropdown = document.getElementById('customer-dropdown');
        var btnChangeCustomer = document.getElementById('btn-change-customer');
        var form = document.getElementById('form_upload_invoices');
        var tagSelect = form.querySelector('select[name="tag"]');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            uploadZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        ['dragenter', 'dragover'].forEach(function(eventName) {
            uploadZone.addEventListener(eventName, function() {
                uploadZone.classList.add('dragover');
            });
        });
        
        ['dragleave', 'drop'].forEach(function(eventName) {
            uploadZone.addEventListener(eventName, function() {
                uploadZone.classList.remove('dragover');
            });
        });
        
        uploadZone.addEventListener('drop', function(e) {
            handleFiles(e.dataTransfer.files);
        });
        
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });
        
        var customerSearchTimeout = null;
        customerSearchInput.addEventListener('input', function() {
            var query = this.value.trim();
            clearTimeout(customerSearchTimeout);
            
            if (query.length < 2) {
                customerDropdown.classList.remove('show');
                return;
            }
            
            customerSearchTimeout = setTimeout(function() {
                searchCustomers(query);
            }, 300);
        });
        
        customerSearchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                searchCustomers(this.value.trim());
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#customer-select-wrapper')) {
                customerDropdown.classList.remove('show');
            }
        });
        
        btnChangeCustomer.addEventListener('click', function() {
            selectedCustomer = null;
            document.getElementById('selected-customer-id').value = '';
            document.getElementById('selected-customer-badge').style.display = 'none';
            document.getElementById('customer-select-wrapper').style.display = 'block';
            customerSearchInput.value = '';
            customerSearchInput.focus();
            updateSubmitButton();
        });
        
        tagSelect.addEventListener('change', updateSubmitButton);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitInvoices();
        });
        
        modalEl.addEventListener('hidden.bs.modal', function() {
            resetUploadModal();
        });
    }
    
    function searchCustomers(query) {
        var dropdown = document.getElementById('customer-dropdown');
        
        fetch('/api/advisory-clients-paginated?search=' + encodeURIComponent(query) + '&limit=10')
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data && result.data.data && result.data.data.length > 0) {
                    renderCustomerDropdown(result.data.data);
                } else {
                    dropdown.innerHTML = '<div class="p-3 text-center text-muted">No se encontraron clientes</div>';
                    dropdown.classList.add('show');
                }
            })
            .catch(function() {
                dropdown.innerHTML = '<div class="p-3 text-center text-danger">Error de conexión</div>';
                dropdown.classList.add('show');
            });
    }
    
    function renderCustomerDropdown(customers) {
        var dropdown = document.getElementById('customer-dropdown');
        
        var html = '';
        customers.forEach(function(customer) {
            var fullName = ((customer.name || '') + ' ' + (customer.lastname || '')).trim();
            var initials = getInitials(fullName);
            
            html += '<div class="dropdown-item d-flex align-items-center gap-2 cursor-pointer" ' +
                'data-id="' + customer.id + '" ' +
                'data-name="' + escapeHtml(fullName) + '" ' +
                'data-email="' + escapeHtml(customer.email || '') + '">' +
                '<div class="avatar avatar-xs">' + initials + '</div>' +
                '<div class="flex-grow-1">' +
                    '<div class="fw-semibold">' + escapeHtml(fullName) + '</div>' +
                    '<div class="text-muted fs-7">' + escapeHtml(customer.email || '-') + '</div>' +
                '</div>' +
            '</div>';
        });
        
        dropdown.innerHTML = html;
        dropdown.classList.add('show');
        
        dropdown.querySelectorAll('.dropdown-item').forEach(function(opt) {
            opt.addEventListener('click', function() {
                selectCustomer({
                    id: this.dataset.id,
                    name: this.dataset.name,
                    email: this.dataset.email
                });
            });
        });
    }
    
    function selectCustomer(customer) {
        selectedCustomer = customer;
        
        document.getElementById('selected-customer-id').value = customer.id;
        document.getElementById('selected-customer-name').textContent = customer.name;
        document.getElementById('selected-customer-email').textContent = customer.email || '-';
        document.getElementById('selected-customer-avatar').textContent = getInitials(customer.name);
        
        document.getElementById('customer-dropdown').classList.remove('show');
        document.getElementById('customer-select-wrapper').style.display = 'none';
        document.getElementById('selected-customer-badge').style.display = 'flex';
        
        updateSubmitButton();
    }
    
    function handleFiles(files) {
        var validFiles = Array.from(files).filter(function(file) {
            var ext = file.name.split('.').pop().toLowerCase();
            return ['pdf', 'jpg', 'jpeg', 'png'].indexOf(ext) !== -1;
        });
        
        if (validFiles.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Formato no válido', text: 'Solo se permiten archivos PDF, JPG o PNG', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            return;
        }
        
        selectedFiles = selectedFiles.concat(validFiles);
        renderFilesPreview();
        updateSubmitButton();
    }
    
    function renderFilesPreview() {
        var container = document.getElementById('files-preview');
        
        if (selectedFiles.length === 0) {
            container.classList.remove('has-files');
            container.innerHTML = '';
            return;
        }
        
        container.classList.add('has-files');
        
        var html = '<div class="files-preview-header">' +
            '<span class="files-preview-title">' +
                '<i class="ki-outline ki-document"></i>' +
                selectedFiles.length + ' archivo' + (selectedFiles.length > 1 ? 's' : '') + ' seleccionado' + (selectedFiles.length > 1 ? 's' : '') +
            '</span>' +
            '<button type="button" class="btn-clear-files" onclick="clearAllFiles()">' +
                '<i class="ki-outline ki-trash me-1"></i>Eliminar todos' +
            '</button>' +
        '</div>' +
        '<div class="files-preview-list">';
        
        selectedFiles.forEach(function(file, index) {
            var ext = file.name.split('.').pop().toLowerCase();
            var iconClass = ext === 'pdf' ? 'icon-pdf' : 'icon-image';
            var iconName = ext === 'pdf' ? 'ki-document' : 'ki-picture';
            
            html += '<div class="file-preview-item">' +
                '<div class="file-icon ' + iconClass + '">' +
                    '<i class="ki-outline ' + iconName + '"></i>' +
                '</div>' +
                '<div class="file-info">' +
                    '<div class="file-name">' + escapeHtml(file.name) + '</div>' +
                    '<div class="file-size">' + formatFileSize(file.size) + '</div>' +
                '</div>' +
                '<button type="button" class="btn-remove-file" onclick="removeFile(' + index + ')" title="Eliminar">' +
                    '<i class="ki-outline ki-cross"></i>' +
                '</button>' +
            '</div>';
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        var dt = new DataTransfer();
        selectedFiles.forEach(function(file) { dt.items.add(file); });
        document.getElementById('invoice-files-input').files = dt.files;
        renderFilesPreview();
        updateSubmitButton();
    };
    
    window.clearAllFiles = function() {
        selectedFiles = [];
        document.getElementById('invoice-files-input').value = '';
        renderFilesPreview();
        updateSubmitButton();
    };
    
    function updateSubmitButton() {
        var btn = document.getElementById('btn-submit-invoices');
        var hasCustomer = selectedCustomer !== null;
        var hasFiles = selectedFiles.length > 0;
        var hasTag = document.querySelector('select[name="tag"]').value !== '';
        
        btn.disabled = !(hasCustomer && hasFiles && hasTag);
    }
    
    function submitInvoices() {
        var btn = document.getElementById('btn-submit-invoices');
        var form = document.getElementById('form_upload_invoices');
        
        if (!selectedCustomer || selectedFiles.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Selecciona un cliente y al menos un archivo' });
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...';
        
        var formData = new FormData(form);
        
        formData.delete('invoice_files[]');
        selectedFiles.forEach(function(file) {
            formData.append('invoice_files[]', file);
        });
        
        fetch('/api/advisory-upload-customer-invoices', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.status === 'ok') {
                Swal.fire({ icon: 'success', title: 'Facturas subidas', text: result.message || 'Facturas subidas correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                uploadModal.hide();
                loadData();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al subir facturas' });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ki-outline ki-cloud-add me-1"></i>Subir Facturas';
        });
    }
    
    function resetUploadModal() {
        selectedFiles = [];
        selectedCustomer = null;
        
        document.getElementById('form_upload_invoices').reset();
        document.getElementById('selected-customer-id').value = '';
        document.getElementById('customer-search-input').value = '';
        document.getElementById('customer-dropdown').classList.remove('show');
        document.getElementById('customer-select-wrapper').style.display = 'block';
        document.getElementById('selected-customer-badge').style.display = 'none';
        document.getElementById('files-preview').classList.remove('has-files');
        document.getElementById('files-preview').innerHTML = '';
        
        updateSubmitButton();
    }
    
    function getInitials(name) {
        if (!name) return '--';
        var parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    window.openUploadInvoicesModal = function(customerId, customerName, customerEmail) {
        resetUploadModal();
        
        if (customerId && customerName) {
            selectCustomer({
                id: customerId,
                name: customerName,
                email: customerEmail || ''
            });
        }
        
        uploadModal.show();
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>