<?php
$currentPage = 'advisory-invoices';

$stmt = $pdo->prepare("
    SELECT ca.advisory_id 
    FROM customers_advisories ca
    WHERE ca.customer_id = ?
    LIMIT 1
");
$stmt->execute([USER['id']]);
$advisory_data = $stmt->fetch();
$customer_advisory_id = $advisory_data['advisory_id'] ?? null;

$can_send = false;
if ($customer_advisory_id) {
    $stmt = $pdo->prepare("SELECT plan FROM advisories WHERE id = ?");
    $stmt->execute([$customer_advisory_id]);
    $advisory = $stmt->fetch();
    $can_send = ($advisory && $advisory['plan'] !== 'gratuito');
}

$tags = [
    'restaurante' => 'Restaurante',
    'gasolina' => 'Gasolina',
    'proveedores' => 'Proveedores',
    'material_oficina' => 'Mat. oficina',
    'viajes' => 'Viajes',
    'servicios' => 'Servicios',
    'otros' => 'Otros'
];
?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <?php if (!$customer_advisory_id): ?>
    <!-- Sin asesoría -->
    <div class="card" style="flex: 1; display: flex; align-items: center; justify-content: center;">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-information-2"></i>
            </div>
            <div class="empty-state-title">No tienes una asesoría vinculada</div>
            <p class="empty-state-text">Para enviar facturas, primero debes estar vinculado a una asesoría</p>
        </div>
    </div>
    
    <?php elseif (!$can_send): ?>
    <!-- Plan gratuito -->
    <div class="card" style="flex: 1; display: flex; align-items: center; justify-content: center;">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-lock text-warning"></i>
            </div>
            <div class="empty-state-title">Función no disponible</div>
            <p class="empty-state-text">Tu asesoría tiene el plan gratuito que no incluye envío de facturas</p>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="invoices-results-count">Cargando...</span>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="pagination-size">
                    <label for="filter-type">Tipo:</label>
                    <select id="filter-type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="gasto">Gastos</option>
                        <option value="ingreso">Ingresos</option>
                    </select>
                </div>
                <div class="pagination-size">
                    <label for="filter-tag">Etiqueta:</label>
                    <select id="filter-tag" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($tags as $tag_val => $tag_label): ?>
                        <option value="<?php echo $tag_val; ?>"><?php echo $tag_label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pagination-size">
                    <label for="filter-month">Mes:</label>
                    <select id="filter-month" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php 
                        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        foreach ($months as $idx => $month): 
                        ?>
                        <option value="<?php echo $idx + 1; ?>"><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_send_invoice">
                    <i class="ki-outline ki-add-files me-1"></i>Nueva
                </button>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="invoices-container" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando facturas...</span>
                </div>
            </div>
        </div>
        
    </div>
    
    <?php endif; ?>
    
</div>

<?php if ($can_send): ?>
<!-- Modal Enviar Factura - Diseño Mejorado -->
<div class="modal fade" id="modal_send_invoice" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-document"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Enviar Factura</h5>
                        <p class="text-muted fs-7 mb-0">Sube tus facturas para que tu asesoría las procese</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_send_invoice" enctype="multipart/form-data">
                <div class="modal-body pt-4">
                    
                    <!-- Upload zone mejorada -->
                    <div class="mb-4">
                        <div class="upload-drop-zone" id="invoice-dropzone">
                            <input type="file" 
                                   name="invoice_file[]" 
                                   id="invoice_file_input"
                                   class="drop-zone-input" 
                                   multiple 
                                   accept=".pdf,.jpg,.jpeg,.png" 
                                   required>
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
                                <?php foreach ($tags as $tag_val => $tag_label): ?>
                                <option value="<?php echo $tag_val; ?>"><?php echo $tag_label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Notas -->
                    <div>
                        <label class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Añade información adicional sobre esta factura..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-send me-1"></i>
                        Enviar factura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Icon */
.modal-icon-wrapper {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--color-main-facilitame) 0%, #00a8b0 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-icon-wrapper i {
    font-size: 1.5rem;
    color: white;
}

