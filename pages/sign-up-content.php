<?php
$code = (isset($_GET["code"]) && $_GET["code"] != "") ? $_GET["code"] : "";
$advisory_code = (isset($_GET["advisory"]) && $_GET["advisory"] != "") ? $_GET["advisory"] : "";
$code_readonly = ($code == "") ? "" : " readonly ";
$referal = (isset($_GET["referal"]) && $_GET["referal"] != "") ? base64_decode($_GET["referal"]) : "";
$regions_options = get_regions();
?>

<style>
body {
	background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
	font-family: 'Inter', sans-serif;
}

.signup-aside-facilitame {
	background: linear-gradient(135deg, var(--color-main-facilitame) 0%, #00a8b0 100%);
	position: relative;
	overflow: hidden;
}

.signup-logo-icon {
	width: 100px;
	height: 100px;
	filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
}

.signup-logo-text {
	max-width: 280px;
}

.signup-tagline {
	color: white;
	font-size: 1.75rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
}

.signup-subtitle {
	color: rgba(255, 255, 255, 0.9);
	font-size: 1rem;
	font-weight: 500;
	margin: 0;
}

.signup-form-side {
	background: white;
	overflow-y: auto;
	max-height: 100vh;
}

.signup-form-container {
	width: 100%;
	max-width: 500px;
}

.signup-form-title {
	color: #1e293b;
	font-size: 1.75rem;
	font-weight: 700;
	margin-bottom: 0.25rem;
}

.signup-form-subtitle {
	color: #64748b;
	font-size: 0.875rem;
	margin: 0;
}

.form-label-facilitame {
	color: var(--color-main-facilitame) !important;
	font-weight: 600;
	font-size: 0.75rem;
	margin-bottom: 0.375rem;
	display: block;
}

.form-label-facilitame.required::after {
	content: '*';
	color: #ef4444;
	margin-left: 0.25rem;
}

.form-control-facilitame-sm,
.form-select-facilitame-sm {
	height: 42px;
	padding: 0.5rem 0.875rem;
	border: 2px solid #e2e8f0;
	border-radius: 8px;
	font-size: 0.8125rem;
	transition: all 0.2s ease;
	background-color: #f8fafc;
	color: #475569;
}

.form-control-facilitame-sm:focus,
.form-select-facilitame-sm:focus {
	border-color: var(--color-main-facilitame);
	background-color: white;
	box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.1);
	outline: none;
}

.form-control-facilitame-sm::placeholder {
	color: #94a3b8;
}

.form-check-custom .form-check-input {
	width: 1.125rem;
	height: 1.125rem;
	border: 2px solid #cbd5e1;
	border-radius: 5px;
}

.form-check-custom .form-check-input:checked {
	background-color: var(--color-main-facilitame);
	border-color: var(--color-main-facilitame);
}

.link-facilitame {
	color: var(--color-main-facilitame);
	text-decoration: none;
	font-weight: 600;
}

.link-facilitame:hover {
	text-decoration: underline;
}

#kt_sign_up_submit {
	height: 46px;
	font-size: 0.9375rem;
	font-weight: 700;
	border-radius: 8px;
}

#kt_sign_up_submit .indicator-progress {
	display: none;
}

#kt_sign_up_submit[data-kt-indicator="on"] .indicator-label {
	display: none;
}

#kt_sign_up_submit[data-kt-indicator="on"] .indicator-progress {
	display: inline-flex;
}

#asesoria-fields {
	padding: 0.875rem;
	background: #f0fdff;
	border: 2px dashed var(--color-main-facilitame);
	border-radius: 10px;
	margin-bottom: 0.75rem;
}

#client-subtype-fields {
	padding: 0.875rem;
	background: #f0fdf4;
	border: 2px dashed #10b981;
	border-radius: 10px;
	margin-bottom: 0.75rem;
}

@media (max-width: 991px) {
	.signup-aside-facilitame {
		min-height: 220px;
	}
	
	.signup-logo-icon {
		width: 60px;
		height: 60px;
	}
	
	.signup-logo-text {
		max-width: 180px;
	}
	
	.signup-tagline {
		font-size: 1.25rem;
	}
}

.row.g-3 {
	--bs-gutter-y: 0;
}
</style>

