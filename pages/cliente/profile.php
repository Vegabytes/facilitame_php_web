<?php
/**
 * Perfil de Usuario - Cliente
 * Usa clases de dashboard-common.css y dashboard-cliente.css
 */
$currentPage = 'profile';
$scripts = ["profile"];
?>

<div id="facilita-app">
    <div class="profile-page">
        <div class="row g-3">
            
            <!-- Sidebar: Info del usuario -->
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        
                        <!-- Avatar y datos principales -->
                        <div class="profile-sidebar">
                            <div class="profile-avatar-wrapper">
                                <img src="<?php echo MEDIA_DIR . "/" . USER["profile_picture"] ?>" 
                                     alt="<?php echo USER["name"]; ?>" 
                                     class="profile-avatar-large">
                            </div>
                            
                            <h5 class="profile-name"><?php secho(USER["name"] . " " . USER["lastname"]) ?></h5>
                            
                            <span class="profile-role"><?php secho(display_role()) ?></span>
                            
                            <button type="button" class="btn btn-light btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modal-user-profile-picture-update">
                                <i class="ki-outline ki-pencil me-1"></i> Cambiar foto
                            </button>
                        </div>
                        
                        <hr class="my-3">
                        
                        <!-- Info items -->
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="ki-outline ki-sms"></i>
                            </div>
                            <div>
                                <div class="profile-info-label">Email</div>
                                <div class="profile-info-value">
                                    <a href="mailto:<?php secho(USER['email']); ?>"><?php secho(USER["email"]) ?></a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($user["phone"])) : ?>
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="ki-outline ki-phone"></i>
                            </div>
                            <div>
                                <div class="profile-info-label">Teléfono</div>
                                <div class="profile-info-value">
                                    <a href="tel:<?php secho($user['phone']); ?>"><?php secho($user["phone"]) ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="ki-outline ki-badge"></i>
                            </div>
                            <div>
                                <div class="profile-info-label">ID Usuario</div>
                                <div class="profile-info-value">
                                    <span class="badge-status badge-status-primary">#<?php echo $user["id"] ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($user["created_at"])) : ?>
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="ki-outline ki-calendar"></i>
                            </div>
                            <div>
                                <div class="profile-info-label">Miembro desde</div>
                                <div class="profile-info-value"><?php echo date("d/m/Y", strtotime($user["created_at"])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user["allow_invoice_access"] != "1") : ?>
                        <hr class="my-3">
                        
                        <!-- Consentimiento pendiente -->
                        <form action="api/user-profile-invoice-acces-grant" data-reload="1" id="form-user-profile-invoice-access-grant">
                            <div class="consent-alert">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <i class="ki-outline ki-information-2 text-warning"></i>
                                    <div>
                                        <div class="fw-bold text-dark mb-2" style="font-size: 0.8125rem;">Consentimiento requerido</div>
                                        <div class="form-check mb-2 fv-row">
                                            <input class="form-check-input" type="checkbox" name="allow-invoice-acces" value="1" id="consent-checkbox" required>
                                            <label class="form-check-label" for="consent-checkbox" style="font-size: 0.8125rem;">
                                                Acepto el <a href="/terms" target="_blank" class="text-primary">acceso a mis facturas</a>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100 bold-submit">
                                    <i class="ki-outline ki-check me-1"></i> Conceder acceso
                                </button>
                            </div>
                        </form>
                        
                        <?php else : ?>
                        <hr class="my-3">
                        
                        <div class="consent-alert consent-alert-success">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ki-outline ki-check-circle text-success"></i>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.8125rem;">Facturas autorizadas</div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo date("d/m/Y", strtotime($user["allow_invoice_access_granted_at"])) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
            
            <!-- Main: Formularios -->
            <div class="col-xl-9 col-lg-8">
                
                <!-- Datos personales -->
                <div class="profile-form-section mb-3">
                    <div class="profile-form-header">
                        <div class="profile-form-icon profile-form-icon-primary">
                            <i class="ki-outline ki-user-edit"></i>
                        </div>
                        <div>
                            <h6 class="profile-form-title">Datos Personales</h6>
                            <p class="profile-form-subtitle">Actualiza tu información</p>
                        </div>
                    </div>
                    
                    <form action="api/user-profile-details-update" data-reload="1" id="form-user-profile-details-update">
                        <div class="profile-form-body">
                            <div class="row g-3">
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Nombre</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $user["name"] ?>" required>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Apellidos</label>
                                    <input type="text" class="form-control" name="lastname" value="<?php echo $user["lastname"] ?>" required>
                                </div>
                                <div class="col-12 fv-row">
                                    <label class="form-label required">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo USER["email"] ?>" readonly>
                                    <div class="form-text">El email no se puede modificar</div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-form-footer">
                            <button type="submit" class="btn btn-primary bold-submit">
                                <i class="ki-outline ki-check me-1"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Cambiar contraseña -->
                <div class="profile-form-section">
                    <div class="profile-form-header">
                        <div class="profile-form-icon profile-form-icon-danger">
                            <i class="ki-outline ki-lock"></i>
                        </div>
                        <div>
                            <h6 class="profile-form-title">Seguridad</h6>
                            <p class="profile-form-subtitle">Cambia tu contraseña</p>
                        </div>
                    </div>
                    
                    <form action="api/user-profile-password-update" data-reload="0" id="form-user-profile-password-update">
                        <div class="profile-form-body">
                            <div class="row g-3">
                                <div class="col-12 fv-row">
                                    <label class="form-label required">Contraseña actual</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Nueva contraseña</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Confirmar contraseña</label>
                                    <input type="password" class="form-control" name="new_password_confirm" required>
                                </div>
                            </div>
                        </div>
                        <div class="profile-form-footer">
                            <button type="submit" class="btn btn-danger bold-submit">
                                <i class="ki-outline ki-shield-tick me-1"></i> Cambiar contraseña
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
            
        </div>
    </div>
</div>

<!-- Modal actualizar imagen -->
<div class="modal fade" tabindex="-1" id="modal-user-profile-picture-update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-picture"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Foto de Perfil</h5>
                        <p class="text-muted fs-7 mb-0">Actualiza tu imagen</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="api/user-profile-picture-update" data-reload="1">
                <div class="modal-body pt-4">
                    <div class="text-center">
                        <div class="image-input image-input-outline mx-auto" data-kt-image-input="true" style="background-image: url(/assets/media/svg/avatars/blank.svg)">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: url(<?php echo MEDIA_DIR . "/" . USER["profile_picture"] ?>); border-radius: 50%;"></div>
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Cambiar imagen">
                                <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                <input type="hidden" name="avatar_remove" />
                            </label>
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancelar">
                                <i class="ki-outline ki-cross fs-4"></i>
                            </span>
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Eliminar">
                                <i class="ki-outline ki-trash fs-4"></i>
                            </span>
                        </div>
                        <div class="form-text mt-3">PNG, JPG, JPEG — Máx. 2MB</div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <?php if (!guest()) : ?>
                    <button type="submit" class="btn btn-primary bold-submit-file">
                        <i class="ki-outline ki-check me-1"></i> Guardar
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>