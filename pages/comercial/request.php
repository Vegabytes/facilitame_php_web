<?php
$scripts = [
    "tinymce",
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
        <strong>Solicitud eliminada</strong> — Esta solicitud ha sido eliminada. Solo puedes consultar el historial.
    </div>
</div>
<?php endif; ?>

<div class="request-page" style="height: calc(100vh - <?php echo $isDeleted ? '220' : '160'; ?>px); overflow: hidden;">
    
    <div class="request-layout" style="height: 100%; align-items: stretch;">
        
        <!-- COLUMNA 1: Detalles -->
        <aside class="request-sidebar" style="height: 100%; overflow-y: auto;">
            <?php require COMPONENTS_DIR . "/request-details-comercial.php" ?>
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
                <div class="tab-content">
                    
                    <!-- Tab: Ofertas (solo lectura) -->
                    <div class="tab-pane fade show active" id="tab-offers" role="tabpanel">
                        <div class="request-list-scroll" style="padding: 1rem 1.25rem;">
                            <?php if (empty($offers)) : ?>
                                <div class="empty-state empty-state-compact">
                                    <div class="empty-state-icon"><i class="ki-outline ki-dollar"></i></div>
                                    <p class="empty-state-text">No hay ofertas registradas</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($offers as $offer) : ?>
                                    <div class="list-card list-card-<?php echo $offer['status_id'] == 1 ? 'warning' : ($offer['status_id'] == 2 ? 'success' : 'muted'); ?> mb-2">
                                        <div class="list-card-content">
                                            <div class="list-card-title">
                                                <?php secho($offer['title']); ?>
                                            </div>
                                            <div class="list-card-meta">
                                                <span><i class="ki-outline ki-calendar"></i> <?php echo fdate($offer['created_at']); ?></span>
                                           <span class="badge-status badge-status-<?php echo $offer['status_id'] == 1 ? 'warning' : ($offer['status_id'] == 3 ? 'success' : 'muted'); ?>">
    <?php echo $offer['status_id'] == 1 ? 'Pendiente' : ($offer['status_id'] == 3 ? 'Aceptada' : ($offer['status_id'] == 4 ? 'Rechazada' : 'Otro')); ?>
</span>
                                            </div>
                                        </div>
                                        <?php if (!empty($offer['file'])) : ?>
                                        <div class="list-card-actions">
                                            <a href="<?php echo MEDIA_DIR . '/' . $offer['file']; ?>" target="_blank" class="btn-icon" title="Ver documento">
                                                <i class="ki-outline ki-file"></i>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tab: Documentos (solo lectura) -->
                    <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                        <div class="request-list-scroll" style="padding: 1rem 1.25rem;">
                            <?php if (empty($documents)) : ?>
                                <div class="empty-state empty-state-compact">
                                    <div class="empty-state-icon"><i class="ki-outline ki-folder"></i></div>
                                    <p class="empty-state-text">No hay documentos adjuntos</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($documents as $doc) : ?>
                                    <div class="list-card list-card-primary mb-2">
                                        <div class="list-card-content">
                                            <div class="list-card-title">
                                                <?php secho($doc['file_name'] ?? $doc['name'] ?? 'Documento'); ?>
                                            </div>
                                            <div class="list-card-meta">
                                                <span><i class="ki-outline ki-calendar"></i> <?php echo fdate($doc['created_at']); ?></span>
                                                <?php if (!empty($doc['type_name'])) : ?>
                                                    <span class="badge-status badge-status-info"><?php secho($doc['type_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="list-card-actions">
                                            <a href="<?php echo MEDIA_DIR . '/' . $doc['file']; ?>" target="_blank" class="btn-icon" title="Descargar">
                                                <i class="ki-outline ki-cloud-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tab: Comentarios internos -->
                    <div class="tab-pane fade" id="tab-comments" role="tabpanel">
                        <div class="comments-container" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                            <!-- Textarea oculto requerido por request.js -->
                            <textarea id="provider-comments" style="display:none;"><?php echo htmlspecialchars($comments ?? ''); ?></textarea>
                            
                            <div class="request-list-scroll" id="comments-list-container">
                                <?php if (empty(trim($comments ?? ''))) : ?>
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
        
        <!-- COLUMNA 3: Chat (solo lectura para comercial) -->
        <section class="request-chat-section" style="display: flex; flex-direction: column; min-height: 0; height: 100%;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                <div class="card-header" style="flex-shrink: 0;">
                    <h3 class="card-title">
                        <i class="ki-outline ki-messages me-2"></i>
                        Historial de mensajes
                    </h3>
                </div>
                <div class="card-body" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem;">
                    <?php if (empty($messages)) : ?>
                        <div class="empty-state empty-state-compact">
                            <div class="empty-state-icon"><i class="ki-outline ki-messages"></i></div>
                            <p class="empty-state-text">No hay mensajes en esta solicitud</p>
                        </div>
                    <?php else : ?>
                        <div class="chat-messages">
                            <?php foreach ($messages as $msg) : ?>
                                <?php 
                                    $isProvider = ($msg['sender_type'] ?? '') === 'provider';
                                    $alignClass = $isProvider ? 'chat-message-out' : 'chat-message-in';
                                ?>
                                <div class="chat-message <?php echo $alignClass; ?>" style="margin-bottom: 1rem;">
                                    <div class="chat-bubble" style="max-width: 80%; padding: 0.75rem 1rem; border-radius: 12px; background: <?php echo $isProvider ? 'var(--f-primary-light, #e8f9fa)' : '#f4f4f4'; ?>;">
                                        <div class="chat-sender" style="font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--f-text-muted);">
                                            <?php secho($msg['sender_name'] ?? 'Usuario'); ?>
                                        </div>
                                        <div class="chat-text" style="font-size: 0.875rem;">
                                            <?php echo nl2br(htmlspecialchars($msg['content'] ?? '')); ?>
                                        </div>
                                        <div class="chat-time" style="font-size: 0.7rem; color: var(--f-text-muted); margin-top: 0.25rem;">
                                            <?php echo fdate($msg['created_at'], 'd/m/Y H:i'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Comercial solo puede ver el chat, no escribir -->
            </div>
        </section>
        
    </div>
    
</div>