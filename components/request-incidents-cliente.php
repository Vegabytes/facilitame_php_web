<?php
// $incidents viene del controlador

$statusConfig = [
    8  => ['color' => 'info',    'icon' => 'ki-information-2', 'label' => 'Abierta'],
    9  => ['color' => 'warning', 'icon' => 'ki-time',          'label' => 'En proceso'],
    10 => ['color' => 'success', 'icon' => 'ki-check-circle',  'label' => 'Cerrada'],
];
?>

<!-- Lista scrolleable -->
<div class="tab-list-container">
    <?php if (empty($incidents)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-shield-tick"></i>
            </div>
            <div class="empty-state-title">Sin incidencias</div>
            <p class="empty-state-text">No hay incidencias registradas en esta solicitud</p>
        </div>
    <?php else: ?>
        <?php foreach ($incidents as $incident): 
            $config = $statusConfig[$incident["status_id"]] ?? ['color' => 'info', 'icon' => 'ki-information-2', 'label' => 'Desconocido'];
            $incident_date = !empty($incident["created_at"]) ? fdate($incident["created_at"]) : "-";
            $updated_at = !empty($incident["updated_at"]) ? fdate($incident["updated_at"]) : "-";
            $has_details = !empty($incident["details"]);
        ?>
            <div class="list-card list-card-<?= $config['color'] ?>">
                <div class="list-card-content">
                    <!-- Header -->
                    <div class="list-card-header">
                        <span class="list-card-title">
                            <i class="ki-outline <?= $config['icon'] ?>"></i>
                            <?php secho($incident["category_name"]); ?>
                        </span>
                        <span class="badge-status badge-status-<?= $config['color'] ?>">
                            <i class="ki-outline <?= $config['icon'] ?>"></i>
                            <?= $config['label'] ?>
                        </span>
                    </div>
                    
                    <!-- Meta -->
                    <div class="list-card-meta">
                        <span class="meta-item">
                            <i class="ki-outline ki-calendar"></i>
                            <?= $incident_date ?>
                        </span>
                        <span class="meta-item">
                            <i class="ki-outline ki-time"></i>
                            Actualizado: <?= $updated_at ?>
                        </span>
                        <?php if ($has_details): ?>
                            <a class="meta-toggle" 
                               data-bs-toggle="collapse" 
                               href="#incident-details-<?= $incident['id'] ?>"
                               role="button">
                                <i class="ki-outline ki-text-align-left"></i>
                                Ver detalles
                                <i class="ki-outline ki-down toggle-chevron"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Detalles colapsables -->
                    <?php if ($has_details): ?>
                        <div class="collapse" id="incident-details-<?= $incident['id'] ?>">
                            <div class="incident-description">
                                <strong>Detalles:</strong><br>
                                <?php secho($incident["details"]); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
                    <?php if ($incident["status_id"] == 10): // Cerrada - puede valorar ?>
                        <button type="button"
                                class="btn btn-sm btn-light-warning incident-valoracion"
                                data-request-id="<?= $incident["request_id"] ?>"
                                data-incident-id="<?= $incident["id"] ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#modal-incidence-valoracion"
                                title="Valorar incidencia">
                            <i class="ki-outline ki-star"></i>
                            Valorar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal valorar incidencia -->
<div class="modal fade" tabindex="-1" id="modal-incidence-valoracion">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Valorar incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="text-muted mb-4">¿Cómo valorarías la resolución de esta incidencia?</p>
                
                <div class="rating-stars mb-4" id="rating-stars">
                    <button type="button" class="rating-star" data-value="1"><i class="ki-outline ki-star"></i></button>
                    <button type="button" class="rating-star" data-value="2"><i class="ki-outline ki-star"></i></button>
                    <button type="button" class="rating-star" data-value="3"><i class="ki-outline ki-star"></i></button>
                    <button type="button" class="rating-star" data-value="4"><i class="ki-outline ki-star"></i></button>
                    <button type="button" class="rating-star" data-value="5"><i class="ki-outline ki-star"></i></button>
                </div>
                
                <div class="mb-0">
                    <label class="form-label">Comentario (opcional)</label>
                    <textarea id="rating-comment" class="form-control" rows="3" placeholder="¿Quieres añadir algún comentario?"></textarea>
                </div>

                <input type="hidden" id="modal-incident-id">
                <input type="hidden" id="modal-request-id">
                <input type="hidden" id="rating-value" value="0">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-submit-valoracion" class="btn btn-primary">
                    <i class="ki-outline ki-check me-1"></i>
                    Enviar valoración
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Poblar modal valoración
    document.querySelectorAll('.incident-valoracion').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal-incident-id').value = this.dataset.incidentId;
            document.getElementById('modal-request-id').value = this.dataset.requestId;
            document.getElementById('rating-value').value = '0';
            document.getElementById('rating-comment').value = '';
            
            // Reset stars
            document.querySelectorAll('.rating-star').forEach(star => {
                star.classList.remove('active');
            });
        });
    });
    
    // Rating stars
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            document.getElementById('rating-value').value = value;
            
            document.querySelectorAll('.rating-star').forEach((s, idx) => {
                if (idx < value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
});
</script>