<div class="d-flex flex-column flex-root" id="kt_app_root">
	<div class="d-flex flex-column flex-lg-row flex-column-fluid min-vh-100">
		
		<div class="d-flex flex-lg-row-fluid w-lg-50 signup-aside-facilitame">
			<div class="d-flex flex-column flex-center p-10 w-100">
				<div class="mb-8">
					<img class="mx-auto mw-100 signup-logo-icon" 
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-f-negra-fondo-transp.png" ?>" 
					     alt="Facilítame Logo Icon" />
				</div>
				<div class="mb-8">
					<img class="mx-auto mw-100 signup-logo-text" 
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-letras-negras.png" ?>" 
					     alt="Facilítame Logo" />
				</div>
				<div class="text-center">
					<h2 class="signup-tagline">Únete a Facilítame</h2>
					<p class="signup-subtitle">Simplifica tu gestión administrativa hoy</p>
				</div>
			</div>
		</div>
		
		<div class="d-flex flex-column flex-lg-row-fluid w-lg-50 signup-form-side">
			<div class="d-flex flex-center flex-column w-100 py-10 px-5">
				<div class="signup-form-container">
					
					<form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="login" action="api/sign-up">
						
						<div class="text-center mb-6">
							<h1 class="signup-form-title">Crea tu cuenta</h1>
							<p class="signup-form-subtitle">Completa el formulario para comenzar</p>
						</div>

						<div class="fv-row mb-3">
							<label class="form-label-facilitame">Código promocional</label>
							<input type="text" 
							       placeholder="¿Tienes un código?" 
							       name="sales_code" 
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm" 
							       value="<?php secho($code) ?>" 
							       <?php echo $code_readonly ?>>
						</div>

						<div class="fv-row mb-3">
							<label class="form-label-facilitame required">Tipo de cuenta</label>
							<select name="role" id="role-id" class="form-select form-select-facilitame-sm">
								<option value="" disabled selected>Selecciona tipo</option>
								<option value="autonomo">Autónomo</option>
								<option value="empresa">Empresa</option>
								<option value="particular">Particular</option>
								<option value="asesoria">Asesoría</option>
							</select>
						</div>
						
						<div id="asesoria-fields" style="display:none;">
							<div class="fv-row mb-3">
								<label class="form-label-facilitame required">CIF</label>
								<input type="text" 
								       placeholder="B12345678" 
								       name="cif" 
								       id="cif"
								       class="form-control form-control-facilitame-sm"
								       maxlength="9">
							</div>
							<div class="fv-row mb-3">
								<label class="form-label-facilitame required">Razón Social</label>
								<input type="text" 
								       placeholder="Nombre de la empresa" 
								       name="razon_social" 
								       id="razon_social"
								       class="form-control form-control-facilitame-sm">
							</div>
							<div class="fv-row mb-3">
								<label class="form-label-facilitame required">Dirección</label>
								<input type="text" 
								       placeholder="Dirección completa" 
								       name="direccion" 
								       id="direccion"
								       class="form-control form-control-facilitame-sm">
							</div>
							<div class="fv-row mb-3">
								<label class="form-label-facilitame required">Email de empresa</label>
								<input type="email" 
								       placeholder="contacto@asesoria.com" 
								       name="email_empresa" 
								       id="email_empresa"
								       class="form-control form-control-facilitame-sm">
							</div>
							<div class="fv-row mb-3">
								<label class="form-label-facilitame required">Plan</label>
								<select name="plan" id="plan" class="form-select form-select-facilitame-sm">
									<option value="" disabled selected>Selecciona un plan</option>
									<option value="gratuito">Gratuito</option>
									<option value="basic">Basic - 300 euros/año</option>
									<option value="estandar">Estándar - 650 euros/año</option>
									<option value="pro">Pro - 1799 euros/año</option>
									<option value="premium">Premium - 2799 euros/año</option>
									<option value="enterprise">Enterprise - 5799 euros/año</option>
								</select>
							</div>
						</div>

						<div class="row g-3">
							<div class="col-md-6">
								<div class="fv-row mb-3">
									<label class="form-label-facilitame required">Nombre</label>
									<input type="text" 
									       placeholder="Tu nombre" 
									       name="name" 
									       id="name"
									       autocomplete="off" 
									       class="form-control form-control-facilitame-sm">
								</div>
							</div>
							<div class="col-md-6">
								<div class="fv-row mb-3">
									<label class="form-label-facilitame required">Apellido</label>
									<input type="text" 
									       placeholder="Tu apellido" 
									       name="lastname" 
									       id="lastname"
									       autocomplete="off" 
									       class="form-control form-control-facilitame-sm">
								</div>
							</div>
						</div>

						<div class="fv-row mb-3" id="nif-field">
							<label class="form-label-facilitame">NIF / CIF</label>
							<input type="text" 
							       placeholder="NIF / CIF" 
							       name="nif_cif" 
							       id="nif-cif"
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm">
						</div>

						<div class="row g-3">
							<div class="col-md-6">
								<div class="fv-row mb-3">
									<label class="form-label-facilitame required">Provincia</label>
									<select name="region_code" id="region-id" class="form-select form-select-facilitame-sm">
										<option value="" disabled selected>Provincia</option>
										<?php foreach ($regions_options as $code => $name) : ?>
											<option value="<?php echo $code ?>"><?php echo $name ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="fv-row mb-3">
									<label class="form-label-facilitame required">Teléfono</label>
									<input type="tel" 
									       placeholder="600 123 456" 
									       name="phone" 
									       autocomplete="off" 
									       class="form-control form-control-facilitame-sm">
								</div>
							</div>
						</div>

						<div class="fv-row mb-3" style="display:none">
							<input type="text" 
							       name="referal_id" 
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm" 
							       value="<?php secho($referal) ?>" 
							       readonly>
						</div>
						
						<!-- Código de Asesoría (visible para clientes) -->
						<div class="fv-row mb-3" id="advisory-code-field" style="display:none;">
							<label class="form-label-facilitame">Código de Asesoría (Opcional)</label>
							<input type="text" 
							       placeholder="Ej: ASE-B12345678" 
							       name="advisory_code" 
							       id="advisory_code"
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm" 
							       value="<?php secho($advisory_code) ?>">
							<div class="form-text fs-8 text-muted">Si tu asesoría te proporcionó un código, ingrésalo aquí</div>
						</div>

						<!-- Subtipos de cliente (si tiene código de asesoría) -->
						<div id="client-subtype-fields" style="display:none;">
							
							<!-- Para Autónomos -->
							<div class="fv-row mb-3" id="autonomo-size-field" style="display:none;">
								<label class="form-label-facilitame required">Tamaño del Negocio</label>
								<select name="client_subtype" id="client_subtype_autonomo" class="form-select form-select-facilitame-sm">
									<option value="">Selecciona...</option>
									<option value="1-10">1 a 10 empleados</option>
									<option value="10-50">10 a 50 empleados</option>
									<option value="50+">Más de 50 empleados</option>
								</select>
							</div>
							
							<!-- Para Empresas -->
							<div class="fv-row mb-3" id="empresa-size-field" style="display:none;">
								<label class="form-label-facilitame required">Tamaño de la Empresa</label>
								<select name="client_subtype" id="client_subtype_empresa" class="form-select form-select-facilitame-sm">
									<option value="">Selecciona...</option>
									<option value="0-10">0 a 10 empleados</option>
									<option value="10-50">10 a 50 empleados</option>
									<option value="50-250">50 a 250 empleados</option>
									<option value="250+">Más de 250 empleados</option>
								</select>
							</div>
							
						</div>

						<div class="fv-row mb-3">
							<label class="form-label-facilitame required">Email</label>
							<input type="email" 
							       placeholder="tu@email.com" 
							       name="email" 
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm" />
						</div>

						<div class="fv-row mb-3" data-kt-password-meter="true">
							<label class="form-label-facilitame required">Contraseña</label>
							<div class="mb-1">
								<div class="position-relative mb-2">
									<input class="form-control form-control-facilitame-sm" 
									       type="password" 
									       placeholder="********" 
									       name="password" 
									       autocomplete="off" 
									       style="padding-right: 3rem;" />
									<span class="btn btn-sm btn-icon position-absolute translate-middle-y top-50 end-0 me-2" 
									      data-kt-password-meter-control="visibility"
									      style="z-index: 10;">
										<i class="ki-outline ki-eye-slash fs-5"></i>
										<i class="ki-outline ki-eye fs-5 d-none"></i>
									</span>
								</div>
								
								<div class="d-flex align-items-center mb-1" data-kt-password-meter-control="highlight">
									<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
									<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
									<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
									<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
								</div>
								
								<div class="text-muted fs-8">Mín. 8 caracteres con letras y números</div>
							</div>
						</div>

						<div class="fv-row mb-3">
							<label class="form-label-facilitame required">Confirmar contraseña</label>
							<input placeholder="Repite tu contraseña" 
							       name="confirm-password" 
							       type="password" 
							       autocomplete="off" 
							       class="form-control form-control-facilitame-sm" />
						</div>

						<div class="fv-row mb-4">
							<label class="form-check form-check-custom form-check-sm form-check-solid">
								<input class="form-check-input" type="checkbox" name="privacy-policy" value="1" />
								<span class="form-check-label fw-semibold text-gray-700 fs-8 ms-2">
									Acepto la <a href="/terms" target="_blank" class="link-facilitame">política de privacidad</a>
								</span>
							</label>
						</div>

						<div class="d-grid mb-4">
							<button type="submit" id="kt_sign_up_submit" class="btn btn-primary-facilitame">
								<span class="indicator-label">Crear cuenta</span>
								<span class="indicator-progress">
									Creando...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
								</span>
							</button>
						</div>

						<div class="text-center">
							<span class="text-gray-600 fw-semibold fs-7">¿Ya tienes cuenta?</span>
							<a href="login" class="link-facilitame fw-bold fs-7 ms-1">Inicia sesión</a>
						</div>

					</form>
					
				</div>
			</div>
		</div>
		
	</div>
