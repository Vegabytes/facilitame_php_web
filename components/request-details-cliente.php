<!-- request-details-cliente.php -->
<div class="card request-details-card">
    
    <!-- Header -->
    <div class="card-header">
        <div class="d-flex flex-column">
            <span class="card-title mb-1">Solicitud #<?php echo $request["id"] ?></span>
            <span class="text-muted fs-7"><?php secho($category["name"]) ?></span>
        </div>
        <?php 
            $statusMap = [
                1 => 'primary',   // Iniciado
                2 => 'info',      // Oferta disponible
                3 => 'success',   // Aceptada
                4 => 'info',      // En curso
                5 => 'danger',    // Rechazada
                6 => 'warning',   // Sin respuesta
                7 => 'success',   // Activada
                8 => 'warning',   // Revisión
                9 => 'muted',     // Eliminada
                10 => 'warning',  // Aplazada
                11 => 'muted'     // Desactivada
            ];
            $statusClass = $statusMap[$request["status_id"]] ?? 'muted';
        ?>
        <span class="badge-status badge-status-<?php echo $statusClass; ?>">
            <?php secho($request["status_name"]) ?>
        </span>
    </div>
    
    <!-- Body -->
    <div class="card-body">
        
        <!-- Sección: Detalles de la solicitud -->
        <?php if (!empty($form_values) && is_array($form_values)): ?>
        <div class="details-section">
            <h6 class="details-section-title">
                <i class="ki-outline ki-questionnaire-tablet"></i>
                Detalles de la solicitud
            </h6>
            
            <dl class="details-list">
                <?php foreach ($form_values as $fv): ?>
                    <?php if (is_array($fv) && isset($fv["question"])): ?>
                    <div class="details-item">
                        <dt><?php secho($fv["question"]) ?></dt>
                        <dd><?php secho($fv["answer"] ?? "-") ?></dd>
                    </div>
                    <?php elseif (is_array($fv) && isset($fv["name"])): ?>
                    <div class="details-item">
                        <dt><?php secho($fv["name"]) ?></dt>
                        <dd><?php secho($fv["value"] ?? "-") ?></dd>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="details-item">
                    <dt>Fecha de solicitud</dt>
                    <dd><?php echo fdate($request["request_date"]) ?></dd>
                </div>
                
                <?php if ((int)$request["status_id"] === 9 || !is_null($request["deleted_at"])): ?>
                <div class="details-item">
                    <dt class="text-danger">Motivo de eliminación</dt>
                    <dd class="text-danger"><?php echo nl2br(htmlspecialchars($request["delete_reason"] ?? '-')) ?></dd>
                </div>
                <?php endif; ?>
                
                <?php if ((int)$request["status_id"] === 10 && !empty($request["rescheduled_at"])): ?>
                <div class="details-item">
                    <dt>Reagendado para</dt>
                    <dd><?php echo fdate($request["rescheduled_at"]) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
        <?php endif; ?>
        
        <!-- Sección: Solicitante -->
        <div class="details-section">
            <h6 class="details-section-title">
                <i class="ki-outline ki-profile-circle"></i>
                Mis datos
            </h6>
            
            <dl class="details-list">
                <div class="details-item">
                    <dt>Nombre</dt>
                    <dd><?php secho(ucwords($requestor["name"] . " " . $requestor["lastname"])) ?></dd>
                </div>
                
                <?php if (!empty($requestor["nif_cif"])): ?>
                <div class="details-item">
                    <dt>CIF/NIF</dt>
                    <dd><?php secho($requestor["nif_cif"]) ?></dd>
                </div>
                <?php endif; ?>
                
                <div class="details-item">
                    <dt>Email</dt>
                    <dd>
                        <a href="mailto:<?php secho($requestor['email']); ?>" class="text-primary">
                            <?php secho($requestor["email"]) ?>
                        </a>
                    </dd>
                </div>
                
                <?php if (!empty($requestor["phone"])): ?>
                <div class="details-item">
                    <dt>Teléfono</dt>
                    <dd>
                        <a href="tel:<?php echo $requestor['phone']; ?>" class="text-primary">
                            <?php echo phone($requestor["phone"]) ?>
                        </a>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
        
        <!-- Sección: Acciones (solo si no está eliminada) -->
        <?php if ((int)$request["status_id"] !== 9 && is_null($request["deleted_at"])): ?>
        <div class="details-section">
            <h6 class="details-section-title">
                <i class="ki-outline ki-setting-2"></i>
                Acciones
            </h6>
            
            <div class="details-actions">
                <a href="tel:<?php echo $category["phone"] ?? '' ?>" class="btn btn-sm btn-light-primary w-100 mb-2">
                    <i class="ki-outline ki-phone me-1"></i>
                    Llamar al asesor
                </a>
                
                <?php if ($request["status_id"] == 7): ?>
                <button type="button" class="btn btn-sm btn-light-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modal-offer-review-request">
                    <i class="ki-outline ki-document me-1"></i>
                    Solicitar revisión
                </button>
                
                <button type="button" class="btn btn-sm btn-light-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modal-incident-report">
                    <i class="ki-outline ki-information me-1"></i>
                    Comunicar incidencia
                </button>
                <?php endif; ?>
                
                <button type="button" class="btn btn-sm btn-light-danger w-100" data-bs-toggle="modal" data-bs-target="#modal-request-delete">
                    <i class="ki-outline ki-trash me-1"></i>
                    Eliminar solicitud
                </button>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
</div>