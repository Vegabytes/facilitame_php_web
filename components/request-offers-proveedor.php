<?php
// $offers ya viene definido desde el controlador
$base_url = ROOT_URL . "/" . DOCUMENTS_DIR . "/";

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

<!-- Toolbar fijo -->
<div class="tab-toolbar">
    <div class="toolbar-actions">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-offer-upload">
            <i class="ki-outline ki-add-files"></i>
            Cargar oferta
        </button>
        
        <?php if (!in_array($request["status_id"], [10])): ?>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modal-reschedule">
                <i class="ki-outline ki-calendar"></i>
                Reagendar
            </button>
        <?php endif; ?>
        
        <?php if (in_array($request["status_id"], [8])): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-request-reactivate">
                <i class="ki-outline ki-arrows-circle"></i>
                Reactivar
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Lista scrolleable -->
<div class="tab-list-container">
    <?php if (empty($offers)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-document"></i>
            </div>
            <div class="empty-state-title">No hay ofertas todav&iacute;a</div>
            <p class="empty-state-text">Carga la primera oferta para tu cliente usando el bot&oacute;n de arriba</p>
        </div>
    <?php else: ?>
        <?php foreach ($offers as $o): 
            $config = $statusConfig[$o["status_id"]] ?? ['color' => 'primary', 'label' => 'Desconocido', 'icon' => 'ki-question'];
            $file_url = $base_url . $o["offer_file"];
            $has_desc = !empty($o["offer_content"]);
        ?>
            <div class="list-card list-card-<?= $config['color'] ?>">
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
                               role="button"
                               aria-expanded="false"
                               aria-controls="offer-desc-<?= $o['id'] ?>">
                                <i class="ki-outline ki-text-align-left"></i>
                                Ver descripci&oacute;n
                                <i class="ki-outline ki-down toggle-chevron"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Descripci¨®n colapsable -->
                    <?php if ($has_desc): ?>
                        <div class="collapse" id="offer-desc-<?= $o['id'] ?>">
                            <div class="offer-description">
                                <?= $o["offer_content"] ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
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
                    <?php if ($o["status_id"] == 2): ?>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>" 
                                class="btn-icon btn-light-danger btn-offer-withdraw"
                                title="Retirar oferta"
                                data-bs-toggle="tooltip">
                            <i class="ki-outline ki-cross-circle"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($o["status_id"] == 3): ?>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>"
                                data-offer-title="<?= htmlspecialchars($o["offer_title"]) ?>"
                                data-offer-content="<?= htmlspecialchars($o["offer_content"] ?? '') ?>"
                                class="btn-icon btn-light-success btn-offer-confirm-open-modal"
                                title="Confirmar oferta"
                                data-bs-toggle="tooltip">
                            <i class="ki-outline ki-check-circle"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($o["status_id"] == 4): ?>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>"
                                data-offer-title="<?= htmlspecialchars($o["offer_title"]) ?>"
                                class="btn-icon btn-light-primary btn-offer-activate-open-modal"
                                title="Activar oferta"
                                data-bs-toggle="tooltip">
                            <i class="ki-outline ki-verify"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>