</div>

<script>
// Mostrar/ocultar campos según tipo de cuenta
document.getElementById("role-id").addEventListener("change", function() {
	var role = this.value;
	var asesoriaFields = document.getElementById("asesoria-fields");
	var nifField = document.getElementById("nif-field");
	var advisoryCodeField = document.getElementById("advisory-code-field");
	var lastnameWrapper = document.getElementById("lastname").closest(".fv-row");
	
	// Reset todo
	asesoriaFields.style.display = "none";
	advisoryCodeField.style.display = "none";
	nifField.style.display = "block";
	lastnameWrapper.style.display = "block";
	
	if (role === "asesoria") {
		// Asesoría
		asesoriaFields.style.display = "block";
		nifField.style.display = "none";
	} else if (role === "empresa") {
		// Empresa
		lastnameWrapper.style.display = "none";
		advisoryCodeField.style.display = "block";
	} else if (role === "particular") {
		// Particular
		nifField.style.display = "none";
		advisoryCodeField.style.display = "block";
	} else if (role === "autonomo") {
		// Autónomo
		advisoryCodeField.style.display = "block";
	}
	
	// Verificar si debe mostrar subtipos
	checkSubtypeFields();
});

// Detectar cambios en el código de asesoría
document.getElementById('advisory_code').addEventListener('input', checkSubtypeFields);

