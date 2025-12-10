<!--begin::Card header-->
<div class="card-provider-header">
    <h3 class="card-provider-title-small">
        <i class="ki-outline ki-message-text"></i>
        Comentarios internos
    </h3>
</div>
<!--end::Card header-->

<!--begin::Card body-->
<div class="card-provider-body-comments">
    <?php if (empty(trim($comments))) : ?>
        <div class="empty-state-comments-provider">
            <div class="empty-state-icon-small-provider">
                <i class="ki-outline ki-message-text"></i>
            </div>
            <p class="empty-state-text-small-provider">No hay comentarios internos todavía</p>
        </div>
    <?php else : ?>
        <div class="comments-display-provider">
            <?php echo nl2br(htmlspecialchars($comments)); ?>
        </div>
    <?php endif; ?>
</div>
<!--end::Card body-->

<!--begin::Card footer-->
<div class="card-provider-footer">
    <div class="comment-input-wrapper-provider mb-3">
        <input 
            type="text" 
            class="comment-input-provider" 
            id="provider-comment-input" 
            placeholder="Escribe un comentario interno...">
        <button 
            class="btn-send-provider" 
            type="button" 
            id="btn-add-provider-comment" 
            data-request-id="<?php echo $request["id"] ?>">
            <i class="ki-outline ki-send"></i>
        </button>
    </div>
    
    <div class="comment-info-banner-provider">
        <i class="ki-outline ki-information-2"></i>
        <span>Estos comentarios solo son visibles para el equipo interno</span>
    </div>
</div>
<!--end::Card footer-->