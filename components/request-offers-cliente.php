<?php
// $offers ya viene del controlador
$base_url = ROOT_URL . "/" . DOCUMENTS_DIR . "/";

// Solo bloquear ofertas si la solicitud está en estado APLAZADA (10)
// Si ya cambió a otro estado (como "Oferta Disponible"), no bloquear nada
$is_request_postponed = ((int)$request["status_id"] === 10);
$rescheduled_at = ($is_request_postponed && !empty($request["rescheduled_at"])) 
    ? strtotime($request["rescheduled_at"]) 
    : null;

$statusConfig = [
    1  => ['color' => 'muted',   'label' => 'Borrador',    'icon' => 'ki-pencil'],
    2  => ['color' => 'warning', 'label' => 'Disponible',  'icon' => 'ki-time'],
    3  => ['color' => 'success', 'label' => 'Aceptada',    'icon' => 'ki-check-circle'],
    4  => ['color' => 'info',    'label' => 'En curso',    'icon' => 'ki-loading'],
    5  => ['color' => 'danger',  'label' => 'Rechazada',   'icon' => 'ki-cross-circle'],
    6  => ['color' => 'muted',   'label' => 'Retirada',    'icon' => 'ki-trash'],
    7  => ['color' => 'success', 'label' => 'Activada',    'icon' => 'ki-verify'],
    11 => ['color' => 'muted',   'label' => 'Desactivada', 'icon' => 'ki-minus-circle'],
];
?>

<!-- Lista scrolleable -->
<div class="tab-list-container">
    <?php if (empty($offers)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-document"></i>
            </div>
            <div class="empty-state-title">No hay ofertas todavía</div>
            <p class="empty-state-text">En breve llegarán las primeras ofertas, ¡estate atento!</p>
        </div>
    <?php else: ?>
        <?php foreach ($offers as $o): 
            $config = $statusConfig[$o["status_id"]] ?? ['color' => 'primary', 'label' => 'Desconocido', 'icon' => 'ki-question'];
            $file_url = $base_url . $o["offer_file"];
            $has_desc = !empty($o["offer_content"]);
            
            // Solo verificar bloqueo si la solicitud está aplazada
            $offer_created_at = strtotime($o["created_at"]);
            $is_blocked = $rescheduled_at !== null && $offer_created_at < $rescheduled_at;
            
            // Puede aceptar/rechazar si: status_id de request es 2,4,8,10 Y oferta es status 2 Y no bloqueada
            $can_respond = in_array($request["status_id"], [2, 4, 8, 10]) 
                        && (int)$o["status_id"] === 2 
                        && !$is_blocked;
        ?>
            <div class="list-card list-card-<?= $config['color'] ?><?= $is_blocked ? ' list-card-blocked' : '' ?>">
                <div class="list-card-content">
                    <!-- Header -->
                    <div class="list-card-header">
                        <a href="<?= $file_url ?>" target="_blank" rel="noopener" class="list-card-title">
                            <span class="text-muted">#<?= $o["id"] ?></span>
                            <i class="ki-outline ki-document"></i>
                            <?= htmlspecialchars($o["offer_title"]) ?>
                        </a>
                        <span class="badge-status badge-status-<?= $config['color'] ?>">
                            <i class="ki-outline <?= $config['icon'] ?>"></i>
                            <?= $config['label'] ?>
                        </span>
                    </div>
                    
                    <!-- Meta -->
                    <div class="list-card-meta">
                        <span class="meta-item">
                            <i class="ki-outline ki-calendar"></i>
                            <?= fdate($o["updated_at"]) ?>
                        </span>
                        <?php if ($has_desc): ?>
                            <a class="meta-toggle" 
                               data-bs-toggle="collapse" 
                               href="#offer-desc-<?= $o['id'] ?>"
                               role="button">
                                <i class="ki-outline ki-text-align-left"></i>
                                Ver descripción
                                <i class="ki-outline ki-down toggle-chevron"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Descripción colapsable -->
                    <?php if ($has_desc): ?>
                        <div class="collapse" id="offer-desc-<?= $o['id'] ?>">
                            <div class="offer-description">
                                <?= $o["offer_content"] ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Alerta si bloqueada -->
                    <?php if ($is_blocked): ?>
                        <div class="list-card-alert list-card-alert-warning">
                            <i class="ki-outline ki-information-2"></i>
                            <span>Esta oferta estará disponible a partir del <?= date('d/m/Y', $rescheduled_at) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
                    <?php if ($can_respond): ?>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>" 
                                data-offer-title="<?= htmlspecialchars($o["offer_title"]) ?>" 
                                data-offer-content="<?= htmlspecialchars($o["offer_content"] ?? '') ?>" 
                                class="btn btn-sm btn-light-success btn-offer-accept-open-modal"
                                title="Aceptar oferta">
                            <i class="ki-outline ki-check"></i>
                            Aceptar
                        </button>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>" 
                                data-offer-title="<?= htmlspecialchars($o["offer_title"]) ?>" 
                                data-offer-content="<?= htmlspecialchars($o["offer_content"] ?? '') ?>" 
                                class="btn btn-sm btn-light-danger btn-offer-reject-open-modal"
                                title="Rechazar oferta">
                            <i class="ki-outline ki-cross"></i>
                            Rechazar
                        </button>
                    <?php else: ?>
                        <a href="<?= $file_url ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="btn-icon btn-light-primary" 
                           title="Ver archivo"
                           data-bs-toggle="tooltip">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        <a href="<?= $file_url ?>" 
                           download 
                           class="btn-icon btn-light-success" 
                           title="Descargar"
                           data-bs-toggle="tooltip">
                            <i class="ki-outline ki-cloud-download"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>