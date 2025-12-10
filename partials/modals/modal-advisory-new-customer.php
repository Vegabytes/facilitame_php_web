<!-- Modal Crear Cliente -->
<div class="modal fade" id="modal_create_customer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="ki-outline ki-user-tick fs-2 text-facilitame me-2"></i>
                    Crear Nuevo Cliente
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="form_create_customer">
                <div class="modal-body">
                    
                    <!-- Datos personales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Nombre</label>
                            <input type="text" name="name" class="form-control" required placeholder="Nombre del cliente">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Apellidos</label>
                            <input type="text" name="lastname" class="form-control" required placeholder="Apellidos">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="email@ejemplo.com">
                            <div class="form-text">Se enviará un correo de bienvenida</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+34 600 000 000">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">NIF/CIF</label>
                            <input type="text" name="nif_cif" class="form-control" placeholder="12345678A" required>
                        </div>
                    </div>
                    
                    <div class="separator separator-dashed my-5"></div>
                    
                    <!-- Tipo de cliente -->
                    <div class="mb-4">
                        <label class="form-label required">Tipo de Cliente</label>
                        <select name="client_type" class="form-select" id="client_type" required>
                            <option value="">Selecciona...</option>
                            <option value="autonomo">Autónomo</option>
                            <option value="empresa">Empresa</option>
                            <option value="comunidad">Comunidad</option>
                            <option value="asociacion">Asociación</option>
                        </select>
                    </div>
                    
                    <!-- Subtipo para autónomos -->
                    <div class="mb-4" id="autonomo_subtype_container" style="display: none;">
                        <label class="form-label required">Tamaño (Autónomo)</label>
                        <select name="autonomo_subtype" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="1-10">1 a 10 empleados</option>
                            <option value="10-50">10 a 50 empleados</option>
                            <option value="50+">Más de 50 empleados</option>
                        </select>
                    </div>
                    
                    <!-- Subtipo para empresas -->
                    <div class="mb-4" id="empresa_subtype_container" style="display: none;">
                        <label class="form-label required">Tamaño (Empresa)</label>
                        <select name="empresa_subtype" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="0-10">0 a 10 empleados</option>
                            <option value="10-50">10 a 50 empleados</option>
                            <option value="50-250">50 a 250 empleados</option>
                            <option value="250+">Más de 250 empleados</option>
                        </select>
                    </div>
                    
                    <!-- Subtipo para comunidades -->
                    <div class="mb-4" id="comunidad_subtype_container" style="display: none;">
                        <label class="form-label required">Tipo (Comunidad)</label>
                        <select name="comunidad_subtype" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="vecinos">Comunidad de vecinos</option>
                            <option value="propietarios">Comunidad de propietarios</option>
                        </select>
                    </div>
                    
                    <!-- Subtipo para asociaciones -->
                    <div class="mb-4" id="asociacion_subtype_container" style="display: none;">
                        <label class="form-label required">Tipo (Asociación)</label>
                        <select name="asociacion_subtype" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="lucro">Con ánimo de lucro</option>
                            <option value="sin-lucro">Sin ánimo de lucro</option>
                            <option value="federacion">Federación</option>
                        </select>
                    </div>
                    
                    <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4 mt-4">
                        <i class="ki-outline ki-information-2 fs-2tx text-info me-4"></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <div class="fs-6 text-gray-700">
                                    El cliente recibirá un email con instrucciones para establecer su contraseña y acceder a la plataforma.
                                    Se le asignará automáticamente tu código de asesoría.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-facilitame" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-facilitame">
                        <span class="indicator-label">
                            <i class="ki-outline ki-check fs-4 me-2 text-white"></i>
                            Crear Cliente
                        </span>
                        <span class="indicator-progress">
                            Creando...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Vincular Cliente Existente -->
<div class="modal fade" id="modal_link_customer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title">
                    <i class="ki-outline ki-people fs-4 text-facilitame me-2"></i>
                    Cliente Existente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body text-center py-4">
                <div class="d-flex align-items-center gap-3 p-3 rounded bg-light-primary">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="ki-outline ki-user fs-4 text-white"></i>
                    </div>
                    <div class="text-start">
                        <div class="fw-bold text-gray-800" id="link_customer_name"></div>
                        <div class="text-gray-500 fs-7" id="link_customer_email"></div>
                    </div>
                </div>
                
                <p class="text-gray-600 fs-7 mt-4 mb-0">¿Vincular a tu asesoría?</p>
            </div>
            
            <div class="modal-footer justify-content-center py-3 gap-2">
                <button type="button" class="btn btn-sm btn-secondary-facilitame" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary-facilitame" id="btn_confirm_link">
                    <span class="indicator-label">
                        <i class="ki-outline ki-check fs-5 me-1 text-white"></i>
                        Vincular
                    </span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let pendingLinkData = null;

