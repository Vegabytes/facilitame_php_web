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
            <i class="ki-outline ki-send"></i>
        </button>
    </div>
    
    <div class="comment-info-banner">
        <i class="ki-outline ki-information-2"></i>
        <span>Estos comentarios solo son visibles para el equipo interno</span>
    </div>
</div>

<style>
/* ============================================
   EMPTY STATE PARA COMENTARIOS
   ============================================ */

.empty-state-comments {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    text-align: center;
}

.empty-state-icon-small {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.empty-state-icon-small i {
    font-size: 1.75rem;
    color: var(--color-main-facilitame);
}

.empty-state-text-small {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* ============================================
   DISPLAY DE COMENTARIOS
   ============================================ */

.comments-display {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
    font-size: 0.875rem;
    line-height: 1.8;
    color: #475569;
    white-space: pre-wrap;
    word-wrap: break-word;
    border: 1px solid #e2e8f0;
}

/* ============================================
   INPUT DE COMENTARIOS MODERNO
   ============================================ */

.comment-input-wrapper {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.comment-input-modern {
    flex: 1;
    height: 44px;
    padding: 0 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    font-size: 0.875rem;
    background: white;
    transition: all 0.2s ease;
}

.comment-input-modern:focus {
    outline: none;
    border-color: var(--color-main-facilitame);
    box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.1);
}

.comment-input-modern::placeholder {
    color: #94a3b8;
}

.btn-send-modern {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: var(--color-main-facilitame);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.btn-send-modern:hover {
    background: var(--color-main-facilitame-active);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 194, 203, 0.3);
}

.btn-send-modern:active {
    transform: scale(0.95);
}

.btn-send-modern i {
    font-size: 1.125rem;
}

/* ============================================
   BANNER INFORMATIVO
   ============================================ */

.comment-info-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #e0f2fe;
    border-radius: 8px;
    font-size: 0.8125rem;
    color: #0369a1;
}

.comment-info-banner i {
    font-size: 1rem;
    flex-shrink: 0;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .comment-input-wrapper {
        gap: 0.375rem;
    }
    
    .comment-input-modern {
        font-size: 0.8125rem;
    }
}
</style>