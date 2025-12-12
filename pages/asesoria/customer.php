<?php $scripts = []; ?>

<div class="customer-detail-page">
    
    <div class="customer-detail-layout">
        
        <!-- SIDEBAR CLIENTE -->
        <aside class="customer-sidebar">
            <div class="card">
                <div class="card-body">
                    
                    <!-- Avatar y datos principales -->
                    <div class="customer-profile">
                        <div class="customer-avatar">
                            <?php if (!empty($customer["profile_picture"])) : ?>
                                <img src="<?php echo MEDIA_DIR . "/" . $customer["profile_picture"] ?>" alt="Foto de perfil" loading="lazy">
                            <?php else : ?>
                                <img src="/assets/media/bold/profile-default.jpg" alt="Foto de perfil" loading="lazy">
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="customer-name">
                            <?php secho($customer["name"] . " " . $customer["lastname"]); ?>
                        </h3>
                        
                        <span class="badge-status badge-status-info">
                            <i class="ki-outline ki-profile-circle"></i>
                            <?php echo display_role($customer["role_name"] ?? 'cliente'); ?>
                        </span>
                        
                        <!-- Stats -->
                        <div class="customer-stats">
                            <div class="customer-stat-item">
                                <span class="customer-stat-value"><?php echo count($appointments ?? []); ?></span>
                                <span class="customer-stat-label">Citas</span>
                            </div>
                            <div class="customer-stat-item">
                                <span class="customer-stat-value"><?php echo count($invoices ?? []); ?></span>
                                <span class="customer-stat-label">Facturas</span>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <!-- Información del cliente -->
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-information-5"></i>
                            Información del cliente
                        </h6>
                        
                        <dl class="customer-details">
                            <div class="customer-detail-row">
                                <dt>ID</dt>
                                <dd><span class="badge-status badge-status-primary">#<?php echo $customer["id"]; ?></span></dd>
                            </div>
                            
                            <div class="customer-detail-row">
                                <dt>Email</dt>
                                <dd><a href="mailto:<?php secho($customer['email']); ?>" class="customer-email"><?php secho($customer["email"]); ?></a></dd>
                            </div>
                            
                            <?php if (!empty($customer["phone"])): ?>
                            <div class="customer-detail-row">
                                <dt>Teléfono</dt>
                                <dd><a href="tel:<?php secho($customer['phone']); ?>" class="customer-phone"><?php secho($customer["phone"]); ?></a></dd>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($customer["nif_cif"])): ?>
                            <div class="customer-detail-row">
                                <dt>NIF/CIF</dt>
                                <dd><?php secho($customer["nif_cif"]); ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <div class="customer-detail-row">
                                <dt>Email verificado</dt>
                                <dd>
                                    <?php if (!empty($customer["email_verified_at"])) : ?>
                                        <span class="badge-status badge-status-success"><i class="ki-outline ki-check-circle"></i> Verificado</span>
                                    <?php else : ?>
                                        <span class="badge-status badge-status-warning"><i class="ki-outline ki-time"></i> Pendiente</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            
                            <div class="customer-detail-row">
                                <dt>Fecha de alta</dt>
                                <dd><i class="ki-outline ki-calendar text-muted"></i> <?php echo !empty($customer["created_at"]) ? fdate($customer["created_at"]) : '-'; ?></dd>
                            </div>
                        </dl>
                    </div>
                    
                </div>
                
                <!-- Acciones rápidas -->
                <div class="card-footer customer-actions">
                    <a href="mailto:<?php secho($customer['email']); ?>" class="btn btn-sm btn-light-primary flex-fill">
                        <i class="ki-outline ki-sms me-1"></i> Email
                    </a>
                    <?php if (!empty($customer["phone"])): ?>
                    <a href="tel:<?php secho($customer['phone']); ?>" class="btn btn-sm btn-light-success flex-fill">
                        <i class="ki-outline ki-phone me-1"></i> Llamar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
        
        <!-- CONTENIDO PRINCIPAL CON TABS -->
        <main class="customer-main">
            <div class="card">
                
                <!-- Tabs -->
                <ul class="nav nav-tabs nav-line-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-citas" type="button">
                            <i class="ki-outline ki-calendar"></i>
                            <span>Citas</span>
                            <span class="badge-tab"><?php echo count($appointments ?? []); ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-facturas" type="button">
                            <i class="ki-outline ki-document"></i>
                            <span>Facturas</span>
                            <span class="badge-tab"><?php echo count($invoices ?? []); ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-comunicados" type="button">
                            <i class="ki-outline ki-notification"></i>
                            <span>Comunicados</span>
                            <span class="badge-tab"><?php echo count($communications ?? []); ?></span>
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    
                    <!-- TAB CITAS -->
                    <div class="tab-pane fade show active" id="tab-citas">
                        <div class="tab-list-container">
                            <?php if (empty($appointments)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="ki-outline ki-calendar"></i></div>
                                    <div class="empty-state-title">No hay citas</div>
                                    <p class="empty-state-text">Este cliente no tiene citas registradas</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($appointments as $apt): ?>
                                    <?php
                                        $statusMap = ['solicitado' => 'warning', 'agendado' => 'info', 'finalizado' => 'success', 'cancelado' => 'danger'];
                                        $statusClass = $statusMap[$apt['status']] ?? 'muted';
                                        $typeLabels = [
                                            'llamada' => '<i class="ki-outline ki-phone me-1"></i>Llamada', 
                                            'reunion_presencial' => '<i class="ki-outline ki-geolocation me-1"></i>Presencial', 
                                            'reunion_virtual' => '<i class="ki-outline ki-screen me-1"></i>Virtual'
                                        ];
                                        $typeBadge = $typeLabels[$apt['type']] ?? $apt['type'];
                                        $deptLabels = ['contabilidad' => 'Contabilidad', 'fiscalidad' => 'Fiscalidad', 'laboral' => 'Laboral', 'gestion' => 'Gestión', 'otro' => 'Otro'];
                                        $deptLabel = $deptLabels[$apt['department']] ?? '';
                                        $dateStr = $apt['scheduled_date'] ? fdate($apt['scheduled_date']) : ($apt['created_at'] ? 'Creada: ' . fdate($apt['created_at']) : 'Sin agendar');
                                    ?>
                                    <div class="list-card list-card-<?php echo $statusClass; ?>">
                                        <div class="list-card-content">
                                            <div class="list-card-title">
                                                <a href="/appointment?id=<?php echo $apt['id']; ?>" class="list-card-link">Cita #<?php echo $apt['id']; ?></a>
                                                <span class="badge-status badge-status-<?php echo $statusClass; ?>"><?php echo ucfirst($apt['status']); ?></span>
                                            </div>
                                            <div class="list-card-meta">
                                                <span class="badge-status badge-status-light"><?php echo $typeBadge; ?></span>
                                                <?php if ($deptLabel): ?><span class="badge-status badge-status-primary"><?php echo $deptLabel; ?></span><?php endif; ?>
                                                <span><i class="ki-outline ki-calendar"></i> <?php echo $dateStr; ?></span>
                                            </div>
                                            <?php if (!empty($apt['reason'])): ?>
                                                <div class="list-card-desc"><?php secho(mb_strimwidth($apt['reason'], 0, 100, '...')); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="list-card-actions">
                                            <a href="/appointment?id=<?php echo $apt['id']; ?>" class="btn-icon btn-icon-info" title="Ver cita"><i class="ki-outline ki-eye"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- TAB FACTURAS -->
                    <div class="tab-pane fade" id="tab-facturas">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-sm btn-primary-facilitame" data-bs-toggle="modal" data-bs-target="#modal_upload_customer_invoices">
                                <i class="ki-outline ki-cloud-add me-1"></i>Subir Facturas
                            </button>
                        </div>
                        <div class="tab-list-container">
                            <?php if (empty($invoices)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="ki-outline ki-document"></i></div>
                                    <div class="empty-state-title">No hay facturas</div>
                                    <p class="empty-state-text">Este cliente no ha subido facturas</p>
                                    <button type="button" class="btn btn-sm btn-primary-facilitame mt-3" data-bs-toggle="modal" data-bs-target="#modal_upload_customer_invoices">
                                        <i class="ki-outline ki-cloud-add me-1"></i>Subir primera factura
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <?php
                                        $filename = $inv['original_name'] ?? $inv['filename'];
                                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                        $iconMap = ['pdf' => 'ki-file-added', 'jpg' => 'ki-picture', 'jpeg' => 'ki-picture', 'png' => 'ki-picture'];
                                        $fileIcon = $iconMap[$ext] ?? 'ki-document';
                                    ?>
                                    <div class="list-card list-card-<?php echo $inv['is_processed'] ? 'success' : 'danger'; ?>">
                                        <div class="list-card-content">
                                            <div class="list-card-title">
                                                <span class="list-card-file">
                                                    <i class="ki-outline <?php echo $fileIcon; ?>"></i>
                                                    <?php secho($filename); ?>
                                                </span>
                                                <span class="badge-status badge-status-<?php echo $inv['is_processed'] ? 'light' : 'warning'; ?>">
                                                    <?php echo $inv['is_processed'] ? 'Procesada' : 'Pendiente'; ?>
                                                </span>
                                            </div>
                                            <div class="list-card-meta">
                                                <?php if (!empty($inv['tag'])): ?><span><i class="ki-outline ki-tag"></i> <?php secho($inv['tag']); ?></span><?php endif; ?>
                                                <span><i class="ki-outline ki-calendar"></i> <?php echo fdate($inv['created_at']); ?></span>
                                            </div>
                                            <?php if (!empty($inv['notes'])): ?>
                                                <div class="list-card-desc"><?php secho(mb_strimwidth($inv['notes'], 0, 80, '...')); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="list-card-actions">
                                            <a href="/api/file-download?type=advisory_invoice&id=<?php echo $inv['id']; ?>" class="btn-icon" title="Descargar" target="_blank">
                                                <i class="ki-outline ki-folder-down"></i>
                                            </a>
                                            <?php if (!$inv['is_processed']): ?>
                                            <button class="btn-icon btn-icon-success" title="Marcar procesada" onclick="markProcessed(<?php echo $inv['id']; ?>, this)">
                                                <i class="ki-outline ki-check"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- TAB COMUNICADOS -->
                    <div class="tab-pane fade" id="tab-comunicados">
                        <div class="tab-list-container">
                            <?php if (empty($communications)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="ki-outline ki-notification"></i></div>
                                    <div class="empty-state-title">No hay comunicados</div>
                                    <p class="empty-state-text">No se han enviado comunicados a este cliente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($communications as $com): ?>
                                    <?php $impClass = ['alta' => 'danger', 'media' => 'warning', 'baja' => 'info'][$com['importance']] ?? 'primary'; ?>
                                    <div class="list-card list-card-<?php echo $impClass; ?>">
                                        <div class="list-card-content">
                                            <div class="list-card-title">
                                                <?php secho($com['subject']); ?>
                                                <span class="badge-status badge-status-<?php echo $com['is_read'] ? 'light' : 'warning'; ?>">
                                                    <?php echo $com['is_read'] ? '<i class="ki-outline ki-check"></i> Leído' : 'No leído'; ?>
                                                </span>
                                            </div>
                                            <div class="list-card-meta">
                                                <span><i class="ki-outline ki-calendar"></i> <?php echo fdate($com['created_at']); ?></span>
                                                <?php if ($com['read_at']): ?><span><i class="ki-outline ki-eye"></i> Leído: <?php echo fdate($com['read_at']); ?></span><?php endif; ?>
                                            </div>
                                            <div class="list-card-desc"><?php secho(mb_strimwidth(strip_tags($com['message']), 0, 120, '...')); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </main>
        
    </div>
</div>

<script>
function markProcessed(id, btn) {
    const icon = btn.querySelector('i');
    const originalClass = icon.className;
    icon.className = 'spinner-border spinner-border-sm';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('invoice_id', id);
    
    fetch('/api/advisory-mark-invoice-processed', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        if (result.status === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Factura procesada',
                text: 'La factura ha sido marcada como procesada',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            
            const card = btn.closest('.list-card');
            card.classList.remove('list-card-danger');
            card.classList.add('list-card-success');
            const badge = card.querySelector('.badge-status-warning');
            if (badge) { 
                badge.classList.remove('badge-status-warning'); 
                badge.classList.add('badge-status-light'); 
                badge.textContent = 'Procesada'; 
            }
            btn.remove();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Error al procesar la factura'
            });
            icon.className = originalClass;
            btn.disabled = false;
        }
    })
    .catch(() => { 
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión'
        });
        icon.className = originalClass; 
        btn.disabled = false; 
    });
}
</script>

