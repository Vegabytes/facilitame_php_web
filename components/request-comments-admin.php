<div class="card-header">
    <h3 class="card-title fw-bold text-gray-800 mb-0">
        <i class="ki-outline ki-message-text fs-2 text-facilitame me-2"></i>
        Comentarios internos
    </h3>
</div>

<div class="card-body card-scroll pt-3" style="max-height: calc(50vh - 160px);">
    <?php if (empty(trim($comments))) : ?>
        <div class="empty-state-comments">
            <div class="empty-state-icon-small">
                <i class="ki-outline ki-message-text"></i>
            </div>
            <p class="empty-state-text-small">No hay comentarios internos todavía</p>
        </div>
    <?php else : ?>
        <div class="comments-display">
            <?php echo nl2br(htmlspecialchars($comments)); ?>
        </div>
    <?php endif; ?>
</div>

<div class="card-footer bg-light p-4">
    <div class="comment-input-wrapper mb-3">
        <input 
            type="text" 
            class="comment-input-modern" 
            id="provider-comment-input" 
            placeholder="Escribe un comentario interno...">
        <button 
            class="btn-send-modern" 
            type="button" 
            id="btn-add-provider-comment" 
            data-request-id="<?php echo $request["id"] ?>">
            <i class="ki-outline ki-send text-white"></i>
        </button>
    </div>
    
    <div class="comment-info-banner">
        <i class="ki-outline ki-information-2"></i>
        <span>Estos comentarios solo son visibles para el equipo interno</span>
    </div>
</div>