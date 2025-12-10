<?php
$chat_disabled = comercial();
?>

<div class="card chat-card" style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden;">
    
    <!-- Header -->
    <div class="card-header" style="flex-shrink: 0;">
        <div class="card-title">
            <i class="ki-outline ki-messages"></i>
            <span>Chat con el cliente</span>
        </div>
    </div>
    
    <!-- Body: Messages -->
    <div class="card-body chat-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
        <div id="chat-container" class="chat-messages-container" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
            <?php echo (build_messages($messages, $request["id"])); ?>
        </div>
    </div>
    
    <!-- Footer: Input -->
    <div class="card-footer chat-footer" style="flex-shrink: 0;">
        <?php if ($chat_disabled): ?>
            <div class="chat-notice chat-notice-warning">
                <i class="ki-outline ki-information"></i>
                <span>Los comerciales no pueden enviar mensajes en el chat</span>
            </div>
        <?php else: ?>
            <textarea 
                id="chat-input" 
                data-request-id="<?php echo $request["id"] ?>" 
                class="form-control chat-textarea" 
                rows="2" 
                placeholder="Escribe tu mensaje aquí..."></textarea>
            
            <div class="chat-toolbar">
                <div class="chat-notice">
                    <i class="ki-outline ki-information"></i>
                    <span>Mensaje visible para el cliente</span>
                </div>
                <button class="btn btn-primary btn-sm" type="button" id="btn-send-chat-message">
                    <i class="ki-outline ki-send"></i>
                    Enviar
                </button>
            </div>
        <?php endif; ?>
    </div>
    
</div>