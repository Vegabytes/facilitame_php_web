<!--begin::Modal - Enviar comunicación-->
<div class="modal fade" id="kt_modal_advisory_communication" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header pb-0 border-0 justify-content-end">
                <!--begin::Close-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            
            <!--begin::Modal body-->
            <div class="modal-body scroll-y mx-5 mx-xl-18 pt-0 pb-15">
                <!--begin::Heading-->
                <div class="text-center mb-13">
                    <!--begin::Title-->
                    <h1 class="mb-3">Enviar comunicación</h1>
                    <!--end::Title-->
                    <!--begin::Description-->
                    <div class="text-muted fw-semibold fs-5">
                        Envía notificaciones y emails a tus clientes
                    </div>
                    <!--end::Description-->
                </div>
                <!--end::Heading-->
                
                <!--begin::Form-->
                <form id="form-advisory-communication">
                    <!--begin::Destinatarios-->
                    <div class="mb-8">
                        <label class="fs-6 fw-semibold mb-2">Destinatarios</label>
                        <select class="form-select form-select-solid" id="comm-target-type" name="target_type" data-control="select2" data-placeholder="Selecciona destinatarios" data-dropdown-parent="#kt_modal_advisory_communication">
                            <option value="all">Todos los clientes</option>
                            <optgroup label="Autónomos">
                                <option value="autonomo">Todos los autónomos</option>
                                <option value="autonomo|1-10">Autónomos (1-10 empleados)</option>
                                <option value="autonomo|10-50">Autónomos (10-50 empleados)</option>
                                <option value="autonomo|50+">Autónomos (+50 empleados)</option>
                            </optgroup>
                            <optgroup label="Empresas">
                                <option value="empresa">Todas las empresas</option>
                                <option value="empresa|0-10">Empresas (0-10 empleados)</option>
                                <option value="empresa|10-50">Empresas (10-50 empleados)</option>
                                <option value="empresa|50-250">Empresas (50-250 empleados)</option>
                                <option value="empresa|250+">Empresas (+250 empleados)</option>
                            </optgroup>
                            <optgroup label="Comunidades">
                                <option value="comunidad">Todas las comunidades</option>
                                <option value="comunidad|vecinos">Comunidades de vecinos</option>
                                <option value="comunidad|propietarios">Comunidades de propietarios</option>
                            </optgroup>
                            <optgroup label="Asociaciones">
                                <option value="asociacion">Todas las asociaciones</option>
                                <option value="asociacion|lucro">Con ánimo de lucro</option>
                                <option value="asociacion|sin-lucro">Sin ánimo de lucro</option>
                                <option value="asociacion|federacion">Federaciones</option>
                            </optgroup>
                        </select>
                    </div>
                    <!--end::Destinatarios-->
                    
                    <!--begin::Importancia-->
                    <div class="mb-8">
                        <label class="fs-6 fw-semibold mb-2">Nivel de Importancia</label>
                        <!--begin::Options-->
                        <div class="d-flex flex-wrap gap-3">
                            <!--begin::Option-->
                            <label class="btn btn-outline btn-outline-dashed btn-active-light-success d-flex flex-stack text-start p-4 flex-grow-1" data-kt-button="true">
                                <div class="d-flex align-items-center me-2">
                                    <div class="form-check form-check-custom form-check-solid form-check-success me-4">
                                        <input class="form-check-input" type="radio" name="importance" value="leve" />
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold">Leve</span>
                                        <span class="text-muted d-block fs-7">Solo notificación</span>
                                    </div>
                                </div>
                                <i class="ki-outline ki-information-2 fs-2x text-success"></i>
                            </label>
                            <!--end::Option-->
                            
                            <!--begin::Option-->
                            <label class="btn btn-outline btn-outline-dashed btn-active-light-warning d-flex flex-stack text-start p-4 flex-grow-1" data-kt-button="true">
                                <div class="d-flex align-items-center me-2">
                                    <div class="form-check form-check-custom form-check-solid form-check-warning me-4">
                                        <input class="form-check-input" type="radio" name="importance" value="media" checked />
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold">Media</span>
                                        <span class="text-muted d-block fs-7">Email + notificación</span>
                                    </div>
                                </div>
                                <i class="ki-outline ki-notification-on fs-2x text-warning"></i>
                            </label>
                            <!--end::Option-->
                            
                            <!--begin::Option-->
                            <label class="btn btn-outline btn-outline-dashed btn-active-light-danger d-flex flex-stack text-start p-4 flex-grow-1" data-kt-button="true">
                                <div class="d-flex align-items-center me-2">
                                    <div class="form-check form-check-custom form-check-solid form-check-danger me-4">
                                        <input class="form-check-input" type="radio" name="importance" value="importante" />
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold">Importante</span>
                                        <span class="text-muted d-block fs-7">Recordatorio 24h</span>
                                    </div>
                                </div>
                                <i class="ki-outline ki-shield-tick fs-2x text-danger"></i>
                            </label>
                            <!--end::Option-->
                        </div>
                        <!--end::Options-->
                    </div>
                    <!--end::Importancia-->
                    
                    <!--begin::Asunto-->
                    <div class="mb-8">
                        <label class="fs-6 fw-semibold mb-2 required">Asunto</label>
                        <input type="text" class="form-control form-control-solid" id="comm-subject" name="subject" placeholder="Ej: Nuevas ayudas disponibles para autónomos" maxlength="255" />
                    </div>
                    <!--end::Asunto-->
                    
                    <!--begin::Mensaje-->
                    <div class="mb-8">
                        <label class="fs-6 fw-semibold mb-2 required">Mensaje</label>
                        <textarea class="form-control form-control-solid" id="comm-message" name="message" rows="8" placeholder="Escribe el contenido de tu comunicación..."></textarea>
                        <div class="form-text">Puedes incluir enlaces y el mensaje se formateará automáticamente.</div>
                    </div>
                    <!--end::Mensaje-->
                    
                    <!--begin::Adjuntos-->
                    <div class="mb-8">
                        <label class="fs-6 fw-semibold mb-2">Adjuntar documentos</label>
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                            <i class="ki-outline ki-file-up fs-2tx text-facilitame me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h4 class="text-gray-900 fw-bold">Próximamente</h4>
                                    <div class="fs-6 text-gray-700">Podrás adjuntar PDFs y documentos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Adjuntos-->
                </form>
                <!--end::Form-->
                
                <!--begin::Actions-->
                <div class="d-flex flex-center">
                    <button type="button" class="btn btn-secondary-facilitame me-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary-facilitame" id="btn-send-communication">
                        <span class="indicator-label">
                            <i class="ki-outline ki-send me-1 text-white"></i>
                            Enviar comunicación
                        </span>
                        <span class="indicator-progress">
                            Enviando... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
                <!--end::Actions-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Enviar comunicación-->

