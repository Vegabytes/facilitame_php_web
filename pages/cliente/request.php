<?php
$scripts = [
    "request",
    "chat"
];
?>

<div class="request-page">
    
    <div class="request-layout">
        
        <!-- COLUMNA 1: Detalles -->
        <aside class="request-sidebar">
            <?php require COMPONENTS_DIR . "/request-details-cliente.php" ?>
        </aside>
        
        <?php if ((int)$request["status_id"] !== 9 && is_null($request["deleted_at"])) : ?>
        
        <!-- COLUMNA 2: Tabs (Ofertas/Documentos/Incidencias) -->
        <section class="request-tabs-section">
            <div class="card dashboard-tabs-card">
                
                <!-- Header con tabs -->
                <div class="card-header">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-offers" 
                                    type="button"
                                    role="tab" 
                                    aria-selected="true">
                                <i class="ki-outline ki-dollar"></i>
                                Ofertas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-docs" 
                                    type="button"
                                    role="tab" 
                                    aria-selected="false">
                                <i class="ki-outline ki-folder"></i>
                                Documentos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-incidents" 
                                    type="button"
                                    role="tab" 
                                    aria-selected="false">
                                <i class="ki-outline ki-information-2"></i>
                                Incidencias
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Body con contenido de tabs -->
                <div class="tab-content">
                    
                    <!-- Tab: Ofertas -->
                    <div class="tab-pane fade show active" id="tab-offers" role="tabpanel">
                        <?php require COMPONENTS_DIR . "/request-offers-cliente.php" ?>
                    </div>
                    
                    <!-- Tab: Documentos -->
                    <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                        <?php require COMPONENTS_DIR . "/request-documents-cliente.php" ?>
                    </div>
                    
                    <!-- Tab: Incidencias -->
                    <div class="tab-pane fade" id="tab-incidents" role="tabpanel">
                        <?php require COMPONENTS_DIR . "/request-incidents-cliente.php" ?>
                    </div>
                    
                </div>
                
            </div>
        </section>
        
        <!-- COLUMNA 3: Chat -->
        <section class="request-chat-section">
            <?php require COMPONENTS_DIR . "/request-chat.php" ?>
        </section>
        
        <?php endif; ?>
        
    </div>
    
</div>

<!-- Modal de incidencias -->
<div class="modal fade" tabindex="-1" id="modal-incident-report">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comunicar incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="api/incident-report" data-reload="1">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?php echo $request["id"] ?>">
                    
                    <div class="mb-4">
                        <label class="form-label">Tipo de incidencia</label>
                        <select name="incident_category_id" class="form-select">
                            <option value="1">General</option>
                        </select>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label">Detalles</label>
                        <textarea rows="4" name="incident_details" class="form-control" placeholder="Detalla la incidencia, por favor" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary bold-submit">
                        <span class="indicator-label">Enviar</span>
                        <span class="indicator-progress">Enviando...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de eliminación de solicitud -->
<div class="modal fade" tabindex="-1" id="modal-request-delete">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="api/request-delete" data-reload="0" data-redirect="home" data-confirm-message="¿Estás seguro?<br>Esta acción no se puede deshacer.">
                <input type="hidden" name="request_id" readonly value="<?php echo $request["id"] ?>">

                <div class="modal-body">
                    <label class="form-label">Indica el motivo por el que deseas eliminar la solicitud</label>
                    <textarea name="reason" class="form-control" required placeholder="Mínimo 15 caracteres" minlength="15" rows="4"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="bold-submit btn btn-danger">
                        <span class="indicator-label">Eliminar</span>
                        <span class="indicator-progress">Eliminando...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal aceptar oferta -->
<div class="modal fade" tabindex="-1" id="modal-offer-accept">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aceptar oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Vas a aceptar la oferta <strong id="modal-offer-accept-offer-title"></strong>.</p>
                <div class="mb-4" id="modal-offer-accept-desc-wrapper" style="display: none;">
                    <label class="form-label">Descripción:</label>
                    <div id="modal-offer-accept-offer-content" class="p-3 bg-light rounded"></div>
                </div>
                <input type="hidden" id="modal-offer-accept-offer-id">
                <input type="hidden" id="modal-offer-accept-request-id" value="<?= $request["id"] ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="modal-offer-accept-close" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-offer-accept" class="btn btn-success">
                    <i class="ki-outline ki-check-circle me-1"></i>
                    Aceptar oferta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal rechazar oferta -->
<div class="modal fade" tabindex="-1" id="modal-offer-reject">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Vas a rechazar la oferta <strong id="modal-offer-reject-offer-title"></strong>.</p>
                <div class="mb-4" id="modal-offer-reject-desc-wrapper" style="display: none;">
                    <label class="form-label">Descripción:</label>
                    <div id="modal-offer-reject-offer-content" class="p-3 bg-light rounded"></div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Motivo del rechazo (opcional)</label>
                    <textarea name="rejection_reason" id="modal-offer-reject-reason" class="form-control" rows="3" placeholder="Indica por qué rechazas esta oferta..."></textarea>
                </div>
                <input type="hidden" id="modal-offer-reject-offer-id">
                <input type="hidden" id="modal-offer-reject-request-id" value="<?= $request["id"] ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="modal-offer-reject-close" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-offer-reject" class="btn btn-danger">
                    <i class="ki-outline ki-cross-circle me-1"></i>
                    Rechazar oferta
                </button>
            </div>
        </div>
    </div>
</div>