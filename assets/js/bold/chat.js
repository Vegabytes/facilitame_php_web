(function ($)
{
    $(document).ready(function ()
    {
        let btn_send = $("#btn-send-chat-message");
        btn_send.on("click", send_message_full);

        async function send_message_full()
        {
            let message_id = await store_message();
            if (message_id === false) return;

            let chat_message_html = await get_chat_messages_html();
            $("#chat-container").html(chat_message_html);
            $("#chat-input").val("").focus();
        }

        async function store_message()
        {
            const message = $("#chat-input").val();
            const request_id = $("#chat-input").data("request-id");

            let data = {
                request_id: request_id,
                message: message
            };

            console.log(`data:`);
            console.log(data);

            let response;
            try
            {
                response = await $.post("api/message-store", data).fail(() => { return; });
                
                // Fix: verificar si ya es objeto antes de parsear
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                
                if (response.status == "ok")
                {
                    return response.data.message_id;
                }
                else
                {
                    Swal.fire({
                        html: response.message_html,
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return false;
                }
            } catch (error)
            {
                console.error(error);
                return false;
            }
        }

        async function get_chat_messages_html()
        {
            const request_id = $("#chat-input").data("request-id");
            let data = {
                request_id: request_id
            };

            let response;
            try
            {
                response = await $.post("api/messages-get-html", data).fail(() => { return; });
                
                // Fix: verificar si ya es objeto antes de parsear
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.status == "ok")
                {
                    return response.data.html;
                }
                else
                {
                    Swal.fire({
                        html: response.message_html,
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return false;
                }
            } catch (error)
            {
                console.error(error);
                return false;
            }
        }

        // Obtener mensajes cada 30 segundos :: inicio
        function getHash(content)
        {
            const cleanContent = content.replace(/\s+/g, ""); // Eliminar espacios y saltos de línea
            return cleanContent.split("").reduce((a, b) => {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0);
        }
        
        setInterval(async () =>
        {
            const chatContainer = $("#chat-container");
            if (chatContainer.length === 0) return; // No ejecutar si no existe el container

            // Extraer el texto del último mensaje actual
            const currentLastMessage = chatContainer.find(".chat-message:last").text().trim();
        
            // Obtener el HTML actualizado
            const chat_message_html = await get_chat_messages_html();
            if (!chat_message_html) return; // Si hubo error, no continuar
        
            // Crear un contenedor temporal para analizar el nuevo HTML
            const tempContainer = $("<div>").html(chat_message_html);
            const newLastMessage = tempContainer.find(".chat-message:last").text().trim();
        
            // Comparar los últimos mensajes
            if (currentLastMessage !== newLastMessage)
            {
                chatContainer.html(chat_message_html);
            }
        }, 30000);
        // Obtener mensajes cada 30 segundos :: fin

        // Selecciona el elemento a observar
        const targetNode = document.getElementById('chat-container');
        
        // Solo configurar observer si existe el elemento
        if (targetNode) {
            // Configura las opciones del observer
            const config = { childList: true, subtree: true };

            // Callback para ejecutar cuando se detecten cambios
            const callback = function (mutationsList, observer)
            {
                for (let mutation of mutationsList)
                {
                    if (mutation.type === 'childList')
                    {
                        scroll_chat();
                    }
                }
            };

            // Crea una instancia de MutationObserver con el callback
            const observer = new MutationObserver(callback);

            // Comienza a observar el nodo objetivo con las opciones configuradas
            observer.observe(targetNode, config);

            function scroll_chat()
            {
                targetNode.scrollTop = targetNode.scrollHeight;
            }
            scroll_chat();
        }

        // Verificar si la URL contiene "#tab-chat"
        if (window.location.hash === "#tab-chat")
        {
            $('#chat-tab-scroll').tab('show');
        }
    });
})(jQuery)