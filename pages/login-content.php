<script>
	if (window.top != window.self) { 
		window.top.location.replace(window.self.location.href); 
	}
</script>

<div class="d-flex flex-column flex-root" id="kt_app_root">

	<div class="d-flex flex-column flex-lg-row flex-column-fluid min-vh-100">

		<div class="d-flex flex-lg-row-fluid w-lg-50 login-aside-facilitame" id="login-left-side">
			<div class="d-flex flex-column flex-center p-10 w-100" id="login-left-content">
				
				<div class="mb-10 mb-lg-20" id="login-logo-main-wrapper">
					<img class="mx-auto login-logo-icon"
					     id="login-logo-main"
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-f-negra-fondo-transp.png" ?>"
					     alt="Facilítame Logo Icon" />
				</div>

				<div class="mb-12 mb-lg-20" id="login-logo-text-wrapper">
					<img class="mx-auto login-logo-text"
					     id="login-logo-text"
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-letras-negras.png" ?>"
					     alt="Facilítame Logo" />
				</div>

				<div class="text-center" id="login-tagline-wrapper">
					<h2 class="login-tagline" id="login-tagline-title">Tu gestoría digital</h2>
					<p class="login-subtitle" id="login-tagline-subtitle">Simplificamos tus trámites administrativos</p>
				</div>

			</div>
		</div>

		<div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 login-form-side" id="login-right-side">

			<div class="d-flex flex-center flex-column flex-lg-row-fluid" id="login-form-wrapper">

				<div class="login-form-container" id="login-form-container">

					<form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="home" action="api/login">
						
						<div class="text-center mb-10" id="login-header">
							<h1 class="login-form-title" id="login-title">¡Bienvenido de nuevo!</h1>
							<p class="login-form-subtitle" id="login-subtitle">Inicia sesión para continuar</p>
						</div>

						<div class="fv-row mb-4" id="login-email-wrapper">
							<label class="form-label-facilitame" for="login-email-input">Correo electrónico</label>
							<div class="input-group-facilitame">
								<span class="input-icon" id="login-email-icon">
									<i class="ki-outline ki-sms fs-4 text-facilitame"></i>
								</span>
								<input type="email" 
								       placeholder="tu@email.com" 
								       name="email" 
								       id="login-email-input"
								       autocomplete="off" 
								       class="form-control form-control-facilitame" />
							</div>
						</div>

						<div class="fv-row mb-4" id="login-password-wrapper">
							<label class="form-label-facilitame" for="login-password-input">Contraseña</label>
							<div class="input-group-facilitame">
								<span class="input-icon" id="login-password-icon">
									<i class="ki-outline ki-lock fs-4 text-facilitame"></i>
								</span>
								<input type="password" 
								       placeholder="••••••••" 
								       name="password" 
								       id="login-password-input"
								       autocomplete="off" 
								       class="form-control form-control-facilitame" />
							</div>
						</div>

						<div class="d-flex justify-content-end mb-6" id="login-forgot-wrapper">
							<a href="recovery" class="link-facilitame" id="login-forgot-link">
								¿Olvidaste tu contraseña?
							</a>
						</div>

						<div class="d-grid mb-5" id="login-submit-wrapper">
							<button type="submit" id="kt_sign_in_submit" class="btn btn-primary-facilitame btn-lg">
								<span class="indicator-label" id="login-btn-text">
									<i class="ki-outline ki-entrance-right fs-3 text-white me-2"></i>
									Acceder
								</span>
								<span class="indicator-progress" id="login-btn-progress">
									Iniciando sesión...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
								</span>
							</button>
						</div>

						<div class="separator-facilitame mb-5" id="login-separator">
							<span>o</span>
						</div>

						<div class="text-center mb-4" id="login-signup-wrapper">
							<span class="text-gray-600 fw-semibold">¿Aún no tienes cuenta?</span>
							<a href="sign-up" class="link-facilitame fw-bold ms-2" id="login-signup-link">
								Regístrate gratis
							</a>
						</div>

						<div class="text-center" id="login-guest-wrapper">
							<span class="text-gray-500 fw-semibold fs-7">¿Quieres conocer Facilítame?</span>
							<a href="#" class="link-facilitame fw-bold fs-7 ms-2" id="login-guest">
								Accede como invitado
							</a>
						</div>

					</form>

				</div>

			</div>

		</div>

	</div>

</div>