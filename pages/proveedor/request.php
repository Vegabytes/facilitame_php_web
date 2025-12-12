<?php
$scripts = [
    "request",
    "chat"
];

$isDeleted = ((int)$request["status_id"] === 9 || !is_null($request["deleted_at"]));
?>

<?php if ($isDeleted) : ?>
<!-- Banner solicitud eliminada -->
<div class="alert alert-light-danger d-flex align-items-center mb-3" style="border-radius: var(--f-radius);">
    <i class="ki-outline ki-trash fs-2 me-3 text-danger"></i>
    <div>
        <strong>Solicitud eliminada</strong> — El chat está deshabilitado. Puedes seguir usando los comentarios internos.
    </div>
</div>
<?php endif; ?>

<div class="request-page" style="height: calc(100vh - <?php echo $isDeleted ? '220' : '160'; ?>px); overflow: hidden;">
    
    <div class="request-layout" style="height: 100%; align-items: stretch;">
        
        <!-- COLUMNA 1: Detalles -->
        <aside class="request-sidebar" style="height: 100%; overflow-y: auto;">
            <?php require COMPONENTS_DIR . "/request-details-proveedor.php" ?>
        </aside>
        
        <!-- COLUMNA 2: Tabs (Ofertas/Documentos/Comentarios) -->
        <section class="request-tabs-section" style="display: flex; flex-direction: column; min-height: 0; height: 100%;">
            <div class="card dashboard-tabs-card" style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden; height: 100%;">
                
                <!-- Header con tabs -->
                <div class="card-header" style="flex-shrink: 0; padding: 0;">
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
                                    data-bs-target="#tab-comments" 
                                    type="button"
                                    role="tab" 
                                    aria-selected="false">
                                <i class="ki-outline ki-message-text"></i>
                                Comentarios
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Body con contenido de tabs -->
                <div class="tab-content" >
                    
                    <!-- Tab: Ofertas -->
                    <div class="tab-pane fade show active" id="tab-offers" role="tabpanel">
                        <?php require COMPONENTS_DIR . "/request-offers-proveedor.php" ?>
                    </div>
                    
                    <!-- Tab: Documentos -->
                    <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                        <?php require COMPONENTS_DIR . "/request-documents-proveedor.php" ?>
                    </div>
                    
                    <!-- Tab: Comentarios internos -->
                    <div class="tab-pane fade" id="tab-comments" role="tabpanel">
                        <div class="comments-container" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                            <!-- Textarea oculto requerido por request.js -->
                            <textarea id="provider-comments" style="display:none;"><?php echo htmlspecialchars($comments); ?></textarea>
                            
                            <div class="request-list-scroll" id="comments-list-container">
                                <?php if (empty(trim($comments))) : ?>
                                    <div class="empty-state empty-state-compact">
                                        <div class="empty-state-icon">
                                            <i class="ki-outline ki-message-text"></i>
                                        </div>
                                        <p class="empty-state-text">No hay comentarios internos</p>
                                    </div>
                                <?php else : ?>
                                    <div class="comments-content">
                                        <?php echo nl2br(htmlspecialchars($comments)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="comments-input" style="flex-shrink: 0; padding: 1rem 1.25rem; border-top: 1px solid var(--f-border);">
                                <div class="comments-input-row">
                                    <input type="text" 
                                           class="form-control" 
                                           id="provider-comment-input" 
                                           placeholder="Escribe un comentario interno...">
                                    <button class="btn btn-primary btn-sm" 
                                            type="button" 
                                            id="btn-add-provider-comment" 
                                            data-request-id="<?php echo $request["id"] ?>">
                                        <i class="ki-outline ki-send"></i>
                                    </button>
                                </div>
                                <div class="comments-notice">
                                    <i class="ki-outline ki-lock"></i>
                                    <span>Solo visible para el equipo interno</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </section>
        
        <!-- COLUMNA 3: Chat -->
        <section class="request-chat-section" style="display: flex; flex-direction: column; min-height: 0; height: 100%;">
            <div class="card chat-card" style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden;">
                
                <!-- Header -->
                <div class="card-header" style="flex-shrink: 0;">
                    <div class="card-title">
                        <i class="ki-outline ki-messages"></i>
                        <span>Chat con el cliente</span>
                    </div>
                </div>
                
                <!-- Body: Messages -->
                <div class="card-body chat-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                    <div id="chat-container" class="chat-messages-container" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                        <?php echo (build_messages($messages, $request["id"])); ?>
                    </div>
                </div>
                
                <!-- Footer: Input - Solo si NO está eliminada -->
                <?php if (!$isDeleted) : ?>
                <div class="card-footer chat-footer" style="flex-shrink: 0;">
                    <textarea 
                        id="chat-input" 
                        data-request-id="<?php echo $request["id"] ?>" 
                        class="form-control chat-textarea" 
                        rows="2" 
                        placeholder="Escribe tu mensaje aquí..."></textarea>
                    
                    <div class="chat-toolbar">
                        <div class="chat-notice">
                            <i class="ki-outline ki-information"></i>
                            <span>Mensaje visible para el cliente</span>
                        </div>
                        <button class="btn btn-primary btn-sm" type="button" id="btn-send-chat-message">
                            <i class="ki-outline ki-send"></i>
                            Enviar
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </section>
        
    </div>
    
</div>

<!-- MODALES - Fuera del layout para evitar problemas con overflow -->

<!-- Modal cargar oferta -->
<div class="modal fade" tabindex="-1" id="modal-offer-upload">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cargar Oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form action="#">
                    <div class="mb-4">
                        <label class="form-label">Título de la oferta</label>
                        <input type="text" class="form-control" id="offer_title" name="offer_title" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Archivo de la oferta</label>
                        <input id="offer_file" type="file" name="offer_file" class="form-control" accept="image/*,application/pdf,.docx" capture="camera" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea name="offer_content" id="offer-content-textarea" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" id="modal-offer-upload-close" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="modal-offer-upload-send" class="btn btn-primary" data-request-id="<?php echo $request["id"] ?>">Cargar Oferta</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal reagendar -->
<div class="modal fade" tabindex="-1" id="modal-reschedule">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reagendar revisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-reschedule">
                    <div class="mb-3">
                        <label class="form-label">Selecciona nueva fecha para revisión</label>
                        <input type="date" id="reschedule-date" name="reschedule_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <input type="hidden" name="request_id" value="<?php echo $request["id"] ?>">
                </form>
                <div class="alert alert-info d-flex align-items-start gap-2 mb-0" style="background: rgba(0, 194, 203, 0.1); border: 1px solid rgba(0, 194, 203, 0.3);">
                    <i class="ki-outline ki-notification-on text-primary fs-3 mt-1"></i>
                    <div>
                        <div class="fw-semibold text-dark">Notificación automática</div>
                        <div class="text-muted fs-7">Cuando llegue la fecha seleccionada, se notificará automáticamente al cliente, comercial y proveedor.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-reschedule-save">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal reactivar -->
<div class="modal fade" id="modal-request-reactivate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reanudar solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="request-reactivate-form">
                <input type="hidden" name="request_id" value="<?php echo $request['id'] ?>" readonly>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label">Indica el motivo de la reactivación</label>
                        <textarea class="form-control" name="reactivation_reason" id="reactivation-reason" rows="3" required minlength="10"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-request-reactivate-send" class="btn btn-primary" data-request-id="<?php echo $request['id'] ?>">Reanudar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal retirar oferta -->
<div class="modal fade" id="modal-offer-withdraw" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Retirar oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="ki-outline ki-information-3 text-warning" style="font-size: 4rem;"></i>
                </div>
                <p class="fs-5 fw-semibold mb-2">&iquest;Est&aacute;s seguro de que quieres retirar esta oferta?</p>
                <p class="text-muted mb-0">El cliente ya no podr&aacute; verla.</p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-withdraw">
                    <i class="ki-outline ki-cross-circle me-2"></i>Retirar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmar oferta (proveedor) -->
<div class="modal fade" id="modal-offer-confirm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper modal-icon-success">
                        <i class="ki-outline ki-check-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Confirmar oferta</h5>
                        <p class="text-muted fs-7 mb-0">La oferta pasará a estado "En curso"</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="offer-confirm-form">
                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                <input type="hidden" name="offer_id" id="modal-offer-confirm-offer-id">
                
                <div class="modal-body pt-4">
                    
                    <p class="mb-3">Vas a confirmar la oferta <strong id="modal-offer-confirm-offer-title"></strong>.</p>
                    
                    <div id="modal-offer-confirm-desc-wrapper" class="mb-4 d-none">
                        <p class="text-muted mb-2">Esta es su descripción:</p>
                        <div id="modal-offer-confirm-offer-content" class="p-3 bg-light rounded small"></div>
                    </div>
                    
                    <div class="info-box info-box-success mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">Indica el importe</span>
                            <span class="info-box-text">Este dato se usará para el seguimiento de la solicitud.</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Importe mensual total <span class="text-danger">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="ki-outline ki-euro input-icon"></i>
                            <input type="number" 
                                   class="form-control form-control-icon" 
                                   id="confirm-total-amount"
                                   name="total_amount" 
                                   step="0.01" 
                                   min="0"
                                   placeholder="Ej: 150.00"
                                   required>
                        </div>
                    </div>
                    
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" id="modal-offer-confirm-close" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-offer-confirm" class="btn btn-success">
                        <i class="ki-outline ki-check-circle me-1"></i>
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal activar oferta (proveedor) -->
<div class="modal fade" id="modal-offer-activate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper modal-icon-primary">
                        <i class="ki-outline ki-verify"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Activar oferta</h5>
                        <p class="text-muted fs-7 mb-0">Indica la fecha de vencimiento de la oferta</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="offer-activate-form">
                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                <input type="hidden" name="offer_id" id="modal-offer-activate-offer-id">
                
                <div class="modal-body pt-4">
                    
                    <p class="mb-4">¿Confirmas que quieres activar la oferta <strong id="modal-offer-activate-offer-title"></strong>?</p>
                    
                    <div class="info-box info-box-primary mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">Importante</span>
                            <span class="info-box-text">Al activar la oferta se generarán las comisiones correspondientes.</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Fecha de vencimiento <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control" 
                               id="activate-expires-at"
                               name="expires_at" 
                               min="<?= date('Y-m-d') ?>"
                               required>
                        <div class="form-text">Fecha en la que vence el servicio contratado</div>
                    </div>
                    
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-offer-activate" class="btn btn-primary">
                        <i class="ki-outline ki-verify me-1"></i>
                        Activar oferta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>