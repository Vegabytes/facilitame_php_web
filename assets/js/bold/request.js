(function ($) {
    "use strict";

    // Cache de selectores frecuentes
    const $document = $(document);

    // Helper para parsear respuestas JSON
    function parseResponse(response) {
        return typeof response === "string" ? JSON.parse(response) : response;
    }

    // Helper para mostrar errores con SweetAlert
    function showError(message) {
        Swal.fire({
            icon: "warning",
            html: message || "Ha ocurrido un error.",
            buttonsStyling: false,
            confirmButtonText: "Cerrar",
            customClass: { confirmButton: "btn btn-primary-facilitame" }
        });
    }

    // Helper para mostrar éxito con SweetAlert
    function showSuccess(message, reloadDelay) {
        Swal.fire({
            icon: "success",
            html: message,
            buttonsStyling: false,
            confirmButtonText: "Cerrar",
            customClass: { confirmButton: "btn btn-primary-facilitame" }
        });
        if (reloadDelay) {
            setTimeout(() => location.reload(), reloadDelay);
        }
    }

    // Helper para peticiones AJAX con FormData
    async function postFormData(url, formData) {
        const response = await $.ajax({
            url: url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false
        });
        return parseResponse(response);
    }

    // Helper para peticiones POST simples
    async function postData(url, data) {
        const response = await $.post(url, data);
        return parseResponse(response);
    }

    // Helper para guardar tab activo y recargar
    function reloadKeepingTab() {
        const activeTab = document.querySelector('.nav-tabs .nav-link.active');
        if (activeTab) {
            const tabTarget = activeTab.getAttribute('data-bs-target');
            sessionStorage.setItem('activeTab', tabTarget);
        }
        location.reload();
    }

    // Helper para formatear tamaño de archivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Helper para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    $(function () {
        // =====================================================================
        // RESTAURAR TAB ACTIVO DESPUÉS DE RELOAD
        // =====================================================================
        const savedTab = sessionStorage.getItem('activeTab');
        if (savedTab) {
            sessionStorage.removeItem('activeTab');
            const tabEl = document.querySelector(`[data-bs-target="${savedTab}"]`);
            if (tabEl && typeof bootstrap !== 'undefined') {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }

        // =====================================================================
        // SUBIR DOCUMENTOS (MÚLTIPLE CON DRAG & DROP)
        // =====================================================================
        const dropZone = document.getElementById('doc-drop-zone');
        const fileInput = document.getElementById('document');
        const previewContainer = document.getElementById('doc-upload-preview');
        const previewList = document.getElementById('doc-preview-list');
        const previewCount = document.getElementById('doc-preview-count');
        const $btnUploadDoc = $("#btn-upload-new-doc");
        const btnClear = document.getElementById('btn-clear-docs');
        const $fileTypeSelect = $("#file_type");

        // Estado de archivos seleccionados
        let selectedDocFiles = [];

        if (dropZone && fileInput && $btnUploadDoc.length) {
            
            // -----------------------------------------------------------------
            // DRAG & DROP EVENTS
            // -----------------------------------------------------------------
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'));
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'));
            });

            dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));

            // -----------------------------------------------------------------
            // FILE INPUT CHANGE
            // -----------------------------------------------------------------
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
                this.value = ''; // Reset para permitir seleccionar el mismo archivo
            });

            // -----------------------------------------------------------------
            // HANDLE FILES
            // -----------------------------------------------------------------
            function handleFiles(files) {
                const validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                let added = 0;

                Array.from(files).forEach(file => {
                    const ext = file.name.split('.').pop().toLowerCase();

                    // Validar extensión
                    if (!validExtensions.includes(ext)) {
                        toastr.warning('Archivo no permitido: ' + file.name);
                        return;
                    }

                    // Validar tamaño
                    if (file.size > maxSize) {
                        toastr.warning('Archivo muy grande (máx 10MB): ' + file.name);
                        return;
                    }

                    // Evitar duplicados
                    const isDuplicate = selectedDocFiles.some(f => 
                        f.name === file.name && f.size === file.size
                    );

                    if (isDuplicate) {
                        toastr.info('Archivo ya añadido: ' + file.name);
                        return;
                    }

                    selectedDocFiles.push(file);
                    added++;
                });

                if (added > 0) {
                    renderPreview();
                    updateUploadButton();
                }
            }

            // -----------------------------------------------------------------
            // RENDER PREVIEW
            // -----------------------------------------------------------------
            function renderPreview() {
                if (selectedDocFiles.length === 0) {
                    previewContainer.style.display = 'none';
                    return;
                }

                previewContainer.style.display = 'block';
                previewCount.textContent = selectedDocFiles.length + ' archivo' + (selectedDocFiles.length !== 1 ? 's' : '');

                let html = '';
                selectedDocFiles.forEach((file, index) => {
                    const ext = file.name.split('.').pop().toLowerCase();
                    const iconClass = getIconClass(ext);
                    const iconName = getIconName(ext);

                    html += `<div class="preview-item" data-index="${index}">
                        <div class="preview-item-icon ${iconClass}">
                            <i class="ki-outline ${iconName}"></i>
                        </div>
                        <div class="preview-item-info">
                            <div class="preview-item-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                            <div class="preview-item-size">${formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="preview-item-remove" data-index="${index}" title="Eliminar">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>`;
                });

                previewList.innerHTML = html;

                // Bind remove buttons
                previewList.querySelectorAll('.preview-item-remove').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const idx = parseInt(this.dataset.index);
                        selectedDocFiles.splice(idx, 1);
                        renderPreview();
                        updateUploadButton();
                    });
                });
            }

            // -----------------------------------------------------------------
            // CLEAR ALL
            // -----------------------------------------------------------------
            if (btnClear) {
                btnClear.addEventListener('click', () => {
                    selectedDocFiles = [];
                    renderPreview();
                    updateUploadButton();
                });
            }

            // -----------------------------------------------------------------
            // UPDATE UPLOAD BUTTON
            // -----------------------------------------------------------------
            function updateUploadButton() {
                const hasFiles = selectedDocFiles.length > 0;
                const hasType = $fileTypeSelect.val() !== '';

                $btnUploadDoc.prop('disabled', !(hasFiles && hasType));

                const countBadge = $btnUploadDoc.find('.btn-count');
                if (hasFiles) {
                    countBadge.text(selectedDocFiles.length).show();
                } else {
                    countBadge.hide();
                }
            }

            // Escuchar cambios en el select de tipo
            $fileTypeSelect.on('change', updateUploadButton);

            // -----------------------------------------------------------------
            // UPLOAD FILES
            // -----------------------------------------------------------------
            $btnUploadDoc.on("click", async function () {
                if (selectedDocFiles.length === 0) {
                    showError("Selecciona al menos un archivo");
                    return;
                }

                if (!$fileTypeSelect.val()) {
                    showError("Selecciona el tipo de documento que estás enviando, por favor");
                    return;
                }

                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...');

                const formData = new FormData();
                formData.append("request_id", $btn.data("request-id"));
                formData.append("file_type_id", $fileTypeSelect.val());

                // Añadir todos los archivos
                selectedDocFiles.forEach(file => {
                    formData.append("documents[]", file);
                });

                try {
                    const response = await postFormData("api/request-upload-new-document", formData);
                    if (response.status === "ok") {
                        const count = response.uploaded || selectedDocFiles.length;
                        toastr.success(count + ' documento' + (count !== 1 ? 's' : '') + ' subido' + (count !== 1 ? 's' : '') + ' correctamente');
                        
                        // Limpiar estado
                        selectedDocFiles = [];
                        renderPreview();
                        $fileTypeSelect.val('');
                        
                        reloadKeepingTab();
                    } else {
                        showError(response.message_html || response.message || "Error al subir documentos");
                    }
                } catch (error) {
                    showError("Ha ocurrido un error. Inténtalo de nuevo, por favor.");
                } finally {
                    $btn.html(originalHtml);
                    updateUploadButton();
                }
            });

            // -----------------------------------------------------------------
            // HELPER FUNCTIONS
            // -----------------------------------------------------------------
            function getIconClass(ext) {
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'icon-image';
                if (ext === 'pdf') return 'icon-pdf';
                if (['doc', 'docx'].includes(ext)) return 'icon-doc';
                return 'icon-default';
            }

            function getIconName(ext) {
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'ki-picture';
                if (ext === 'pdf') return 'ki-document';
                if (['doc', 'docx'].includes(ext)) return 'ki-document';
                return 'ki-file';
            }

            // Inicializar estado del botón
            updateUploadButton();
        }

        // =====================================================================
        // MODAL SUBIR OFERTA - TinyMCE (Lazy Load desde CDN)
        // =====================================================================
        const $modalOfferUpload = $("#modal-offer-upload");
        if ($modalOfferUpload.length) {
            let tinymceInitialized = false;
            let tinymceLoaded = false;

            // Función para cargar TinyMCE desde CDN (cdnjs - sin API key)
            function loadTinyMCE() {
                return new Promise((resolve, reject) => {
                    if (window.tinymce) {
                        resolve();
                        return;
                    }
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            $modalOfferUpload.on("shown.bs.modal", async function () {
                if (!tinymceInitialized && $("#offer-content-textarea").length) {
                    try {
                        if (!tinymceLoaded) {
                            await loadTinyMCE();
                            tinymceLoaded = true;
                        }
                        tinymce.init({
                            selector: "#offer-content-textarea",
                            height: 300,
                            menubar: false,
                            toolbar: "bold italic | bullist numlist",
                            plugins: "advlist autolink lists"
                        });
                        tinymceInitialized = true;
                    } catch (error) {
                        console.error('Error loading TinyMCE:', error);
                    }
                }
            });

            $modalOfferUpload.on("hidden.bs.modal", function () {
                if (window.tinymce) {
                    const editor = tinymce.get("offer-content-textarea");
                    if (editor) editor.setContent("");
                }
                $("#offer_title").val("");
                $("#offer_file").val("");
            });

            $("#modal-offer-upload-send").on("click", async function () {
                const $this = $(this);
                const offerTitle = $("#offer_title").val().trim();
                const offerFile = $("#offer_file")[0]?.files[0];
                const editor = window.tinymce ? tinymce.get("offer-content-textarea") : null;
                const offerContent = editor ? editor.getContent() : ($("#offer-content-textarea").val() || "");

                if (!offerTitle || !offerFile) {
                    showError("Debes indicar título y archivo de la oferta.");
                    return;
                }

                const formData = new FormData();
                formData.append("request_id", $this.data("request-id"));
                formData.append("offer_title", offerTitle);
                formData.append("offer_content", offerContent);
                formData.append("offer_file", offerFile);

                try {
                    const response = await postFormData("api/offer-upload", formData);
                    if (response.status === "ok") {
                        reloadKeepingTab();
                    } else {
                        showError(response.message_html || "Error al cargar la oferta");
                    }
                } catch (error) {
                    showError("Ha ocurrido un error. Inténtalo de nuevo, por favor.");
                }
            });
        }

        // =====================================================================
        // RETIRAR OFERTA - Con modal de confirmación
        // =====================================================================
        let withdrawData = { requestId: null, offerId: null };
        const $modalWithdraw = $("#modal-offer-withdraw");

        $document.on("click", ".btn-offer-withdraw", function () {
            withdrawData.requestId = $(this).data("request-id");
            withdrawData.offerId = $(this).data("offer-id");
            $modalWithdraw.modal("show");
        });

        $("#btn-confirm-withdraw").on("click", async function () {
            if (!withdrawData.requestId || !withdrawData.offerId) return;

            const $btn = $(this);
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Retirando...');

            try {
                const response = await postData("api/offer-withdraw", {
                    request_id: withdrawData.requestId,
                    offer_id: withdrawData.offerId
                });

                $modalWithdraw.modal("hide");

                if (response.status === "ok") {
                    reloadKeepingTab();
                } else {
                    showError(response.message_html);
                }
            } catch (error) {
                $modalWithdraw.modal("hide");
                showError("Ha ocurrido un error.");
            } finally {
                $btn.prop("disabled", false).html('<i class="ki-outline ki-cross-circle me-1"></i>Retirar');
            }
        });

        $modalWithdraw.on("hidden.bs.modal", function () {
            withdrawData = { requestId: null, offerId: null };
        });

        // =====================================================================
        // COMENTARIOS DEL PROVEEDOR
        // =====================================================================
        const $btnAddComment = $("#btn-add-provider-comment");
        if ($btnAddComment.length) {
            const $providerComments = $("#provider-comments");
            const $commentsListContainer = $("#comments-list-container");
            const commentsListEl = $commentsListContainer[0];
            
            // Scroll inicial al final
            if (commentsListEl) {
                commentsListEl.scrollTop = commentsListEl.scrollHeight;
            }

            $btnAddComment.on("click", async function () {
                const $input = $("#provider-comment-input");
                const comment = $input.val().trim();
                
                if (!comment) return;

                const $btn = $(this);
                $btn.prop("disabled", true);

                try {
                    const response = await postData("api/request-add-provider-comment", {
                        comment: comment,
                        request_id: $(this).data("request-id"),
                        previous_comment: $providerComments.val()
                    });

                    if (response.status === "ok") {
                        // Actualizar textarea oculto
                        $providerComments.val(response.data.comments);
                        
                        // Limpiar input
                        $input.val("");

                        // Actualizar vista de comentarios
                        if ($commentsListContainer.length) {
                            // Quitar empty state si existe
                            $commentsListContainer.find(".empty-state").remove();
                            
                            // Buscar o crear contenedor de contenido
                            let $content = $commentsListContainer.find(".comments-content");
                            if (!$content.length) {
                                $content = $('<div class="comments-content"></div>').appendTo($commentsListContainer);
                            }
                            
                            // Actualizar contenido
                            $content.html(escapeHtml(response.data.comments).replace(/\n/g, "<br>"));
                            
                            // Scroll al final
                            commentsListEl.scrollTop = commentsListEl.scrollHeight;
                        }
                    } else {
                        showError(response.message_html);
                    }
                } catch (error) {
                    showError("No se ha podido añadir el comentario");
                } finally {
                    $btn.prop("disabled", false);
                }
            });

            // Permitir enviar con Enter
            $("#provider-comment-input").on("keypress", function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $btnAddComment.click();
                }
            });
        }

        // =====================================================================
        // ACEPTAR OFERTA - Abrir modal manualmente (sin data-bs-toggle)
        // =====================================================================
        $document.on("click", ".btn-offer-accept-open-modal", function () {
            const $this = $(this);
            const offerId = $this.data("offer-id");
            const offerTitle = $this.data("offer-title");
            const offerContent = $this.data("offer-content") || '';
            
            // Poblar modal
            $("#modal-offer-accept-offer-id").val(offerId);
            $("#modal-offer-accept-offer-title").text(offerTitle);
            
            // Mostrar/ocultar descripción
            const $descWrapper = $("#modal-offer-accept-desc-wrapper");
            const $descContent = $("#modal-offer-accept-offer-content");
            if (offerContent) {
                $descContent.html(offerContent);
                $descWrapper.show();
            } else {
                $descWrapper.hide();
            }
            
            // Abrir modal manualmente con jQuery
            $("#modal-offer-accept").modal("show");
        });

        $("#btn-offer-accept").on("click", async function () {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Aceptando...');
            
            try {
                const response = await postData("api/offer-accept", {
                    request_id: $("#modal-offer-accept-request-id").val(),
                    offer_id: $("#modal-offer-accept-offer-id").val()
                });

                if (response.status === "ok") {
                    $("#modal-offer-accept-close").click();
                    showSuccess(response.message_html, 2500);
                } else {
                    showError(response.message_html);
                }
            } catch (error) {
                showError("Ha ocurrido un error");
            } finally {
                $btn.prop("disabled", false).html(originalHtml);
            }
        });

        // =====================================================================
        // CONFIRMAR OFERTA - Abrir modal manualmente
        // =====================================================================
        $document.on("click", ".btn-offer-confirm-open-modal", function () {
            const $this = $(this);
            const offerId = $this.data("offer-id");
            const offerTitle = $this.data("offer-title");
            const offerContent = $this.data("offer-content") || '';
            
            $("#modal-offer-confirm-offer-id").val(offerId);
            $("#modal-offer-confirm-offer-title").text(offerTitle);
            
            const $descWrapper = $("#modal-offer-confirm-desc-wrapper");
            const $descContent = $("#modal-offer-confirm-offer-content");
            if (offerContent) {
                $descContent.html(offerContent);
                $descWrapper.show();
            } else {
                $descWrapper.hide();
            }
            
            // Abrir modal manualmente con jQuery
            $("#modal-offer-confirm").modal("show");
        });

        $("#btn-offer-confirm").on("click", async function () {
            const formElement = document.getElementById("offer-confirm-form");
            if (!formElement) return;

            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Confirmando...');

            try {
                const response = await postFormData("api/offer-confirm", new FormData(formElement));

                if (response.status === "ok") {
                    $("#modal-offer-confirm-close").click();
                    showSuccess(response.message_html, 2500);
                } else {
                    showError(response.message_html);
                }
            } catch (error) {
                showError("Ha ocurrido un error");
            } finally {
                $btn.prop("disabled", false).html(originalHtml);
            }
        });

        // =====================================================================
        // ACTIVAR OFERTA - Abrir modal manualmente
        // =====================================================================
        $document.on("click", ".btn-offer-activate-open-modal", function () {
            const $this = $(this);
            const offerId = $this.data("offer-id");
            const offerTitle = $this.data("offer-title");
            
            $("#modal-offer-activate-offer-id").val(offerId);
            $("#modal-offer-activate-offer-title").text(offerTitle);
            $("#activate-expires-at").val("");
            
            // Abrir modal manualmente con jQuery
            $("#modal-offer-activate").modal("show");
        });

        $("#btn-offer-activate").on("click", async function () {
            const expiresAt = $("#activate-expires-at").val();
            
            if (!expiresAt) {
                showError("Debes indicar una fecha de vencimiento");
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Activando...');

            try {
                const response = await postData("api/offer-activate", {
                    request_id: $("input[name='request_id']").val(),
                    expires_at: expiresAt
                });

                if (response.status === "ok") {
                    $("#modal-offer-activate").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "¡Oferta activada!",
                        html: response.message_html || "La oferta ha sido activada correctamente",
                        confirmButtonColor: "#00C2CB"
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    showError(response.message_html || response.message);
                }
            } catch (error) {
                showError("No se pudo activar la oferta");
            } finally {
                $btn.prop("disabled", false).html(originalHtml);
            }
        });

        // =====================================================================
        // RECHAZAR OFERTA - Abrir modal manualmente (sin data-bs-toggle)
        // =====================================================================
        $document.on("click", ".btn-offer-reject-open-modal", function () {
            const $this = $(this);
            const offerId = $this.data("offer-id");
            const offerTitle = $this.data("offer-title");
            const offerContent = $this.data("offer-content") || '';
            
            // Poblar modal
            $("#modal-offer-reject-offer-id").val(offerId);
            $("#modal-offer-reject-offer-title").text(offerTitle);
            $("#modal-offer-reject-reason").val('');
            
            // Mostrar/ocultar descripción
            const $descWrapper = $("#modal-offer-reject-desc-wrapper");
            const $descContent = $("#modal-offer-reject-offer-content");
            if (offerContent) {
                $descContent.html(offerContent);
                $descWrapper.show();
            } else {
                $descWrapper.hide();
            }
            
            // Abrir modal manualmente con jQuery
            $("#modal-offer-reject").modal("show");
        });

        $("#btn-offer-reject").on("click", async function () {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Rechazando...');
            
            try {
                const response = await postData("api/offer-reject", {
                    request_id: $("#modal-offer-reject-request-id").val(),
                    reject_reason: $("#modal-offer-reject-reason").val(),
                    offer_id: $("#modal-offer-reject-offer-id").val()
                });

                if (response.status === "ok") {
                    $("#modal-offer-reject-close").click();
                    showSuccess(response.message_html, 2500);
                } else {
                    showError(response.message_html);
                }
            } catch (error) {
                showError("Ha ocurrido un error");
            } finally {
                $btn.prop("disabled", false).html(originalHtml);
            }
        });

        // =====================================================================
        // ACCIONES POR URL PARAMS
        // =====================================================================
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("review")) {
            document.querySelector('[data-bs-target="#modal-offer-review-request"]')?.click();
        }
        if (urlParams.has("incident")) {
            document.querySelector('[data-bs-target="#modal-incident-report"]')?.click();
        }

        // =========================================================================
        // REAGENDAR
        // =========================================================================
        $("#btn-reschedule-save").on("click", async function () {
            const rescheduledAt = $("#reschedule-date").val();
            const requestId = $("input[name='request_id']").val();

            if (!rescheduledAt) {
                showError("Debes seleccionar una fecha para la revisión.");
                return;
            }

            const formData = new FormData();
            formData.append("request_id", requestId);
            formData.append("rescheduled_at", rescheduledAt);

            try {
                const response = await postFormData("api/request-reschedule", formData);
                if (response.status === "ok") {
                    location.reload();
                } else {
                    showError(response.message_html);
                }
            } catch (error) {
                showError("Ha ocurrido un error. Inténtalo de nuevo, por favor.");
            }
        });

        // =========================================================================
        // REACTIVAR SOLICITUD
        // =========================================================================
        $("#btn-request-reactivate-send").on("click", async function () {
            const requestId = $(this).data("request-id") || $("input[name='request_id']").val();
            const reason = ($("#reactivation-reason").val() || "").trim();

            if (reason.length < 10) {
                showError("El motivo debe tener al menos 10 caracteres.");
                return;
            }

            const formData = new FormData();
            formData.append("request_id", requestId);
            formData.append("reactivation_reason", reason);

            try {
                const response = await postFormData("api/request-reactivate", formData);
                if (response.status === "ok") {
                    showSuccess(response.message_html || "Solicitud reactivada.", 3000);
                } else {
                    showError(response.message_html || "No se ha podido reactivar.");
                }
            } catch (error) {
                showError("Ha ocurrido un error.");
            }
        });

    }); // Cierre de $(function())

})(jQuery);