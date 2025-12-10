<?php
// ========== HELPER PARA ESCAPAR HTML ==========
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ========== DATOS DE ASESORÍA PARA CLIENTE ==========
$has_advisory = false;
$unread_communications = 0;
$pending_appointments_cliente = 0;

if (cliente() && !guest() && isset($pdo)) {
    try {
        $stmt_adv = $pdo->prepare("SELECT advisory_id FROM customers_advisories WHERE customer_id = ?");
        $stmt_adv->execute([USER['id']]);
        $advisory_data = $stmt_adv->fetch();
        $has_advisory = $advisory_data ? true : false;

        if ($has_advisory) {
            // Comunicaciones sin leer
            $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM advisory_communication_recipients acr WHERE acr.customer_id = ? AND acr.is_read = 0");
            $stmt_unread->execute([USER['id']]);
            $unread_communications = (int) $stmt_unread->fetchColumn();

            // Citas pendientes de confirmar por el cliente
            $stmt_apts = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE customer_id = ? AND needs_confirmation_from = 'customer'");
            $stmt_apts->execute([USER['id']]);
            $pending_appointments_cliente = (int) $stmt_apts->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Sidebar advisory query error: " . $e->getMessage());
    }
}

// ========== DATOS DE MENSAJES SIN LEER PARA ASESORÍA ==========
$unread_messages_advisory = 0;
$pending_appointments_advisory = 0;
$pending_invoices_advisory = 0;

if (asesoria() && isset($pdo)) {
    try {
        $stmt_adv = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
        $stmt_adv->execute([USER['id']]);
        $adv_data = $stmt_adv->fetch();

        if ($adv_data) {
            // Mensajes sin leer en citas
            $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM advisory_messages WHERE advisory_id = ? AND sender_type = 'customer' AND is_read = 0");
            $stmt_unread->execute([$adv_data['id']]);
            $unread_messages_advisory = (int) $stmt_unread->fetchColumn();

            // Citas pendientes de confirmar por la asesoría
            $stmt_apts = $pdo->prepare("SELECT COUNT(*) FROM advisory_appointments WHERE advisory_id = ? AND needs_confirmation_from = 'advisory'");
            $stmt_apts->execute([$adv_data['id']]);
            $pending_appointments_advisory = (int) $stmt_apts->fetchColumn();

            // Facturas recibidas pendientes de revisar (status = 'pendiente')
            $stmt_inv = $pdo->prepare("SELECT COUNT(*) FROM advisory_invoices WHERE advisory_id = ? AND status = 'pendiente'");
            $stmt_inv->execute([$adv_data['id']]);
            $pending_invoices_advisory = (int) $stmt_inv->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Sidebar advisory messages query error: " . $e->getMessage());
    }
}
?>

<aside id="app_sidebar" class="app-sidebar" role="navigation" aria-label="Menú principal">
    <button class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Alternar menú lateral" aria-expanded="true" aria-controls="app_sidebar">
        <i class="ki-outline ki-double-left fs-2" id="toggle-icon"></i>
    </button>
    
    <a href="/home" class="sidebar-logo d-flex align-items-center justify-content-center flex-column" title="Ir al inicio">
        <img src="/assets/media/bold/logo-facilitame-letras-blancas.png" alt="Logo Facilítame" class="sidebar-logo-img sidebar-logo-full" />
        <img src="/assets/media/bold/logo-facilitame-f-negra-fondo-transp.png" alt="F" class="sidebar-logo-img sidebar-logo-mini" />
    </a>
    
    <nav>
        <ul class="menu-list" role="menu">
            
            <?php if (cliente()): ?>
                <li class="menu-section"><span class="menu-section-text">Principal</span></li>
                <li class="menu-item <?= ($currentPage === 'home') ? 'active' : '' ?>" role="none">
                    <a href="/home" class="menu-link" role="menuitem" data-tooltip="Inicio" <?= ($currentPage === 'home') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-home menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'notifications') ? 'active' : '' ?>" role="none">
                    <a href="/notifications" class="menu-link" role="menuitem" data-tooltip="Notificaciones" <?= ($currentPage === 'notifications') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-notification-bing menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Notificaciones</span>
                        <?php if (isset(NOTIFICATIONS['unread']) && NOTIFICATIONS['unread'] > 0): ?>
                        <span class="badge badge-circle badge-danger"><?= NOTIFICATIONS['unread'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'services') ? 'active' : '' ?>" role="none">
                    <a href="/services" class="menu-link" role="menuitem" data-tooltip="Buscar servicios" <?= ($currentPage === 'services') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-search-list menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Buscar servicios</span>
                    </a>
                </li>
                
                <li class="menu-section"><span class="menu-section-text">Mis Gestiones</span></li>
                <li class="menu-item <?= ($currentPage === 'my-services') ? 'active' : '' ?>" role="none">
                    <a href="/my-services" class="menu-link" role="menuitem" data-tooltip="Mis solicitudes" <?= ($currentPage === 'my-services') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-folder menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Mis solicitudes</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'my-incidents') ? 'active' : '' ?>" role="none">
                    <a href="/my-incidents" class="menu-link" role="menuitem" data-tooltip="Mis incidencias" <?= ($currentPage === 'my-incidents') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-information-5 menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Mis incidencias</span>
                    </a>
                </li>
                <?php if (!guest()): ?>
                <li class="menu-item <?= ($currentPage === 'invoices') ? 'active' : '' ?>" role="none">
                    <a href="/invoices" class="menu-link" role="menuitem" data-tooltip="Mis facturas" <?= ($currentPage === 'invoices') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-credit-cart menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Mis facturas</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($has_advisory): ?>
                <li class="menu-section"><span class="menu-section-text">Mi Asesoría</span></li>
                <li class="menu-item <?= ($currentPage === 'communications') ? 'active' : '' ?>" role="none">
                    <a href="/communications" class="menu-link" role="menuitem" data-tooltip="Comunicaciones" <?= ($currentPage === 'communications') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-sms menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Comunicaciones</span>
                        <?php if ($unread_communications > 0): ?>
                        <span class="badge badge-circle badge-danger"><?= $unread_communications ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'advisory-invoices') ? 'active' : '' ?>" role="none">
                    <a href="/advisory-invoices" class="menu-link" role="menuitem" data-tooltip="Enviar Facturas" <?= ($currentPage === 'advisory-invoices') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-document menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Enviar Facturas</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'appointments') ? 'active' : '' ?>" role="none">
                    <a href="/appointments" class="menu-link" role="menuitem" data-tooltip="Solicitar Cita" <?= ($currentPage === 'appointments') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-calendar menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Solicitar Cita</span>
                        <?php if ($pending_appointments_cliente > 0): ?>
                        <span class="badge badge-circle badge-warning"><?= $pending_appointments_cliente ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                
            <?php elseif (admin()): ?>
                <li class="menu-section"><span class="menu-section-text">Principal</span></li>
                <li class="menu-item <?= ($currentPage === 'home') ? 'active' : '' ?>" role="none">
                    <a href="/home" class="menu-link" role="menuitem" data-tooltip="Inicio" <?= ($currentPage === 'home') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-home menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'log') ? 'active' : '' ?>" role="none">
                    <a href="/log" class="menu-link" role="menuitem" data-tooltip="Seguimiento" <?= ($currentPage === 'log') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-time menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Seguimiento</span>
                    </a>
                </li>
                
                <li class="menu-section"><span class="menu-section-text">Gestión</span></li>
                <li class="menu-item <?= ($currentPage === 'customers') ? 'active' : '' ?>" role="none">
                    <a href="/customers" class="menu-link" role="menuitem" data-tooltip="Clientes" <?= ($currentPage === 'customers') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-people menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Clientes</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'users-sales') ? 'active' : '' ?>" role="none">
                    <a href="/users?type=sales-rep" class="menu-link" role="menuitem" data-tooltip="Comerciales" <?= ($currentPage === 'users-sales') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-briefcase menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Comerciales</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'users-providers') ? 'active' : '' ?>" role="none">
                    <a href="/users?type=provider" class="menu-link" role="menuitem" data-tooltip="Colaboradores" <?= ($currentPage === 'users-providers') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-handcart menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Colaboradores</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'advisories' || $currentPage === 'advisory') ? 'active' : '' ?>" role="none">
                    <a href="/advisories" class="menu-link" role="menuitem" data-tooltip="Asesorías" <?= ($currentPage === 'advisories' || $currentPage === 'advisory') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-chart menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Asesorías</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'commissions') ? 'active' : '' ?>" role="none">
                    <a href="/commissions" class="menu-link" role="menuitem" data-tooltip="Comisiones" <?= ($currentPage === 'commissions') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-dollar menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Comisiones</span>
                    </a>
                </li>
                
            <?php elseif (proveedor()): ?>
                <li class="menu-section"><span class="menu-section-text">Principal</span></li>
                <li class="menu-item <?= ($currentPage === 'home') ? 'active' : '' ?>" role="none">
                    <a href="/home" class="menu-link" role="menuitem" data-tooltip="Inicio" <?= ($currentPage === 'home') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-home menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'log') ? 'active' : '' ?>" role="none">
                    <a href="/log" class="menu-link" role="menuitem" data-tooltip="Seguimiento" <?= ($currentPage === 'log') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-time menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Seguimiento</span>
                    </a>
                </li>
                
                <li class="menu-section"><span class="menu-section-text">Gestión</span></li>
                <li class="menu-item <?= ($currentPage === 'customers') ? 'active' : '' ?>" role="none">
                    <a href="/customers" class="menu-link" role="menuitem" data-tooltip="Clientes" <?= ($currentPage === 'customers') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-people menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Clientes</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'invoices') ? 'active' : '' ?>" role="none">
                    <a href="/invoices" class="menu-link" role="menuitem" data-tooltip="Facturas" <?= ($currentPage === 'invoices') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-credit-cart menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Facturas</span>
                    </a>
                </li>
                
            <?php elseif (comercial()): ?>
                <li class="menu-section"><span class="menu-section-text">Principal</span></li>
                <li class="menu-item <?= ($currentPage === 'home') ? 'active' : '' ?>" role="none">
                    <a href="/home" class="menu-link" role="menuitem" data-tooltip="Inicio" <?= ($currentPage === 'home') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-home menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'notifications') ? 'active' : '' ?>" role="none">
                    <a href="/notifications" class="menu-link" role="menuitem" data-tooltip="Notificaciones" <?= ($currentPage === 'notifications') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-notification-bing menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Notificaciones</span>
                        <?php if (isset(NOTIFICATIONS['unread']) && NOTIFICATIONS['unread'] > 0): ?>
                        <span class="badge badge-circle badge-danger"><?= NOTIFICATIONS['unread'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'log') ? 'active' : '' ?>" role="none">
                    <a href="/log" class="menu-link" role="menuitem" data-tooltip="Seguimiento" <?= ($currentPage === 'log') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-time menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Seguimiento</span>
                    </a>
                </li>
                
                <li class="menu-section"><span class="menu-section-text">Gestión</span></li>
                <li class="menu-item <?= ($currentPage === 'customers') ? 'active' : '' ?>" role="none">
                    <a href="/customers" class="menu-link" role="menuitem" data-tooltip="Clientes" <?= ($currentPage === 'customers') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-people menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Clientes</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'commissions') ? 'active' : '' ?>" role="none">
                    <a href="/commissions" class="menu-link" role="menuitem" data-tooltip="Comisiones" <?= ($currentPage === 'commissions') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-dollar menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Comisiones</span>
                    </a>
                </li>
                
            <?php elseif (asesoria()): ?>
                <li class="menu-section"><span class="menu-section-text">Principal</span></li>
                <li class="menu-item <?= ($currentPage === 'home') ? 'active' : '' ?>" role="none">
                    <a href="/home" class="menu-link" role="menuitem" data-tooltip="Inicio" <?= ($currentPage === 'home') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-home menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'notifications') ? 'active' : '' ?>" role="none">
                    <a href="/notifications" class="menu-link" role="menuitem" data-tooltip="Notificaciones" <?= ($currentPage === 'notifications') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-notification-bing menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Notificaciones</span>
                        <?php if (isset(NOTIFICATIONS['unread']) && NOTIFICATIONS['unread'] > 0): ?>
                        <span class="badge badge-circle badge-danger"><?= NOTIFICATIONS['unread'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="menu-section"><span class="menu-section-text">Gestión</span></li>
                <li class="menu-item <?= ($currentPage === 'customers') ? 'active' : '' ?>" role="none">
                    <a href="/customers" class="menu-link" role="menuitem" data-tooltip="Mis Clientes" <?= ($currentPage === 'customers') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-people menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Mis Clientes</span>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'invoices') ? 'active' : '' ?>" role="none">
                    <a href="/invoices" class="menu-link" role="menuitem" data-tooltip="Facturas Recibidas" <?= ($currentPage === 'invoices') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-document menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Facturas Recibidas</span>
                        <?php if ($pending_invoices_advisory > 0): ?>
                        <span class="badge badge-circle badge-warning"><?= $pending_invoices_advisory ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'appointments') ? 'active' : '' ?>" role="none">
                    <a href="/appointments" class="menu-link" role="menuitem" data-tooltip="Citas" <?= ($currentPage === 'appointments') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-calendar menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Citas</span>
                        <?php if ($pending_appointments_advisory > 0): ?>
                        <span class="badge badge-circle badge-warning"><?= $pending_appointments_advisory ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage === 'communications') ? 'active' : '' ?>" role="none">
                    <a href="/communications" class="menu-link" role="menuitem" data-tooltip="Comunicaciones" <?= ($currentPage === 'communications') ? 'aria-current="page"' : '' ?>>
                        <i class="ki-outline ki-sms menu-icon" aria-hidden="true"></i>
                        <span class="menu-text">Comunicaciones</span>
                        <?php if ($unread_messages_advisory > 0): ?>
                        <span class="badge badge-circle badge-danger"><?= $unread_messages_advisory ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
            
        </ul>
    </nav>
    
    <div class="sidebar-footer mt-auto d-flex justify-content-center">
        <button class="btn-footer" id="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
            <i class="ki-outline ki-exit-left fs-4 me-1" aria-hidden="true"></i>
            <span class="menu-text">Salir</span>
        </button>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('app_sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const logoutBtn = document.getElementById('logout-btn');
    const overlay = document.getElementById('sidebar-overlay');
    
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
    }
    toggleBtn.setAttribute('aria-expanded', !isCollapsed);
    
    toggleBtn.addEventListener('click', function() {
        const collapsed = sidebar.classList.toggle('collapsed');
        toggleBtn.setAttribute('aria-expanded', !collapsed);
        
        if (!collapsed) {
            sidebar.classList.add('glow');
            setTimeout(function() { sidebar.classList.remove('glow'); }, 800);
        }
        localStorage.setItem('sidebarCollapsed', collapsed);
    });
    
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fetch('/api/logout', { method: 'GET', credentials: 'same-origin' })
            .then(function() { window.location.href = '/login'; })
            .catch(function() { window.location.href = '/login'; });
    });
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }
    
    const menuLinks = document.querySelectorAll('.menu-link');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            document.querySelectorAll('.menu-item').forEach(function(item) {
                item.classList.remove('active');
            });
            const menuItem = this.closest('.menu-item');
            if (menuItem) menuItem.classList.add('active');
            
            if (window.innerWidth <= 991) {
                sidebar.classList.remove('mobile-open');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('app_sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const collapsed = sidebar.classList.toggle('collapsed');
    toggleBtn.setAttribute('aria-expanded', !collapsed);
    
    if (!collapsed) {
        sidebar.classList.add('glow');
        setTimeout(function() { sidebar.classList.remove('glow'); }, 800);
    }
    localStorage.setItem('sidebarCollapsed', collapsed);
}
</script>