// Función para verificar si mostrar campos de subtipo
function checkSubtypeFields() {
	var code = document.getElementById('advisory_code').value.trim();
	var role = document.getElementById('role-id').value;
	var subtypeFields = document.getElementById('client-subtype-fields');
	
	if (!subtypeFields) return;
	
	var autonomoField = document.getElementById('autonomo-size-field');
	var empresaField = document.getElementById('empresa-size-field');
	
	// Ocultar todos
	subtypeFields.style.display = 'none';
	if (autonomoField) autonomoField.style.display = 'none';
	if (empresaField) empresaField.style.display = 'none';
	
	// Si tiene código Y es autónomo o empresa
	if (code && (role === 'autonomo' || role === 'empresa')) {
		subtypeFields.style.display = 'block';
		
		if (role === 'autonomo' && autonomoField) {
			autonomoField.style.display = 'block';
		} else if (role === 'empresa' && empresaField) {
			empresaField.style.display = 'block';
		}
	}
}

// AL CARGAR LA PÁGINA: Si viene código en la URL, mostrarlo
window.addEventListener('DOMContentLoaded', function() {
	var advisoryCode = document.getElementById('advisory_code').value.trim();
	
	if (advisoryCode) {
		// Si tiene código, mostrar el campo
		var advisoryCodeField = document.getElementById('advisory-code-field');
		if (advisoryCodeField) {
			advisoryCodeField.style.display = 'block';
		}
		
		// OCULTAR la opción "Asesoría" del select
		var roleSelect = document.getElementById('role-id');
		var asesoriaOption = roleSelect.querySelector('option[value="asesoria"]');
		if (asesoriaOption) {
			asesoriaOption.remove(); // Eliminar completamente la opción
		}
		
		// Si ya hay un rol seleccionado, trigger change
		if (roleSelect.value) {
			roleSelect.dispatchEvent(new Event('change'));
		}
	}
});
</script>