<?php
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

<!-- Modal: Subir Facturas -->
<div class="modal fade" id="modal_upload_customer_invoices" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper" style="width: 48px; height: 48px; background: linear-gradient(135deg, #00c2cb 0%, #00a8b0 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="ki-outline ki-cloud-add" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Subir Facturas</h5>
                        <p class="text-muted fs-7 mb-0">Para <?php secho($customer["name"] . " " . $customer["lastname"]); ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_upload_customer_invoices" enctype="multipart/form-data">
                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                <div class="modal-body pt-4">

                    <!-- Upload zone -->
                    <div class="mb-4">
                        <div class="upload-drop-zone" id="upload-zone-customer" style="position: relative; border: 2px dashed #d1d5db; border-radius: 16px; padding: 2rem 1.5rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s ease;">
                            <input type="file"
                                   name="invoice_files[]"
                                   id="customer-invoice-files-input"
                                   style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;"
                                   multiple
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <div style="width: 64px; height: 64px; margin: 0 auto 1rem; background: linear-gradient(135deg, #00c2cb 0%, #00a8b0 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(0, 194, 203, 0.25);">
                                <i class="ki-outline ki-folder-up" style="font-size: 1.75rem; color: white;"></i>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 1rem;">
                                <span style="font-size: 1rem; font-weight: 600; color: #1e293b;">Arrastra tus archivos aquí</span>
                                <span style="font-size: 0.875rem; color: #64748b;">o <span style="color: #00c2cb; font-weight: 500; text-decoration: underline;">haz click para seleccionar</span></span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="background: white; border: 1px solid #e2e8f0; padding: 0.25rem 0.625rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #64748b;">PDF</span>
                                <span style="background: white; border: 1px solid #e2e8f0; padding: 0.25rem 0.625rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #64748b;">JPG</span>
                                <span style="background: white; border: 1px solid #e2e8f0; padding: 0.25rem 0.625rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #64748b;">PNG</span>
                                <span style="color: #cbd5e1;">•</span>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Máx. 10MB por archivo</span>
                            </div>
                        </div>
                        <div id="customer-files-preview" style="margin-top: 1rem;"></div>
                    </div>

                    <!-- Tipo y Etiqueta -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <div style="display: flex; gap: 0.75rem;">
                                <label style="flex: 1; cursor: pointer;">
                                    <input type="radio" name="type" value="gasto" checked style="display: none;">
                                    <span class="type-content-gasto" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 10px; border: 2px solid #ef4444; font-weight: 500; transition: all 0.2s ease; background: #fef2f2; color: #dc2626;">
                                        <i class="ki-outline ki-arrow-down"></i>
                                        <span>Gasto</span>
                                    </span>
                                </label>
                                <label style="flex: 1; cursor: pointer;">
                                    <input type="radio" name="type" value="ingreso" style="display: none;">
                                    <span class="type-content-ingreso" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 10px; border: 2px solid #e5e7eb; font-weight: 500; transition: all 0.2s ease; background: white; color: #6b7280;">
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
                    <button type="submit" class="btn btn-primary" id="btn-submit-customer-invoices" disabled>
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

    var selectedFiles = [];
    var uploadZone = document.getElementById('upload-zone-customer');
    var fileInput = document.getElementById('customer-invoice-files-input');
    var filesPreview = document.getElementById('customer-files-preview');
    var form = document.getElementById('form_upload_customer_invoices');
    var submitBtn = document.getElementById('btn-submit-customer-invoices');
    var tagSelect = form.querySelector('select[name="tag"]');
    var modalEl = document.getElementById('modal_upload_customer_invoices');
    var modal = null;

    // Type selector styling
    var typeRadios = form.querySelectorAll('input[name="type"]');
    typeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            var gastoSpan = form.querySelector('.type-content-gasto');
            var ingresoSpan = form.querySelector('.type-content-ingreso');

            if (this.value === 'gasto') {
                gastoSpan.style.border = '2px solid #ef4444';
                gastoSpan.style.background = '#fef2f2';
                gastoSpan.style.color = '#dc2626';
                ingresoSpan.style.border = '2px solid #e5e7eb';
                ingresoSpan.style.background = 'white';
                ingresoSpan.style.color = '#6b7280';
            } else {
                ingresoSpan.style.border = '2px solid #22c55e';
                ingresoSpan.style.background = '#f0fdf4';
                ingresoSpan.style.color = '#16a34a';
                gastoSpan.style.border = '2px solid #e5e7eb';
                gastoSpan.style.background = 'white';
                gastoSpan.style.color = '#6b7280';
            }
        });
    });

    // Drag & drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
        uploadZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(function(eventName) {
        uploadZone.addEventListener(eventName, function() {
            uploadZone.style.borderColor = '#00c2cb';
            uploadZone.style.background = 'rgba(0, 194, 203, 0.08)';
        });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        uploadZone.addEventListener(eventName, function() {
            uploadZone.style.borderColor = '#d1d5db';
            uploadZone.style.background = '#f8fafc';
        });
    });

    uploadZone.addEventListener('drop', function(e) {
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    tagSelect.addEventListener('change', updateSubmitButton);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitInvoices();
    });

    modalEl.addEventListener('shown.bs.modal', function() {
        modal = bootstrap.Modal.getInstance(modalEl);
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        resetForm();
    });

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
        if (selectedFiles.length === 0) {
            filesPreview.innerHTML = '';
            return;
        }

        var html = '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">' +
            '<span style="font-size: 0.875rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.5rem;">' +
                '<i class="ki-outline ki-document" style="color: #00c2cb;"></i>' +
                selectedFiles.length + ' archivo' + (selectedFiles.length > 1 ? 's' : '') + ' seleccionado' + (selectedFiles.length > 1 ? 's' : '') +
            '</span>' +
            '<button type="button" onclick="clearAllCustomerFiles()" style="background: none; border: none; color: #ef4444; font-size: 0.8125rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px;">' +
                '<i class="ki-outline ki-trash me-1"></i>Eliminar todos' +
            '</button>' +
        '</div>' +
        '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';

        selectedFiles.forEach(function(file, index) {
            var ext = file.name.split('.').pop().toLowerCase();
            var isPdf = ext === 'pdf';

            html += '<div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 10px;">' +
                '<div style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: ' + (isPdf ? '#fef2f2' : '#f0fdf4') + '; color: ' + (isPdf ? '#ef4444' : '#22c55e') + ';">' +
                    '<i class="ki-outline ' + (isPdf ? 'ki-document' : 'ki-picture') + '" style="font-size: 1.25rem;"></i>' +
                '</div>' +
                '<div style="flex: 1; min-width: 0;">' +
                    '<div style="font-size: 0.875rem; font-weight: 500; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + escapeHtml(file.name) + '</div>' +
                    '<div style="font-size: 0.75rem; color: #9ca3af;">' + formatFileSize(file.size) + '</div>' +
                '</div>' +
                '<button type="button" onclick="removeCustomerFile(' + index + ')" style="width: 28px; height: 28px; border-radius: 6px; border: none; background: #f3f4f6; color: #6b7280; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Eliminar">' +
                    '<i class="ki-outline ki-cross"></i>' +
                '</button>' +
            '</div>';
        });

        html += '</div>';
        filesPreview.innerHTML = html;
    }

    window.removeCustomerFile = function(index) {
        selectedFiles.splice(index, 1);
        var dt = new DataTransfer();
        selectedFiles.forEach(function(file) { dt.items.add(file); });
        fileInput.files = dt.files;
        renderFilesPreview();
        updateSubmitButton();
    };

    window.clearAllCustomerFiles = function() {
        selectedFiles = [];
        fileInput.value = '';
        renderFilesPreview();
        updateSubmitButton();
    };

    function updateSubmitButton() {
        var hasFiles = selectedFiles.length > 0;
        var hasTag = tagSelect.value !== '';
        submitBtn.disabled = !(hasFiles && hasTag);
    }

    function submitInvoices() {
        if (selectedFiles.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin archivos', text: 'Selecciona al menos un archivo' });
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...';

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
                if (modal) modal.hide();
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al subir facturas' });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ki-outline ki-cloud-add me-1"></i>Subir Facturas';
        });
    }

    function resetForm() {
        selectedFiles = [];
        form.reset();
        filesPreview.innerHTML = '';
        updateSubmitButton();

        // Reset type selector styling
        var gastoSpan = form.querySelector('.type-content-gasto');
        var ingresoSpan = form.querySelector('.type-content-ingreso');
        gastoSpan.style.border = '2px solid #ef4444';
        gastoSpan.style.background = '#fef2f2';
        gastoSpan.style.color = '#dc2626';
        ingresoSpan.style.border = '2px solid #e5e7eb';
        ingresoSpan.style.background = 'white';
        ingresoSpan.style.color = '#6b7280';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>