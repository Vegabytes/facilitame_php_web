<?php
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

<div class="tab-list-container">
    <?php if (empty($offers)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-document"></i>
            </div>
            <div class="empty-state-title">No hay ofertas</div>
            <p class="empty-state-text">Aún no hay ofertas para esta solicitud</p>
        </div>
    <?php else: ?>
        <?php foreach ($offers as $o): 
            $config = $statusConfig[$o["status_id"]] ?? ['color' => 'primary', 'label' => 'Desconocido', 'icon' => 'ki-question'];
            $file_url = $base_url . $o["offer_file"];
            $is_active = ($o['id'] == $request['active_offer_id']);
        ?>
            <div class="list-card list-card-<?= $config['color'] ?><?= $is_active ? ' list-card-active' : '' ?>">
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
                        <?php if ($is_active): ?>
                            <span class="badge-status badge-status-success">
                                <i class="ki-outline ki-verify"></i>
                                Activa
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Meta -->
                    <div class="list-card-meta">
                        <span class="meta-item">
                            <i class="ki-outline ki-profile-circle"></i>
                            <?= htmlspecialchars($o["provider_name"]) ?>
                        </span>
                        <span class="meta-item">
                            <i class="ki-outline ki-calendar"></i>
                            <?= fdate($o["created_at"]) ?>
                        </span>
                        <?php if (!empty($o["total_amount"])): ?>
                            <span class="meta-item fw-semibold text-primary">
                                <i class="ki-outline ki-euro"></i>
                                <?= number_format($o["total_amount"], 2, ",", ".") ?> €
                            </span>
                        <?php endif; ?>
                    </div>
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
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>