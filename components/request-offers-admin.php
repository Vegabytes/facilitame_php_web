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
            <i class="ki-outline ki-plus fs-4 me-1"></i>
            CARGAR OFERTA
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
            <div class="empty-state-title">No hay ofertas todavía</div>
            <p class="empty-state-text">Carga la primera oferta para tu cliente usando el botón de arriba</p>
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
                        <?php if (!empty($o["total_amount"])): ?>
                            <span class="meta-item fw-semibold text-primary">
                                <i class="ki-outline ki-euro"></i>
                                <?= number_format($o["total_amount"], 2, ",", ".") ?> €
                            </span>
                        <?php endif; ?>
                        <?php if ($has_desc): ?>
                            <a class="meta-toggle" 
                               data-bs-toggle="collapse" 
                               href="#offer-desc-<?= $o['id'] ?>"
                               role="button"
                               aria-expanded="false"
                               aria-controls="offer-desc-<?= $o['id'] ?>">
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
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
                    <a href="<?= $file_url ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="btn-icon btn-icon-primary" 
                       title="Ver archivo"
                       data-bs-toggle="tooltip">
                        <i class="ki-outline ki-eye"></i>
                    </a>
                    <a href="<?= $file_url ?>" 
                       download 
                       class="btn-icon btn-icon-success" 
                       title="Descargar"
                       data-bs-toggle="tooltip">
                        <i class="ki-outline ki-cloud-download"></i>
                    </a>
                    <?php if ($o["status_id"] == 2): ?>
                        <button type="button" 
                                data-request-id="<?= $request["id"] ?>" 
                                data-offer-id="<?= $o["id"] ?>" 
                                class="btn-icon btn-icon-danger btn-offer-withdraw"
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
                                class="btn btn-success btn-sm btn-offer-confirm-open-modal">
                            <i class="ki-outline ki-check-circle"></i>
                            Confirmar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Confirmar oferta (solo admin) -->
<div class="modal fade" tabindex="-1" id="modal-offer-confirm">
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
                <div class="modal-body pt-4">
                    <p class="mb-3">Vas a confirmar la oferta <strong id="modal-offer-confirm-offer-title"></strong>.</p>
                    
                    <div id="modal-offer-confirm-desc-wrapper" class="mb-4 d-none">
                        <p class="text-muted mb-2">Descripción:</p>
                        <div id="modal-offer-confirm-offer-content" class="p-3 bg-light rounded small"></div>
                    </div>

                    <?php if (request_requires_total_amount($request["category_id"])) : ?>
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
                            <label class="form-label fw-semibold">
                                <?php if ($request["category_id"] == 24) : ?>
                                    Importe total del Kit Digital <span class="text-danger">*</span>
                                <?php else : ?>
                                    Importe mensual total de la oferta <span class="text-danger">*</span>
                                <?php endif ; ?>
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="ki-outline ki-euro input-icon"></i>
                                <input type="number" 
                                       class="form-control form-control-icon" 
                                       name="total_amount" 
                                       min="1" 
                                       step="0.01" 
                                       placeholder="Ej: 150.00"
                                       required>
                            </div>
                        </div>
                    <?php else : ?>
                        <input type="hidden" name="commission_type" value="1">
                        
                        <div class="info-box info-box-success mb-4">
                            <div class="info-box-icon">
                                <i class="ki-outline ki-information-2"></i>
                            </div>
                            <div class="info-box-content">
                                <span class="info-box-title">Indica la comisión</span>
                                <span class="info-box-text">Este dato se usará para el seguimiento de la solicitud.</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="form-label fw-semibold">Comisión (puntos) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   min="0.01" 
                                   step="0.01" 
                                   class="form-control" 
                                   name="commission" 
                                   placeholder="Ej: 5.00"
                                   required>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="offer_id" readonly id="modal-offer-confirm-offer-id">
                    <input type="hidden" name="request_id" readonly id="modal-offer-confirm-request-id" value="<?= $request["id"] ?>">
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