/* Upload Zone */
.upload-drop-zone {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-drop-zone:hover {
    border-color: var(--color-main-facilitame);
    background: rgba(0, 194, 203, 0.03);
}

.upload-drop-zone.dragover {
    border-color: var(--color-main-facilitame);
    background: rgba(0, 194, 203, 0.08);
    transform: scale(1.01);
}

.upload-drop-zone .drop-zone-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-icon-circle {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, var(--color-main-facilitame) 0%, #00a8b0 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(0, 194, 203, 0.25);
}

.upload-icon-circle i {
    font-size: 1.75rem;
    color: white;
}

.upload-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 1rem;
}

.upload-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.upload-subtitle {
    font-size: 0.875rem;
    color: #64748b;
}

.upload-link {
    color: var(--color-main-facilitame);
    font-weight: 500;
    text-decoration: underline;
}

.upload-formats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.format-badge {
    background: white;
    border: 1px solid #e2e8f0;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
}

.format-separator {
    color: #cbd5e1;
}

.format-size {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Files Preview */
.files-preview-container {
    margin-top: 1rem;
    display: none;
}

.files-preview-container.has-files {
    display: block;
}

.files-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.files-preview-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.files-preview-title i {
    color: var(--color-main-facilitame);
}

.btn-clear-files {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 0.8125rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: background 0.2s;
}

.btn-clear-files:hover {
    background: #fef2f2;
}

.files-preview-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.file-preview-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s;
}

.file-preview-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.file-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.file-icon.icon-pdf {
    background: #fef2f2;
    color: #ef4444;
}

.file-icon.icon-image {
    background: #f0fdf4;
    color: #22c55e;
}

.file-icon i {
    font-size: 1.25rem;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    font-size: 0.75rem;
    color: #9ca3af;
}

.btn-remove-file {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-remove-file:hover {
    background: #fef2f2;
    color: #ef4444;
}

/* Type Selector */
.type-selector {
    display: flex;
    gap: 0.75rem;
}

.type-option {
    flex: 1;
    cursor: pointer;
}

.type-option input {
    display: none;
}

.type-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    font-weight: 500;
    transition: all 0.2s ease;
    background: white;
}

.type-content i {
    font-size: 1rem;
}

/* Gasto */
.type-gasto .type-content {
    color: #6b7280;
}

.type-gasto input:checked + .type-content {
    border-color: #ef4444;
    background: #fef2f2;
    color: #dc2626;
}

/* Ingreso */
.type-ingreso .type-content {
    color: #6b7280;
}

.type-ingreso input:checked + .type-content {
    border-color: #22c55e;
    background: #f0fdf4;
    color: #16a34a;
}

/* Hover states */
.type-option:hover .type-content {
    border-color: #d1d5db;
}

.type-gasto:hover .type-content {
    border-color: #fca5a5;
}

.type-ingreso:hover .type-content {
    border-color: #86efac;
}

/* Modal footer */
#modal_send_invoice .modal-footer {
    padding: 1rem 1.5rem 1.5rem;
}

#modal_send_invoice .btn-primary {
    background: var(--color-main-facilitame);
    border-color: var(--color-main-facilitame);
    padding: 0.625rem 1.5rem;
}

#modal_send_invoice .btn-primary:hover {
    background: var(--color-main-facilitame-active);
    border-color: var(--color-main-facilitame-active);
}