// Mostrar/ocultar subtipos según tipo de cliente
document.getElementById('client_type').addEventListener('change', function() {
    const value = this.value;
    const containers = ['autonomo', 'empresa', 'comunidad', 'asociacion'];
    
    containers.forEach(function(type) {
        document.getElementById(type + '_subtype_container').style.display = 'none';
        document.querySelector('[name="' + type + '_subtype"]').value = '';
    });
    
    if (value && containers.includes(value)) {
        document.getElementById(value + '_subtype_container').style.display = 'block';
    }
});

// Submit del formulario crear cliente
document.getElementById('form_create_customer').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const clientType = document.getElementById('client_type').value;
    if (clientType) {
        const subtypeSelect = document.querySelector('[name="' + clientType + '_subtype"]');
        if (subtypeSelect && !subtypeSelect.value) {
            toastr.error('Debes seleccionar el subtipo de cliente');
            return;
        }
    }
    
    const btn = this.querySelector('button[type="submit"]');
    btn.setAttribute('data-kt-indicator', 'on');
    btn.disabled = true;
    
    const formData = new FormData(this);
    
    fetch('/api/advisory-create-customer', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'ok') {
            toastr.success(data.message_html || 'Cliente creado correctamente');
            setTimeout(function() { location.reload(); }, 1500);
        } 
        else if (data.status === 'exists') {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
            showLinkCustomerModal(data.data, formData);
        }
        else {
            toastr.error(data.message_html || 'Error al crear el cliente');
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        console.error('Error:', err);
        toastr.error('Error de conexión');
        btn.removeAttribute('data-kt-indicator');
        btn.disabled = false;
    });
});

function showLinkCustomerModal(existingData, originalFormData) {
    pendingLinkData = {
        customerId: existingData.existing_user_id,
        email: existingData.email,
        formData: originalFormData
    };
    
    document.getElementById('link_customer_name').textContent = existingData.existing_user_name;
    document.getElementById('link_customer_email').textContent = existingData.email;
    
    var createModal = bootstrap.Modal.getInstance(document.getElementById('modal_create_customer'));
    if (createModal) createModal.hide();
    
    var linkModal = new bootstrap.Modal(document.getElementById('modal_link_customer'));
    linkModal.show();
}

// Confirmar vinculación
document.getElementById('btn_confirm_link').addEventListener('click', function() {
    if (!pendingLinkData) return;
    
    var btn = this;
    btn.setAttribute('data-kt-indicator', 'on');
    btn.disabled = true;
    
    var linkData = new FormData();
    linkData.append('customer_id', pendingLinkData.customerId);
    linkData.append('email', pendingLinkData.email);
    linkData.append('client_type', pendingLinkData.formData.get('client_type'));
    
    var clientType = pendingLinkData.formData.get('client_type');
    var subtype = '';
    if (clientType === 'autonomo') subtype = pendingLinkData.formData.get('autonomo_subtype');
    else if (clientType === 'empresa') subtype = pendingLinkData.formData.get('empresa_subtype');
    else if (clientType === 'comunidad') subtype = pendingLinkData.formData.get('comunidad_subtype');
    else if (clientType === 'asociacion') subtype = pendingLinkData.formData.get('asociacion_subtype');
    
    linkData.append('subtype', subtype);
    
    fetch('/api/advisory-link-customer', {
        method: 'POST',
        body: linkData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'ok') {
            toastr.success('Cliente vinculado correctamente');
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            toastr.error(data.message_html || 'Error al vincular el cliente');
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        console.error('Error:', err);
        toastr.error('Error de conexión');
        btn.removeAttribute('data-kt-indicator');
        btn.disabled = false;
    });
});

// Limpiar al cerrar modal de vincular
document.getElementById('modal_link_customer').addEventListener('hidden.bs.modal', function() {
    pendingLinkData = null;
    var btn = document.getElementById('btn_confirm_link');
    btn.removeAttribute('data-kt-indicator');
    btn.disabled = false;
});
</script>