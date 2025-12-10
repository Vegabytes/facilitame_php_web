<div class="card request-details-card">
    
    <!-- Header -->
    <div class="card-header">
        <div class="card-title">
            <span>Solicitud #<?php echo $request["id"] ?></span>
        </div>
        <div class="card-header-actions">
            <span class="badge-status badge-status-info"><?php echo $category["name"] ?></span>
            <?php if ((int)$request["status_id"] !== 9 && is_null($request["deleted_at"])) : ?>
                <button type="button" class="btn-icon btn-icon-sm" title="Modificar detalles">
                    <i class="ki-outline ki-pencil"></i>
                </button>
                <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" data-bs-toggle="modal" data-bs-target="#modal-request-delete" title="Eliminar">
                    <i class="ki-outline ki-trash"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Body -->
    <div class="card-body request-details-body">
        
        <!-- Sección: Detalles de la solicitud -->
        <div class="details-section">
            <h6 class="details-section-title">
                <i class="ki-outline ki-information-5"></i>
                Detalles de la solicitud
            </h6>
            
            <dl class="details-list">
                <?php foreach ($form_values as $fv) : ?>
                    <div class="details-item">
                        <dt><?php secho($fv["name"]) ?></dt>
                        <dd><?php secho($fv["value"]) ?></dd>
                    </div>
                <?php endforeach; ?>
                
                <div class="details-item">
                    <dt>Fecha de solicitud</dt>
                    <dd><?php echo fdate($request["request_date"]) ?></dd>
                </div>
                
                <div class="details-item">
                    <dt>Estado</dt>
                    <dd><?php print_request_status($request["id"]) ?></dd>
                </div>
                
                <?php if ((int)$request["status_id"] === 9 || !is_null($request["deleted_at"])) : ?>
                    <div class="details-item details-item-alert">
                        <dt class="text-danger">Motivo de eliminación</dt>
                        <dd><?php echo nl2br(htmlspecialchars($request["delete_reason"])) ?></dd>
                    </div>
                <?php endif; ?>
                
                <?php if ((int)$request["status_id"] === 10 && !empty($request["rescheduled_at"])) : ?>
                    <div class="details-item">
                        <dt>Fecha de reagendado</dt>
                        <dd><?php echo fdate($request["rescheduled_at"]) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
        
        <!-- Sección: Solicitante -->
        <div class="details-section">
            <h6 class="details-section-title">
                <i class="ki-outline ki-profile-circle"></i>
                Solicitante
            </h6>
            
            <dl class="details-list">
                <?php if (!empty($sales_rep)) : ?>
                    <div class="details-item details-item-highlight">
                        <dt class="text-primary">Comercial</dt>
                        <dd>
                            <a href="#" class="details-link"><?php echo ucwords($sales_rep["name"] . " " . $sales_rep["lastname"]) ?></a>
                        </dd>
                    </div>
                <?php endif; ?>
                
                <div class="details-item">
                    <dt>Nombre y apellidos</dt>
                    <dd><?php echo ucwords($requestor["name"] . " " . $requestor["lastname"]) ?></dd>
                </div>
                
                <div class="details-item">
                    <dt>CIF/NIF</dt>
                    <dd><?php secho($requestor["nif_cif"]) ?></dd>
                </div>
                
                <div class="details-item">
                    <dt>Email</dt>
                    <dd>
                        <a href="mailto:<?php secho($requestor['email']); ?>" class="details-link">
                            <?php secho($requestor["email"]) ?>
                        </a>
                    </dd>
                </div>
                
                <div class="details-item">
                    <dt>Teléfono</dt>
                    <dd>
                        <a href="tel:<?php echo $requestor['phone']; ?>" class="details-link">
                            <?php echo phone($requestor["phone"]) ?>
                        </a>
                    </dd>
                </div>
            </dl>
        </div>
        
        <?php if ($request["status_id"] == 4) : ?>
            <!-- Botón activar oferta -->
            <button class="btn btn-success btn-block" data-bs-toggle="modal" data-bs-target="#modal-offer-activate">
                <i class="ki-outline ki-check-circle"></i>
                Activar oferta
            </button>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- Modal: Activar oferta -->
<div class="modal fade" tabindex="-1" id="modal-offer-activate">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activar oferta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form>
                <input type="hidden" name="request_id" value="<?php echo $request["id"] ?>" readonly>
                
                <div class="modal-body">
                    <p class="text-muted mb-4">¿Confirmas que quieres activar la oferta? Indica a continuación la fecha de vencimiento.</p>
                    
                    <div class="mb-0">
                        <label class="form-label">Fecha de vencimiento</label>
                        <input type="date" name="expires_at" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary bold-submit" data-action="api/offer-activate" data-reload="1">Activar oferta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Eliminar solicitud -->
<div class="modal fade" tabindex="-1" id="modal-request-delete">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="api/request-delete" data-reload="0" data-redirect="home" data-confirm-message="¿Estás seguro?<br>Esta acción no se puede deshacer.">
                <input type="hidden" name="request_id" readonly value="<?php echo $request["id"] ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar solicitud</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label">Indica el motivo por el que deseas eliminar la solicitud</label>
                        <textarea name="reason" class="form-control" required placeholder="Mínimo 15 caracteres" minlength="15" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger bold-submit">Eliminar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>