/* Responsive */
@media (max-width: 576px) {
    .upload-drop-zone {
        padding: 1.5rem 1rem;
    }
    
    .upload-icon-circle {
        width: 56px;
        height: 56px;
    }
    
    .type-selector {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    let selectedFiles = [];
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    let totalInvoices = 0;
    let searchTimeout = null;
    let searchQuery = '';
    
    const tags = <?php echo json_encode($tags); ?>;
    
    const listContainer = document.getElementById('invoices-container');
    const resultsCount = document.getElementById('invoices-results-count');
    const dropzone = document.getElementById('invoice-dropzone');
    const fileInput = document.getElementById('invoice_file_input');
    const filesPreview = document.getElementById('files-preview');
    const formSendInvoice = document.getElementById('form_send_invoice');
    const filterMonth = document.getElementById('filter-month');
    const filterTag = document.getElementById('filter-tag');
    const filterType = document.getElementById('filter-type');
    
    function init() {
        if (!listContainer) return;
        
        // Filtros
        filterMonth?.addEventListener('change', resetAndLoad);
        filterTag?.addEventListener('change', resetAndLoad);
        filterType?.addEventListener('change', resetAndLoad);
        
        // Drag & drop
        if (dropzone && fileInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
            });
            
            ['dragenter', 'dragover'].forEach(evt => {
                dropzone.addEventListener(evt, () => dropzone.classList.add('dragover'));
            });
            
            ['dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, () => dropzone.classList.remove('dragover'));
            });
            
            dropzone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
            fileInput.addEventListener('change', () => handleFiles(fileInput.files));
        }
        
        // Form submit
        formSendInvoice?.addEventListener('submit', handleSubmit);
        
        // Infinite scroll
        listContainer.addEventListener('scroll', function() {
            if (isLoading || !hasMore) return;
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 100) {
                loadData(true);
            }
        });
        
        // Reset modal on close
        document.getElementById('modal_send_invoice')?.addEventListener('hidden.bs.modal', function() {
            formSendInvoice.reset();
            selectedFiles = [];
            renderFilesPreview();
        });
        
        loadData();
    }
    
    function handleFiles(files) {
        selectedFiles = Array.from(files);
        renderFilesPreview();
    }
    
    function renderFilesPreview() {
        if (selectedFiles.length === 0) {
            filesPreview.classList.remove('has-files');
            filesPreview.innerHTML = '';
            return;
        }
        
        filesPreview.classList.add('has-files');
        filesPreview.innerHTML = `
            <div class="files-preview-header">
                <span class="files-preview-title">
                    <i class="ki-outline ki-document"></i>
                    ${selectedFiles.length} archivo${selectedFiles.length > 1 ? 's' : ''} seleccionado${selectedFiles.length > 1 ? 's' : ''}
                </span>
                <button type="button" class="btn-clear-files" onclick="clearAllFiles()">
                    <i class="ki-outline ki-trash me-1"></i>Eliminar todos
                </button>
            </div>
            <div class="files-preview-list">
                ${selectedFiles.map((file, index) => `
                    <div class="file-preview-item">
                        <div class="file-icon ${file.type.includes('pdf') ? 'icon-pdf' : 'icon-image'}">
                            <i class="ki-outline ${file.type.includes('pdf') ? 'ki-document' : 'ki-picture'}"></i>
                        </div>
                        <div class="file-info">
                            <div class="file-name">${escapeHtml(file.name)}</div>
                            <div class="file-size">${formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="btn-remove-file" onclick="removeFile(${index})" title="Eliminar">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        renderFilesPreview();
    };
    
    window.clearAllFiles = function() {
        selectedFiles = [];
        fileInput.value = '';
        renderFilesPreview();
    };
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    async function handleSubmit(e) {
        e.preventDefault();
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
        
        try {
            const response = await fetch('/api/advisory-upload-invoice', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const result = await response.json();
            
            if (result.status === 'ok') {
                bootstrap.Modal.getInstance(document.getElementById('modal_send_invoice')).hide();
                e.target.reset();
                selectedFiles = [];
                renderFilesPreview();
                resetAndLoad();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Factura enviada',
                        text: result.message_plain || 'Tu factura ha sido enviada correctamente',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                throw new Error(result.message_plain || result.message || 'Error al enviar');
            }
        } catch (error) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
    
    async function loadData(append = false) {
        if (isLoading || (!hasMore && append)) return;
        
        if (!append) {
            currentPage = 1;
            hasMore = true;
            showLoading();
        } else {
            showLoadingMore();
        }
        
        isLoading = true;
        
        try {
            const params = new URLSearchParams({
                page: currentPage,
                month: filterMonth?.value || '',
                tag: filterTag?.value || '',
                type: filterType?.value || '',
                search: searchQuery
            });
            
            const response = await fetch(`/customer-invoices-list?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok') {
                totalInvoices = result.data.total;
                hasMore = result.data.has_more;
                
                if (append) {
                    removeLoadingMore();
                    appendItems(result.data.invoices);
                } else {
                    renderList(result.data.invoices);
                }
                
                updateResultsCount();
                currentPage++;
            } else {
                showError(result.message || 'Error al cargar');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        } finally {
            isLoading = false;
        }
    }
    
    function resetAndLoad() {
        currentPage = 1;
        hasMore = true;
        loadData(false);
    }
    
    function renderList(data) {
        if (!data.length) {
            const hasFilters = searchQuery || filterMonth?.value || filterTag?.value || filterType?.value;
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-document"></i>
                    </div>
                    <div class="empty-state-title">${hasFilters ? 'Sin resultados' : 'No hay facturas'}</div>
                    <p class="empty-state-text">
                        ${hasFilters 
                            ? 'No se encontraron facturas con los filtros seleccionados' 
                            : 'Aún no has enviado facturas a tu asesoría'}
                    </p>
                    ${!hasFilters ? `
                        <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modal_send_invoice">
                            <i class="ki-outline ki-add-files me-1"></i>Enviar primera factura
                        </button>
                    ` : ''}
                </div>`;
            return;
        }
        
        listContainer.innerHTML = data.map((inv, i) => renderItem(inv, i)).join('');
        listContainer.scrollTop = 0;
    }
    
    function appendItems(data) {
        const startIndex = listContainer.querySelectorAll('.list-card').length;
        listContainer.insertAdjacentHTML('beforeend', data.map((inv, i) => renderItem(inv, startIndex + i)).join(''));
    }
    
    function renderItem(inv, index) {
        const isProcessed = inv.is_processed;
        const isIngreso = inv.type === 'ingreso';
        const borderClass = isProcessed ? 'list-card-success' : 'list-card-warning';
        const statusBadge = isProcessed 
            ? '<span class="badge-status badge-status-success">Procesada</span>'
            : '<span class="badge-status badge-status-warning">Pendiente</span>';
        const typeBadge = isIngreso
            ? '<span class="badge-status badge-status-success"><i class="ki-outline ki-arrow-up"></i> Ingreso</span>'
            : '<span class="badge-status badge-status-danger"><i class="ki-outline ki-arrow-down"></i> Gasto</span>';
        
        const tagLabel = tags[inv.tag] || inv.tag;
        
        return `
            <div class="list-card ${borderClass}">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <span class="fw-semibold">${escapeHtml(inv.original_name || inv.filename)}</span>
                        ${typeBadge}
                        ${statusBadge}
                    </div>
                    <div class="list-card-meta">
                        <span>
                            <i class="ki-outline ki-tag"></i>
                            ${escapeHtml(tagLabel)}
                        </span>
                        <span>
                            <i class="ki-outline ki-calendar"></i>
                            ${formatDate(inv.created_at)}
                        </span>
                    </div>
                </div>
                <div class="list-card-actions">
                    <a href="/api/file-download?type=advisory_invoice&id=${inv.id}"
                       target="_blank"
                       class="btn-icon"
                       title="Ver archivo">
                        <i class="ki-outline ki-eye"></i>
                    </a>
                </div>
            </div>`;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    
    function updateResultsCount() {
        if (totalInvoices === 0) {
            resultsCount.innerHTML = 'No hay resultados';
        } else {
            const showing = Math.min(currentPage * 20, totalInvoices);
            resultsCount.innerHTML = `Mostrando <strong>1-${showing}</strong> de <strong>${totalInvoices}</strong>`;
        }
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando facturas...</span>
            </div>`;
    }
    
    function showLoadingMore() {
        listContainer.insertAdjacentHTML('beforeend', `
            <div class="loading-state" id="loading-more">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando más...</span>
            </div>
        `);
    }
    
    function removeLoadingMore() {
        document.getElementById('loading-more')?.remove();
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-disconnect text-danger"></i>
                </div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadAdvisoryInvoices()">
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
    
    window.filterAdvisoryInvoices = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchQuery = query.trim();
            resetAndLoad();
        }, 300);
    };
    
    window.reloadAdvisoryInvoices = () => resetAndLoad();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<?php endif; ?>