<script>
document.getElementById('btn-send-communication').addEventListener('click', function() {
    var form = document.getElementById('form-advisory-communication');
    var subject = document.getElementById('comm-subject').value.trim();
    var message = document.getElementById('comm-message').value.trim();
    var targetValue = document.getElementById('comm-target-type').value;
    var importanceInput = form.querySelector('input[name="importance"]:checked');
    var importance = importanceInput ? importanceInput.value : 'media';
    
    if (!subject || !message) {
        Swal.fire({
            text: 'Asunto y mensaje son obligatorios',
            icon: 'warning',
            buttonsStyling: false,
            confirmButtonText: 'Entendido',
            customClass: {
                confirmButton: 'btn btn-primary-facilitame'
            }
        });
        return;
    }
    
    var targetParts = targetValue.split('|');
    var targetType = targetParts[0];
    var targetSubtype = targetParts[1] || '';
    
    var btn = this;
    btn.setAttribute('data-kt-indicator', 'on');
    btn.disabled = true;
    
    fetch('/api/advisory-send-communication', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            subject: subject,
            message: message,
            importance: importance,
            target_type: targetType,
            target_subtype: targetSubtype
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({
                text: result.message_html || result.message,
                icon: 'success',
                buttonsStyling: false,
                confirmButtonText: 'Perfecto',
                customClass: {
                    confirmButton: 'btn btn-primary-facilitame'
                }
            }).then(function() {
                var modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_advisory_communication'));
                modal.hide();
                form.reset();
            });
        } else {
            Swal.fire({
                text: result.message_html || result.message,
                icon: 'error',
                buttonsStyling: false,
                confirmButtonText: 'Cerrar',
                customClass: {
                    confirmButton: 'btn btn-primary-facilitame'
                }
            });
        }
    })
    .catch(function(error) {
        Swal.fire({
            text: 'Error de conexión',
            icon: 'error',
            buttonsStyling: false,
            confirmButtonText: 'Cerrar',
            customClass: {
                confirmButton: 'btn btn-primary-facilitame'
            }
        });
        console.error(error);
    })
    .finally(function() {
        btn.removeAttribute('data-kt-indicator');
        btn.disabled = false;
    });
});
</script>