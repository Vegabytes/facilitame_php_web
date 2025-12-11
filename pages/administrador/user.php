<?php
$scripts = ["bold-submit"];
?>

<div class="salesrep-detail-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <div class="salesrep-detail-layout" style="flex: 1; display: flex; gap: 1.5rem; min-height: 0;">
        
        <!-- SIDEBAR COMERCIAL -->
        <aside class="salesrep-sidebar" style="width: 320px; flex-shrink: 0; display: flex; flex-direction: column;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column;">
                <div class="card-body" style="flex: 1; overflow-y: auto;">
                    
                    <!-- Avatar y datos principales -->
                    <div class="customer-profile">
                        <div class="customer-avatar">
                            <?php if (!empty($sales_rep["profile_picture"])) : ?>
                                <img src="<?php echo MEDIA_DIR . "/" . $sales_rep["profile_picture"] ?>"
                                     alt="Foto de perfil" loading="lazy">
                            <?php else : ?>
                                <img src="assets/media/bold/profile-default.jpg"
                                     alt="Foto de perfil por defecto" loading="lazy">
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="customer-name">
                            <?php secho($sales_rep["name"] . " " . $sales_rep["lastname"]); ?>
                        </h3>
                        
                        <span class="badge-status badge-status-info">
                            <i class="ki-outline ki-briefcase"></i>
                            <?php echo display_role($sales_rep["role_name"]); ?>
                        </span>
                        
                        <div class="customer-stat">
                            <span class="customer-stat-value"><?php echo count($customers); ?></span>
                            <span class="customer-stat-label">Clientes asignados</span>
                        </div>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <!-- Información del comercial -->
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-information-5"></i>
                            Información del comercial
                        </h6>
                        
                        <dl class="customer-details">
                            
                            <!-- ID Usuario -->
                            <div class="customer-detail-row">
                                <dt>ID de usuario</dt>
                                <dd>
                                    <span class="badge-status badge-status-primary">#<?php echo $sales_rep["id"]; ?></span>
                                </dd>
                            </div>
                            
                            <!-- Email -->
                            <div class="customer-detail-row">
                                <dt>Email</dt>
                                <dd>
                                    <a href="mailto:<?php secho($sales_rep['email']); ?>" class="customer-email">
                                        <?php secho($sales_rep["email"]); ?>
                                    </a>
                                </dd>
                            </div>
                            
                            <!-- Teléfono -->
                            <?php if (!empty($sales_rep["phone"])): ?>
                            <div class="customer-detail-row">
                                <dt>Teléfono</dt>
                                <dd>
                                    <a href="tel:<?php secho($sales_rep['phone']); ?>" class="customer-phone">
                                        <?php secho($sales_rep["phone"]); ?>
                                    </a>
                                </dd>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Código comercial -->
                            <div class="customer-detail-row">
                                <dt>Código comercial</dt>
                                <dd>
                                    <a href="#" class="copy salesrep-code">
                                        <?php secho($sales_rep["code"]); ?>
                                    </a>
                                </dd>
                            </div>
                            
                            <!-- NIF/CIF -->
                            <?php if (!empty($sales_rep["nif_cif"])): ?>
                            <div class="customer-detail-row">
                                <dt>NIF / CIF</dt>
                                <dd><?php secho($sales_rep["nif_cif"]); ?></dd>
                            </div>
                            <?php endif; ?>
                            
                        </dl>
                    </div>
                    
                </div>
                
                <!-- Acciones -->
                <div class="card-footer" style="flex-shrink: 0; padding: 1rem; border-top: 1px solid var(--f-border);">
                    <div class="d-flex gap-2 flex-column">
                        <a href="mailto:<?php secho($sales_rep['email']); ?>" class="btn btn-sm btn-light-primary">
                            <i class="ki-outline ki-sms me-1"></i>
                            Enviar email
                        </a>
                        <button class="btn btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#modal-delete-salesrep">
                            <i class="ki-outline ki-trash me-1"></i>
                            Eliminar comercial
                        </button>
                    </div>
                </div>
            </div>
        </aside>
        <!-- /SIDEBAR COMERCIAL -->
        
        <!-- CONTENIDO PRINCIPAL: TABS -->
        <main class="salesrep-main" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                
                <!-- Header con tabs -->
                <div class="card-header" style="flex-shrink: 0; padding: 0;">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-overview" 
                                    type="button"
                                    role="tab"
                                    aria-selected="true">
                                <i class="ki-outline ki-user"></i>
                                General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-customers" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-people"></i>
                                Clientes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-excluded" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-minus-circle"></i>
                                Servicios excluidos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-commissions" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-dollar"></i>
                                Comisiones
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Body con contenido de tabs -->
                <div class="tab-content" style="flex: 1; overflow-y: auto; min-height: 0;">
                    
                    <!-- Tab: General -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <h6 class="details-section-title">
                                    <i class="ki-outline ki-user-edit"></i>
                                    Detalles del comercial
                                </h6>
                                
                                <form action="api/sales-rep-update" data-reload="1">
                                    <input type="hidden" name="sales_rep_id" readonly value="<?php echo $sales_rep["id"] ?>">
                                    
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Nombre</label>
                                            <input type="text" name="name" required class="form-control" value="<?php secho($sales_rep["name"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Apellidos</label>
                                            <input type="text" name="lastname" required class="form-control" value="<?php secho($sales_rep["lastname"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" required class="form-control" value="<?php secho($sales_rep["email"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Teléfono</label>
                                            <input type="text" name="phone" required class="form-control" value="<?php secho($sales_rep["phone"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">NIF / CIF</label>
                                            <input type="text" name="nif_cif" required class="form-control" value="<?php secho($sales_rep["nif_cif"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Código</label>
                                            <input type="text" name="code" maxlength="10" required class="form-control salesrep-code-input" value="<?php secho($sales_rep["code"]); ?>"/>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">
                                                Nueva contraseña 
                                                <span class="text-muted">(dejar vacío para no cambiar)</span>
                                            </label>
                                            <input type="text" name="new_password" class="form-control" placeholder="Nueva contraseña"/>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary bold-submit">
                                            <i class="ki-outline ki-check me-1"></i>
                                            Guardar cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Clientes -->
                    <div class="tab-pane fade" id="tab-customers" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <h6 class="details-section-title">
                                    <i class="ki-outline ki-people"></i>
                                    Clientes asignados
                                </h6>
                                
                                <!-- Buscador -->
                                <div class="search-box mb-4">
                                    <i class="ki-outline ki-magnifier"></i>
                                    <input type="text" class="form-control" placeholder="Buscar clientes..." id="datatables-sales-rep-customers-search">
                                </div>
                                
                                <?php if (empty($customers)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="ki-outline ki-people"></i>
                                        </div>
                                        <div class="empty-state-title">Sin clientes asignados</div>
                                        <p class="empty-state-text">Este comercial aún no tiene clientes asignados</p>
                                    </div>
                                <?php else: ?>
                                    <div class="tab-list-container" id="salesrep-customers-list">
                                        <?php foreach ($customers as $c): ?>
                                            <div class="list-card" data-customer-id="<?php echo $c["id"]; ?>">
                                                <div class="list-card-content">
                                                    <div class="list-card-title">
                                                        <a href="customer?id=<?php echo $c['id']; ?>" class="list-card-customer">
                                                            <?php secho($c["name"] . " " . $c["lastname"]); ?>
                                                        </a>
                                                        <span class="badge-status badge-status-primary">#<?php echo $c["id"]; ?></span>
                                                    </div>
                                                    <div class="list-card-meta">
                                                        <span>
                                                            <i class="ki-outline ki-sms"></i>
                                                            <?php secho($c["email"]); ?>
                                                        </span>
                                                        <?php if (!empty($c["phone"])): ?>
                                                        <span>
                                                            <i class="ki-outline ki-phone"></i>
                                                            <?php secho($c["phone"]); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                        <span class="badge-status badge-status-success">
                                                            <i class="ki-outline ki-check-circle"></i>
                                                            <?php echo $c["services_number"]; ?> servicios
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="list-card-actions">
                                                    <a href="customer?id=<?php echo $c['id']; ?>" 
                                                       class="btn-icon" 
                                                       title="Ver cliente">
                                                        <i class="ki-outline ki-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Servicios excluidos -->
                    <div class="tab-pane fade" id="tab-excluded" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="alert alert-warning mb-4">
                                <i class="ki-outline ki-information-2 me-2"></i>
                                <strong>Importante:</strong> Los servicios marcados NO estarán disponibles para clientes que accedan con el código de este comercial.
                            </div>
                            
                            <form action="api/sales-rep-update-excluded-services" data-reload="1">
                                <input type="hidden" readonly name="sales_rep_id" value="<?php echo $sales_rep["id"] ?>">
                                
                                <div class="excluded-services-list">
                                    <?php foreach ($services as $service) : ?>
                                        <?php $checked = (in_array($service["id"], $excluded_services)) ? "checked" : "" ?>
                                        <div class="excluded-service-item <?php echo $checked ? 'excluded' : ''; ?>">
                                            <label class="form-check">
                                                <input class="form-check-input" name="category_id[]" value="<?php echo $service["id"] ?>" type="checkbox" <?php echo $checked ?>>
                                                <span class="form-check-label">
                                                    <?php secho($service["name"]); ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary bold-submit">
                                        <i class="ki-outline ki-check me-1"></i>
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tab: Comisiones -->
                    <div class="tab-pane fade" id="tab-commissions" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="ki-outline ki-dollar"></i>
                                </div>
                                <div class="empty-state-title">Sección de comisiones</div>
                                <p class="empty-state-text">Funcionalidad en desarrollo</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </main>
        <!-- /CONTENIDO PRINCIPAL -->
        
    </div>
    
</div>

<!-- Modal: Eliminar comercial -->
<div class="modal fade" id="modal-delete-salesrep" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar comercial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="api/sales-rep-delete" data-reload="0" data-redirect="salesreps" data-confirm-message="¿Estás seguro?<br>Esta acción no se puede deshacer">
                <input type="hidden" name="sales_rep_id" value="<?php echo $sales_rep["id"] ?>">
                
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="ki-outline ki-information-2 me-2"></i>
                        <strong>Atención:</strong> Esta acción eliminará permanentemente el comercial y no se puede deshacer.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger bold-submit">Eliminar comercial</button>
                </div>
            </form>
        </div>
    </div>
</div>

