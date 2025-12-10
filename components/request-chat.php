<?php
// $messages y $request vienen del controlador
?>

<div class="card chat-card">
    
    <!-- Header -->
    <div class="card-header">
        <div class="card-title">
            <i class="ki-outline ki-messages"></i>
            <span>Chat con el asesor</span>
        </div>
    </div>
    
    <!-- Body: Messages -->
    <div class="card-body chat-body">
        <div id="chat-container" class="chat-messages-container">
            <?php if (empty($messages)): ?>
                <div class="chat-empty-state">
                    <div class="chat-empty-icon">
                        <i class="ki-outline ki-messages"></i>
                    </div>
                    <p class="chat-empty-text">No hay mensajes aún.<br>¡Inicia la conversación!</p>
                </div>
            <?php else: ?>
                <?php echo build_messages($messages, $request["id"]); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer: Input -->
    <div class="card-footer chat-footer">
        <textarea 
            id="chat-input" 
            data-request-id="<?= $request["id"] ?>" 
            class="form-control chat-textarea" 
            rows="2" 
            placeholder="Escribe tu mensaje aquí..."></textarea>
        
        <div class="chat-toolbar">
            <div class="chat-notice">
                <i class="ki-outline ki-information"></i>
                <span>Mensaje visible para el asesor</span>
            </div>
            <button class="btn btn-primary btn-sm" type="button" id="btn-send-chat-message">
                <i class="ki-outline ki-send"></i>
                Enviar
            </button>
        </div>
    </div>
    
</div>