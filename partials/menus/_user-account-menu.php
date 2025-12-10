<!--begin::User account menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px" 
     data-kt-menu="true">
    
    <!--begin::Header-->
    <div class="user-menu-header">
        <div class="d-flex flex-column align-items-center text-center">
            <div class="user-avatar mb-3">
                <img alt="<?= e(username()) ?>" 
                     src="<?= e(MEDIA_DIR . "/" . USER["profile_picture"]) ?>"/>
            </div>
            <h5 class="text-white fw-bold mb-1 fs-6"><?php secho(username()) ?></h5>
            <p class="text-white opacity-85 fs-7 mb-2 text-truncate" style="max-width: 220px;">
                <?php secho(USER["email"]) ?>
            </p>
            <span class="user-role-badge">
                <?php 
                if (admin()) echo 'Administrador';
                elseif (proveedor()) echo 'Proveedor';
                elseif (comercial()) echo 'Comercial';
                elseif (asesoria()) echo 'Asesoría';
                else echo 'Cliente';
                ?>
            </span>
        </div>
    </div>
    <!--end::Header-->
    
    <!--begin::Menu items-->
    <div class="user-menu-body">
        <?php if (cliente()) : ?>
            <a href="/profile" class="user-menu-item">
                <div class="user-menu-icon">
                    <i class="ki-outline ki-profile-circle"></i>
                </div>
                <span class="user-menu-text">Mi Perfil</span>
                <i class="ki-outline ki-right fs-6 ms-auto"></i>
            </a>
        <?php endif; ?>
        
        <div class="user-menu-separator"></div>
        
        <button type="button" id="btn-logout-menu" class="user-menu-item user-menu-logout">
            <div class="user-menu-icon icon-logout">
                <i class="ki-outline ki-exit-right"></i>
            </div>
            <span class="user-menu-text">Cerrar Sesión</span>
            <i class="ki-outline ki-right fs-6 ms-auto"></i>
        </button>
    </div>
    <!--end::Menu items-->
</div>
<!--end::User account menu-->

<script>
(function() {
    var logoutBtn = document.getElementById('btn-logout-menu');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('/api/logout', { method: 'GET', credentials: 'same-origin' })
            .then(function() { window.location.href = '/login'; })
            .catch(function() { window.location.href = '/login'; });
        });
    }
})();
</script>