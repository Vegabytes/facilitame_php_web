<div class="card request-details-card">
    
    <!-- Header -->
    <div class="card-header">
        <div class="card-title">
            <span>Solicitud #<?php echo $request["id"] ?></span>
        </div>
        <span class="badge-status badge-status-info"><?php echo $category["name"] ?></span>
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
        
    </div>
    
</div>