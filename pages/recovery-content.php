<script>
	if (window.top != window.self) { 
		window.top.location.replace(window.self.location.href); 
	}
</script>

<div class="d-flex flex-column flex-root" id="kt_app_root">
	<div class="d-flex flex-column flex-lg-row flex-column-fluid min-vh-100">
		
		<!-- Aside - Branding -->
		<div class="d-flex flex-lg-row-fluid w-lg-50 login-aside-facilitame">
			<div class="d-flex flex-column flex-center p-10 w-100">
				<div class="mb-10 mb-lg-20">
					<img class="mx-auto mw-100 login-logo-icon" 
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-f-negra-fondo-transp.png" ?>" 
					     alt="Facilítame Logo" />
				</div>
				<div class="mb-10 mb-lg-20">
					<img class="mx-auto mw-100 login-logo-text" 
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-letras-negras.png" ?>" 
					     alt="Facilítame" />
				</div>
				<div class="text-center">
					<h2 class="login-tagline">¿Olvidaste tu contraseña?</h2>
					<p class="login-subtitle">No te preocupes, te ayudamos a recuperarla</p>
				</div>
			</div>
		</div>
		
		<!-- Body - Formulario -->
		<div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 login-form-side">
			<div class="d-flex flex-center flex-column flex-lg-row-fluid">
				<div class="login-form-container">
					
					<form class="form w-100" novalidate="novalidate" id="kt_recovery_form" action="api/recovery">
						
						<div class="text-center mb-10">
							<h1 class="login-form-title">Recuperar contraseña</h1>
							<p class="login-form-subtitle">Te enviaremos un enlace para restablecerla</p>
						</div>

						<div class="fv-row mb-6">
							<label class="form-label-facilitame">Correo electrónico</label>
							<div class="input-group-facilitame">
								<span class="input-icon">
									<i class="ki-outline ki-sms fs-4 text-facilitame"></i>
								</span>
								<input type="email" 
								       placeholder="tu@email.com" 
								       name="email" 
								       autocomplete="off" 
								       class="form-control form-control-facilitame" />
							</div>
						</div>

						<div class="d-grid mb-5">
							<button type="submit" id="kt_recovery_submit" class="btn btn-primary-facilitame btn-lg">
								<span class="indicator-label">
									<i class="ki-outline ki-send fs-3 text-white me-2"></i>
									Enviar enlace
								</span>
								<span class="indicator-progress">
									Enviando...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
								</span>
							</button>
						</div>

						<div class="text-center">
							<span class="text-gray-600 fw-semibold">¿Recordaste tu contraseña?</span>
							<a href="login" class="link-facilitame fw-bold ms-2">
								Volver al inicio
							</a>
						</div>

					</form>
					
				</div>
			</div>
		</div>
		
	</div>
</div>