<?php
// Solo disponible para admin
?>

<!-- Toolbar fijo -->
<div class="tab-toolbar">
    <div class="toolbar-actions">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-offer-commissions-new">
            <i class="ki-outline ki-plus fs-4 me-1"></i>
            NUEVA COMISIÓN
        </button>
    </div>
</div>

<!-- Lista scrolleable -->
<div class="tab-list-container">
    <?php if (empty($offers_commissions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-percentage"></i>
            </div>
            <div class="empty-state-title">No hay ofertas de comisión</div>
            <p class="empty-state-text">Añade la primera usando el botón de arriba</p>
        </div>
    <?php else: ?>
        <?php foreach ($offers_commissions as $oc): ?>
            <form class="list-card list-card-form">
                <input type="hidden" name="id" value="<?= $oc["id"] ?>" readonly>
                
                <div class="list-card-content">
                    <!-- Header -->
                    <div class="list-card-header">
                        <span class="list-card-title">
                            <i class="ki-outline ki-dollar"></i>
                            <?= number_format($oc["value"], 2) ?> €
                        </span>
                        <span class="badge-status badge-status-<?= $oc["recurring"] == "1" ? 'success' : 'muted' ?>">
                            <i class="ki-outline ki-arrows-loop"></i>
                            <?= $oc["recurring"] == "1" ? 'Recurrente' : 'Puntual' ?>
                        </span>
                    </div>
                    
                    <!-- Campos editables -->
                    <div class="list-card-fields">
                        <div class="field-group">
                            <label class="field-label">Importe</label>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   min="0" 
                                   step="0.01"
                                   name="value"  
                                   value="<?= $oc["value"] ?>" 
                                   required />
                        </div>
                        
                        <div class="field-group">
                            <label class="field-label">Recurrente</label>
                            <select class="form-select form-select-sm" name="recurring" required>
                                <option value="0" <?= $oc["recurring"] == "0" ? "selected" : "" ?>>No</option>
                                <option value="1" <?= $oc["recurring"] == "1" ? "selected" : "" ?>>Sí</option>
                            </select>
                        </div>
                        
                        <div class="field-group">
                            <label class="field-label">Activo desde</label>
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   name="activated_at" 
                                   value="<?= $oc["activated_at"] ?>" 
                                   required />
                        </div>
                        
                        <div class="field-group">
                            <label class="field-label">Desactivado el</label>
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   name="deactivated_at" 
                                   value="<?= $oc["deactivated_at"] ?>" />
                        </div>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
                    <button type="submit" 
                            class="btn btn-sm btn-light-primary bold-submit" 
                            data-action="api/offer-commissions-update"
                            title="Actualizar">
                        <i class="ki-outline ki-check"></i>
                    </button>
                    <button type="submit" 
                            class="btn btn-sm btn-light-danger bold-submit" 
                            data-action="api/offer-commissions-delete" 
                            data-confirm-message="¿Estás seguro?<br>Esto no se puede deshacer.<br><br><b>Desaparecerá cualquier registro de esta oferta.</b>" 
                            data-reload="1"
                            title="Eliminar">
                        <i class="ki-outline ki-trash"></i>
                    </button>
                </div>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>