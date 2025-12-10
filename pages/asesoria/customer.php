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
                        <div class="tab-list-container">
                            <?php if (empty($invoices)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="ki-outline ki-document"></i></div>
                                    <div class="empty-state-title">No hay facturas</div>
                                    <p class="empty-state-text">Este cliente no ha subido facturas</p>
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
                                            <button class="btn-icon btn-light-success" title="Marcar procesada" onclick="markProcessed(<?php echo $inv['id']; ?>, this)">
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