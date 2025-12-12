<?php
if (!isset($_GET["token"]) || empty($_GET["token"])) {
    header("Location:login?r=1498641088");
    exit;
}

$query = "SELECT * FROM `password_recovery_tokens` WHERE token = :token";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":token", $_GET["token"]);
$stmt->execute();
$res = $stmt->fetch();

if ($res === false) {
    header("Location:login?r=2053898411");
    exit;
}

if (!is_null($res["used_at"])) {
    set_toastr("ko", "Este enlace de recuperación ya ha sido utilizado.");
    header("Location:login?r=2878721314");
    exit;
}

$now = new DateTime("now");
$token_expires_at = new DateTime("@" . strtotime($res["expires_at"]));

if ($now > $token_expires_at) {
    set_toastr("ko", "Este enlace de recuperación ha caducado.");
    header("Location:login?r=1949288752");
    exit;
}
?>

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
					<img class="mx-auto login-logo-icon"
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-f-negra-fondo-transp.png" ?>"
					     alt="Facilítame Logo" />
				</div>
				<div class="mb-12 mb-lg-20">
					<img class="mx-auto login-logo-text"
					     src="<?php echo MEDIA_DIR . "/logo-facilitame-letras-negras.png" ?>"
					     alt="Facilítame" />
				</div>
				<div class="text-center">
					<h2 class="login-tagline">Crea tu nueva contraseña</h2>
					<p class="login-subtitle">Elige una contraseña segura para tu cuenta</p>
				</div>
			</div>
		</div>
		
		<!-- Body - Formulario -->
		<div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 login-form-side">
			<div class="d-flex flex-center flex-column flex-lg-row-fluid">
				<div class="login-form-container">
					
					<form class="form w-100" novalidate="novalidate" id="kt_restore_form" data-kt-redirect-url="login" action="api/restore">
						
						<div class="text-center mb-10">
							<h1 class="login-form-title">Nueva contraseña</h1>
							<p class="login-form-subtitle">Introduce tu nueva contraseña</p>
						</div>

						<input type="hidden" name="token" value="<?php secho($_GET["token"]) ?>">

						<div class="fv-row mb-4">
							<label class="form-label-facilitame">Nueva contraseña</label>
							<div class="input-group-facilitame">
								<span class="input-icon">
									<i class="ki-outline ki-lock fs-4 text-facilitame"></i>
								</span>
								<input type="password" 
								       placeholder="••••••••" 
								       name="password" 
								       autocomplete="off" 
								       class="form-control form-control-facilitame" />
							</div>
							<div class="text-muted fs-7 mt-2">Mínimo 8 caracteres con letras y números</div>
						</div>

						<div class="fv-row mb-6">
							<label class="form-label-facilitame">Confirmar contraseña</label>
							<div class="input-group-facilitame">
								<span class="input-icon">
									<i class="ki-outline ki-lock-2 fs-4 text-facilitame"></i>
								</span>
								<input type="password" 
								       placeholder="••••••••" 
								       name="confirm-password" 
								       autocomplete="off" 
								       class="form-control form-control-facilitame" />
							</div>
						</div>

						<div class="d-grid mb-5">
							<button type="submit" id="kt_restore_submit" class="btn btn-primary-facilitame btn-lg">
								<span class="indicator-label">
									<i class="ki-outline ki-shield-tick fs-3 text-white me-2"></i>
									Restablecer contraseña
								</span>
								<span class="indicator-progress">
									Guardando...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
								</span>
							</button>
						</div>

						<div class="text-center">
							<a href="login" class="link-facilitame fw-bold">
								<i class="ki-outline ki-arrow-left fs-5 me-1"></i>
								Volver al inicio de sesión
							</a>
						</div>

					</form>
					
				</div>
			</div>
		</div>
		
	</div>
</div>