<?php
$info = json_decode(controller(), true);

// Si requiere establecer contraseña, mostrar formulario
if (isset($info["requires_password"]) && $info["requires_password"]) {
    $scripts = ["activate-with-password"];
    $token = htmlspecialchars($_GET['token'] ?? '');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <base href="" />
        <title>Activar Cuenta - <?php echo COMPANY_NAME ?></title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="shortcut icon" href="assets/media/bold/favicon.png" />
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" />
        <link href="assets/css/bold.css" rel="stylesheet" />
        <link href="assets/css/login.css" rel="stylesheet" />
    </head>
    <body id="kt_body" class="app-blank">
        <div class="d-flex flex-column flex-root min-vh-100">
            <div class="d-flex flex-column flex-lg-row flex-column-fluid">
                
                <!-- Lado izquierdo -->
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
                            <h2 class="login-tagline">Tu gestoría digital</h2>
                            <p class="login-subtitle">Activa tu cuenta y comienza</p>
                        </div>
                    </div>
                </div>

                <!-- Lado derecho - formulario -->
                <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 login-form-side">
                    <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                        <div class="login-form-container">
                            
                            <form class="form w-100" novalidate id="kt_activate_form" action="/api/activate-with-password" method="post">
                                <div class="text-center mb-10">
                                    <h1 class="login-form-title">Activa tu cuenta</h1>
                                    <p class="login-form-subtitle">Establece tu contraseña para acceder</p>
                                </div>

                                <input type="hidden" name="token" value="<?php echo $token ?>" />

                                <div class="fv-row mb-4">
                                    <label class="form-label-facilitame">Nueva Contraseña</label>
                                    <div class="input-group-facilitame">
                                        <span class="input-icon">
                                            <i class="ki-outline ki-lock fs-4 text-facilitame"></i>
                                        </span>
                                        <input type="password" 
                                               placeholder="Mínimo 8 caracteres" 
                                               name="password" 
                                               autocomplete="off" 
                                               class="form-control form-control-facilitame" />
                                    </div>
                                </div>

                                <div class="fv-row mb-6">
                                    <label class="form-label-facilitame">Confirmar Contraseña</label>
                                    <div class="input-group-facilitame">
                                        <span class="input-icon">
                                            <i class="ki-outline ki-lock fs-4 text-facilitame"></i>
                                        </span>
                                        <input type="password" 
                                               placeholder="Repite tu contraseña" 
                                               name="password_confirm" 
                                               autocomplete="off" 
                                               class="form-control form-control-facilitame" />
                                    </div>
                                </div>

                                <div class="d-grid mb-5">
                                    <button type="submit" id="kt_activate_submit" class="btn btn-primary-facilitame btn-lg">
                                        <span class="indicator-label">
                                            <i class="ki-outline ki-check-circle fs-3 text-white me-2"></i>
                                            Activar mi cuenta
                                        </span>
                                        <span class="indicator-progress">
                                            Activando...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </div>

                                <div class="text-center">
                                    <span class="text-gray-600 fw-semibold">¿Ya tienes cuenta?</span>
                                    <a href="/login" class="link-facilitame fw-bold ms-2">Inicia sesión</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <script src="assets/js/custom/activate-with-password.js"></script> 

    </body>
    </html>
    <?php
    exit;
}

// Si NO requiere contraseña, redirigir al login con mensaje
set_toastr($info["status"], $info["message_html"]);
header("Location: login");
exit;
?>