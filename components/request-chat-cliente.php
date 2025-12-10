<?php
$chat_disabled = comercial() ? " disabled " : "";
?>
<div class="card-modern card-modern-full-height" id="kt_chat_messenger">
    <!--begin::Card header-->
    <div class="card-modern-header">
        <h3 class="card-modern-title-small">
            <i class="ki-outline ki-messages"></i>
            Chat con el cliente
        </h3>
    </div>
    <!--end::Card header-->
    
    <!--begin::Card body-->
    <div class="card-modern-body chat-body" id="kt_chat_messenger_body">
        <!--begin::Messages-->
        <div id="chat-container" class="chat-messages-container" 
             data-kt-element="messages" 
             data-kt-scroll="true" 
             data-kt-scroll-activate="{default: false, lg: true}" 
             data-kt-scroll-max-height="auto" 
             data-kt-scroll-dependencies="#kt_header, #kt_app_header, #kt_app_toolbar, #kt_toolbar, #kt_footer, #kt_app_footer, #kt_chat_messenger_header, #kt_chat_messenger_footer" 
             data-kt-scroll-wrappers="#kt_content, #kt_app_content, #kt_chat_messenger_body" 
             data-kt-scroll-offset="5px">
            <?php echo (build_messages($messages, $request["id"])); ?>
        </div>
        <!--end::Messages-->
    </div>
    <!--end::Card body-->
    
    <!--begin::Card footer-->
    <div class="card-modern-footer" id="kt_chat_messenger_footer">
        <?php if (comercial()) : ?>
            <div class="alert-modern alert-modern-info">
                <i class="ki-outline ki-information-2"></i>
                <span>Los comerciales no pueden enviar mensajes en el chat</span>
            </div>
        <?php else: ?>
            <!--begin::Input-->
            <textarea 
                id="chat-input" 
                data-request-id="<?php echo $request["id"] ?>" 
                class="chat-input-textarea" 
                rows="2" 
                data-kt-element="input" 
                placeholder="Escribe tu mensaje aquí..." 
                data-form-type="other"></textarea>
            <!--end::Input-->
            
            <!--begin:Toolbar-->
            <div class="chat-toolbar">
                <div class="chat-info">
                    <i class="ki-outline ki-information"></i>
                    Mensaje visible para el cliente
                </div>
                <button class="btn-modern btn-modern-primary btn-sm" type="button" data-kt-element="send" data-form-type="action" id="btn-send-chat-message">
                    <i class="ki-outline ki-send"></i>
                    Enviar
                </button>
            </div>
            <!--end::Toolbar-->
        <?php endif; ?>
    </div>
    <!--end::Card footer-->
</div>