<?php if (ENVIRONMENT === "DEMO") : ?>
<div style="background-color:crimson;width:100%;height:2rem;display:flex;flex-direction:row;justify-content:center;align-items:center;">
    <span style="font-size:1rem;font-weight:bold;color:white;">DEMO</span>
</div>
<?php endif; ?>

<?php
// ========== HELPER PARA ESCAPAR HTML ==========
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ========== DETECCIÓN DE PÁGINA ACTUAL ==========
if (!isset($currentPage) || empty($currentPage)) {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $currentPage = end($segments);
    $currentPage = str_replace('.php', '', $currentPage);
    
    if (empty($currentPage) || $currentPage === 'index') {
        $currentPage = 'home';
    }
    
    if ($currentPage === 'users') {
        if (isset($_GET['type']) && $_GET['type'] === 'sales-rep') {
            $currentPage = 'users-sales';
        } elseif (isset($_GET['type']) && $_GET['type'] === 'provider') {
            $currentPage = 'users-providers';
        }
    }
    
    if (asesoria()) {
        if ($currentPage === 'requests') {
            $currentPage = 'advisory-requests';
        } elseif ($currentPage === 'commissions') {
            $currentPage = 'advisory-commissions';
        } elseif ($currentPage === 'customers') {
            $currentPage = 'advisory-customers';
        }
    }
}

if ($currentPage === 'user' && isset($user)) {
    $roleCheck = strtolower($user['role_name'] ?? '');
    $roleId = $user['role_id'] ?? 0;
    
    if (strpos($roleCheck, 'sales') !== false || strpos($roleCheck, 'comercial') !== false || $roleId == 3) {
        $currentPage = 'user-salesrep';
    } elseif (strpos($roleCheck, 'provider') !== false || strpos($roleCheck, 'proveedor') !== false || strpos($roleCheck, 'colaborador') !== false || $roleId == 4) {
        $currentPage = 'user-provider';
    } elseif (strpos($roleCheck, 'customer') !== false || strpos($roleCheck, 'client') !== false || $roleId == 5) {
        $currentPage = 'user-customer';
    }
}

// ========== CONFIGURACIÓN DE TÍTULOS E ICONOS ==========
$pageTitles = [
    'home' => 'Inicio',
    'services' => 'Buscar servicios',
    'my-services' => 'Mis solicitudes',
    'my-incidents' => 'Mis incidencias',
    'invoices' => 'Facturas',
    'advisory-invoices' => 'Enviar Facturas',
    'appointments' => 'Citas',
    'communications' => 'Comunicaciones',
    'customers' => 'Clientes',
    'customer' => 'Cliente',
    'request' => 'Solicitud',
    'user' => 'Usuario',
    'user-customer' => 'Cliente',
    'user-salesrep' => 'Comercial',
    'user-provider' => 'Colaborador',
    'salesrep' => 'Comercial',
    'log' => 'Seguimiento',
    'commissions' => 'Comisiones',
    'users' => 'Usuarios',
    'users-sales' => 'Comerciales',
    'users-providers' => 'Colaboradores',
    'advisories' => 'Asesorías',
    'advisory' => 'Asesoría',
    'advisory-requests' => 'Solicitudes',
    'advisory-commissions' => 'Comisiones',
    'advisory-customers' => 'Clientes',
    'notifications' => 'Notificaciones',
];

$pageTitle = $pageTitles[$currentPage] ?? 'Facilítame';

$pageIcons = [
    'home' => 'ki-home',
    'services' => 'ki-search-list',
    'my-services' => 'ki-folder',
    'my-incidents' => 'ki-information-5',
    'invoices' => 'ki-credit-cart',
    'advisory-invoices' => 'ki-document',
    'appointments' => 'ki-calendar',
    'communications' => 'ki-sms',
    'customers' => 'ki-people',
    'customer' => 'ki-profile-circle',
    'request' => 'ki-clipboard',
    'user' => 'ki-user',
    'user-customer' => 'ki-profile-circle',
    'user-salesrep' => 'ki-briefcase',
    'user-provider' => 'ki-handcart',
    'salesrep' => 'ki-briefcase',
    'log' => 'ki-time',
    'commissions' => 'ki-dollar',
    'users' => 'ki-profile-user',
    'users-sales' => 'ki-briefcase',
    'users-providers' => 'ki-handcart',
    'advisories' => 'ki-chart',
    'advisory' => 'ki-chart',
    'advisory-requests' => 'ki-document',
    'advisory-commissions' => 'ki-dollar',
    'advisory-customers' => 'ki-people',
    'notifications' => 'ki-notification-bing',
];

$pageIcon = $pageIcons[$currentPage] ?? 'ki-abstract-26';

// ========== CONFIGURACIÓN DE BÚSQUEDA DINÁMICA ==========
$showSearch = false;
$searchPlaceholder = 'Buscar...';
$searchContext = $currentPage;

if (admin()) {
    $adminSearchPages = ['home', 'log', 'customers', 'customer', 'commissions', 'users', 'users-sales', 'users-providers', 'advisories', 'advisory'];
    $showSearch = in_array($currentPage, $adminSearchPages);
    $adminPlaceholders = [
        'home' => 'Buscar en dashboard...',
        'log' => 'Buscar en seguimiento...',
        'customers' => 'Buscar clientes...',
        'customer' => 'Buscar solicitudes...',
        'commissions' => 'Buscar comisiones...',
        'users' => 'Buscar usuarios...',
        'users-sales' => 'Buscar comerciales...',
        'users-providers' => 'Buscar colaboradores...',
        'advisories' => 'Buscar asesorías...',
        'advisory' => 'Buscar en asesoría...',
    ];
    $searchPlaceholder = $adminPlaceholders[$currentPage] ?? 'Buscar...';
} elseif (proveedor()) {
    $providerSearchPages = ['log', 'customers', 'customer', 'invoices'];
    $showSearch = in_array($currentPage, $providerSearchPages);
    $providerPlaceholders = [
        'log' => 'Buscar en seguimiento...',
        'customers' => 'Buscar clientes...',
        'customer' => 'Buscar solicitudes...',
        'invoices' => 'Buscar facturas...',
    ];
    $searchPlaceholder = $providerPlaceholders[$currentPage] ?? 'Buscar...';
} elseif (comercial()) {
    $salesSearchPages = ['log', 'customers', 'customer', 'commissions', 'notifications'];
    $showSearch = in_array($currentPage, $salesSearchPages);
    $salesPlaceholders = [
        'log' => 'Buscar en seguimiento...',
        'customers' => 'Buscar clientes...',
        'customer' => 'Buscar solicitudes...',
        'commissions' => 'Buscar comisiones...',
        'notifications' => 'Buscar notificaciones...',
    ];
    $searchPlaceholder = $salesPlaceholders[$currentPage] ?? 'Buscar...';
} elseif (asesoria()) {
    $advisorySearchPages = ['customers', 'advisory-customers', 'requests', 'advisory-requests', 'commissions', 'advisory-commissions', 'invoices', 'advisory-invoices', 'appointments', 'communications'];
    $showSearch = in_array($currentPage, $advisorySearchPages);
    $advisoryPlaceholders = [
        'customers' => 'Buscar clientes...',
        'customer' => 'Buscar solicitudes...',
        'advisory-customers' => 'Buscar clientes...',
        'requests' => 'Buscar solicitudes...',
        'advisory-requests' => 'Buscar solicitudes...',
        'commissions' => 'Buscar comisiones...',
        'advisory-commissions' => 'Buscar comisiones...',
        'invoices' => 'Buscar facturas...',
        'advisory-invoices' => 'Buscar facturas...',
        'appointments' => 'Buscar citas...',
        'communications' => 'Buscar comunicaciones...',
    ];
    $searchPlaceholder = $advisoryPlaceholders[$currentPage] ?? 'Buscar...';
} elseif (cliente()) {
    $clientSearchPages = ['home', 'my-services', 'my-incidents', 'invoices', 'advisory-invoices', 'appointments', 'communications', 'notifications'];
    $showSearch = in_array($currentPage, $clientSearchPages);
    $clientPlaceholders = [
        'home' => 'Buscar en dashboard...',
        'my-services' => 'Buscar solicitudes...',
        'my-incidents' => 'Buscar incidencias...',
        'invoices' => 'Buscar facturas...',
        'advisory-invoices' => 'Buscar facturas enviadas...',
        'appointments' => 'Buscar citas...',
        'communications' => 'Buscar comunicaciones...',
        'notifications' => 'Buscar notificaciones...',
    ];
    $searchPlaceholder = $clientPlaceholders[$currentPage] ?? 'Buscar...';
}

$currentRole = 'guest';
if (admin()) $currentRole = 'admin';
elseif (proveedor()) $currentRole = 'provider';
elseif (comercial()) $currentRole = 'sales';
elseif (asesoria()) $currentRole = 'advisory'; 
elseif (cliente()) $currentRole = 'client';
?>

<!--begin::Header-->
<header class="facilitame-header" id="kt_app_header">
    <div class="header-left">
        <button class="mobile-toggle-btn d-lg-none" id="kt_app_header_menu_toggle" aria-label="Abrir menú">
            <i class="ki-outline ki-burger-menu-1 fs-1"></i>
        </button>
        
        <div class="header-title-wrapper">
            <div class="header-title-icon">
                <i class="ki-outline <?= e($pageIcon) ?>"></i>
            </div>
            <h1 class="header-title"><?= e($pageTitle) ?></h1>
        </div>
    </div>
    
    <div class="header-right">
        <?php if ($showSearch): ?>
        <div class="search-wrapper d-none d-md-flex">
            <i class="ki-outline ki-magnifier search-icon"></i>
            <input type="text" class="search-input" placeholder="<?= e($searchPlaceholder) ?>"
                   id="header-search-input" data-search-context="<?= e($searchContext) ?>"
                   data-user-role="<?= e($currentRole) ?>" data-default-placeholder="<?= e($searchPlaceholder) ?>"
                   aria-label="<?= e($searchPlaceholder) ?>" autocomplete="off">
            <button class="search-clear-btn" id="search-clear-btn" style="display: none;" aria-label="Limpiar búsqueda">
                <i class="ki-outline ki-cross fs-4"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="app-navbar-item">
            <button class="header-icon-btn" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" 
                    data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" aria-label="Notificaciones">
                <i class="ki-outline ki-notification fs-2"></i>
                <?php if (!empty(NOTIFICATIONS) && isset(NOTIFICATIONS["unread"]) && intval(NOTIFICATIONS["unread"]) > 0): ?>
                <span class="notification-badge"></span>
                <?php endif; ?>
            </button>
            <?php require ROOT_DIR . "/partials/menus/_notifications-menu.php" ?>
        </div>
        
        <div class="app-navbar-item" id="kt_header_user_menu_toggle">
            <div class="user-menu cursor-pointer" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" 
                 data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                <img src="<?= e(MEDIA_DIR . '/' . USER['profile_picture']) ?>" class="user-avatar" alt="Usuario">
                <div class="user-info d-none d-md-flex">
                    <span class="user-name"><?= e(USER['name'] ?? 'Usuario') ?></span>
                    <small class="user-role">
                        <?php 
                        if (admin()) echo 'Administrador';
                        elseif (proveedor()) echo 'Proveedor';
                        elseif (comercial()) echo 'Comercial';
                        elseif (asesoria()) echo 'Asesoría';
                        else echo 'Cliente #' . e(USER['id'] ?? '0');
                        ?>
                    </small>
                </div>
            </div>
            <?php require ROOT_DIR . "/partials/menus/_user-account-menu.php" ?>
        </div>
    </div>
</header>
<!--end::Header-->

<!--begin::Menu móvil-->
<div class="app-header-menu app-header-mobile-drawer" 
     data-kt-drawer="true" data-kt-drawer-name="app-header-menu" 
     data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" 
     data-kt-drawer-width="{default:'250px', '300px': '280px'}" 
     data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle">
    
    <div class="mobile-menu-header">
        <a href="/home" title="Ir al inicio">
            <img src="/assets/media/bold/logo-facilitame-letras-blancas.png" alt="Facilítame" class="mobile-menu-logo">
        </a>
    </div>
    
    <div class="menu menu-column menu-rounded menu-active-bg fw-semibold my-3 px-3" data-kt-menu="true">
        <?php if (cliente()): ?>
            <div class="menu-section-mobile"><span>Principal</span></div>
            <a href="/home" class="mobile-menu-link <?= ($currentPage === 'home') ? 'active' : '' ?>">
                <i class="ki-outline ki-home"></i><span>Inicio</span>
            </a>
            <a href="/notifications" class="mobile-menu-link <?= ($currentPage === 'notifications') ? 'active' : '' ?>">
                <i class="ki-outline ki-notification-bing"></i><span>Notificaciones</span>
                <?php if (isset(NOTIFICATIONS['unread']) && NOTIFICATIONS['unread'] > 0): ?>
                <span class="badge badge-circle badge-danger ms-auto"><?= NOTIFICATIONS['unread'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/services" class="mobile-menu-link <?= ($currentPage === 'services') ? 'active' : '' ?>">
                <i class="ki-outline ki-search-list"></i><span>Buscar servicios</span>
            </a>
            
            <div class="menu-section-mobile"><span>Mis Gestiones</span></div>
            <a href="/my-services" class="mobile-menu-link <?= ($currentPage === 'my-services') ? 'active' : '' ?>">
                <i class="ki-outline ki-folder"></i><span>Mis solicitudes</span>
            </a>
            <a href="/my-incidents" class="mobile-menu-link <?= ($currentPage === 'my-incidents') ? 'active' : '' ?>">
                <i class="ki-outline ki-information-5"></i><span>Mis incidencias</span>
            </a>
            <?php if (!guest()): ?>
            <a href="/invoices" class="mobile-menu-link <?= ($currentPage === 'invoices') ? 'active' : '' ?>">
                <i class="ki-outline ki-credit-cart"></i><span>Mis facturas</span>
            </a>
            <?php endif; ?>
            
            <?php if (isset($has_advisory) && $has_advisory): ?>
            <div class="menu-section-mobile"><span>Mi Asesoría</span></div>
            <a href="/communications" class="mobile-menu-link <?= ($currentPage === 'communications') ? 'active' : '' ?>">
                <i class="ki-outline ki-sms"></i><span>Comunicaciones</span>
                <?php if (isset($unread_communications) && $unread_communications > 0): ?>
                <span class="badge badge-circle badge-danger ms-auto"><?= $unread_communications ?></span>
                <?php endif; ?>
            </a>
            <a href="/advisory-invoices" class="mobile-menu-link <?= ($currentPage === 'advisory-invoices') ? 'active' : '' ?>">
                <i class="ki-outline ki-document"></i><span>Enviar Facturas</span>
            </a>
            <a href="/appointments" class="mobile-menu-link <?= ($currentPage === 'appointments') ? 'active' : '' ?>">
                <i class="ki-outline ki-calendar"></i><span>Solicitar Cita</span>
            </a>
            <?php endif; ?>
            
        <?php elseif (admin()): ?>
            <div class="menu-section-mobile"><span>Principal</span></div>
            <a href="/home" class="mobile-menu-link <?= ($currentPage === 'home') ? 'active' : '' ?>">
                <i class="ki-outline ki-home"></i><span>Inicio</span>
            </a>
            <a href="/log" class="mobile-menu-link <?= ($currentPage === 'log') ? 'active' : '' ?>">
                <i class="ki-outline ki-time"></i><span>Seguimiento</span>
            </a>
            
            <div class="menu-section-mobile"><span>Gestión</span></div>
            <a href="/customers" class="mobile-menu-link <?= ($currentPage === 'customers') ? 'active' : '' ?>">
                <i class="ki-outline ki-people"></i><span>Clientes</span>
            </a>
            <a href="/users?type=sales-rep" class="mobile-menu-link <?= ($currentPage === 'users-sales') ? 'active' : '' ?>">
                <i class="ki-outline ki-briefcase"></i><span>Comerciales</span>
            </a>
            <a href="/users?type=provider" class="mobile-menu-link <?= ($currentPage === 'users-providers') ? 'active' : '' ?>">
                <i class="ki-outline ki-handcart"></i><span>Colaboradores</span>
            </a>
            <a href="/advisories" class="mobile-menu-link <?= ($currentPage === 'advisories' || $currentPage === 'advisory') ? 'active' : '' ?>">
                <i class="ki-outline ki-chart"></i><span>Asesorías</span>
            </a>
            <a href="/commissions" class="mobile-menu-link <?= ($currentPage === 'commissions') ? 'active' : '' ?>">
                <i class="ki-outline ki-dollar"></i><span>Comisiones</span>
            </a>
            
        <?php elseif (proveedor()): ?>
            <div class="menu-section-mobile"><span>Principal</span></div>
            <a href="/home" class="mobile-menu-link <?= ($currentPage === 'home') ? 'active' : '' ?>">
                <i class="ki-outline ki-home"></i><span>Inicio</span>
            </a>
            <a href="/log" class="mobile-menu-link <?= ($currentPage === 'log') ? 'active' : '' ?>">
                <i class="ki-outline ki-time"></i><span>Seguimiento</span>
            </a>
            
            <div class="menu-section-mobile"><span>Gestión</span></div>
            <a href="/customers" class="mobile-menu-link <?= ($currentPage === 'customers') ? 'active' : '' ?>">
                <i class="ki-outline ki-people"></i><span>Clientes</span>
            </a>
            <a href="/invoices" class="mobile-menu-link <?= ($currentPage === 'invoices') ? 'active' : '' ?>">
                <i class="ki-outline ki-credit-cart"></i><span>Facturas</span>
            </a>
            
        <?php elseif (comercial()): ?>
            <div class="menu-section-mobile"><span>Principal</span></div>
            <a href="/home" class="mobile-menu-link <?= ($currentPage === 'home') ? 'active' : '' ?>">
                <i class="ki-outline ki-home"></i><span>Inicio</span>
            </a>
            <a href="/notifications" class="mobile-menu-link <?= ($currentPage === 'notifications') ? 'active' : '' ?>">
                <i class="ki-outline ki-notification-bing"></i><span>Notificaciones</span>
                <?php if (isset(NOTIFICATIONS['unread']) && NOTIFICATIONS['unread'] > 0): ?>
                <span class="badge badge-circle badge-danger ms-auto"><?= NOTIFICATIONS['unread'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/log" class="mobile-menu-link <?= ($currentPage === 'log') ? 'active' : '' ?>">
                <i class="ki-outline ki-time"></i><span>Seguimiento</span>
            </a>
            
            <div class="menu-section-mobile"><span>Gestión</span></div>
            <a href="/customers" class="mobile-menu-link <?= ($currentPage === 'customers') ? 'active' : '' ?>">
                <i class="ki-outline ki-people"></i><span>Clientes</span>
            </a>
            <a href="/commissions" class="mobile-menu-link <?= ($currentPage === 'commissions') ? 'active' : '' ?>">
                <i class="ki-outline ki-dollar"></i><span>Comisiones</span>
            </a>
            
        <?php elseif (asesoria()): ?>
            <div class="menu-section-mobile"><span>Principal</span></div>
            <a href="/home" class="mobile-menu-link <?= ($currentPage === 'home') ? 'active' : '' ?>">
                <i class="ki-outline ki-home"></i><span>Inicio</span>
            </a>
            
            <div class="menu-section-mobile"><span>Gestión</span></div>
            <a href="/customers" class="mobile-menu-link <?= ($currentPage === 'customers') ? 'active' : '' ?>">
                <i class="ki-outline ki-people"></i><span>Mis Clientes</span>
            </a>
            <a href="/invoices" class="mobile-menu-link <?= ($currentPage === 'invoices') ? 'active' : '' ?>">
                <i class="ki-outline ki-document"></i><span>Facturas Recibidas</span>
            </a>
            <a href="/appointments" class="mobile-menu-link <?= ($currentPage === 'appointments') ? 'active' : '' ?>">
                <i class="ki-outline ki-calendar"></i><span>Citas</span>
            </a>
            <a href="/communications" class="mobile-menu-link <?= ($currentPage === 'communications') ? 'active' : '' ?>">
                <i class="ki-outline ki-sms"></i><span>Comunicaciones</span>
            </a>
            <a href="/commissions" class="mobile-menu-link <?= ($currentPage === 'commissions') ? 'active' : '' ?>">
                <i class="ki-outline ki-dollar"></i><span>Comisiones</span>
            </a>
        <?php endif; ?>
        
        <div class="mobile-menu-divider"></div>
        <a href="/logout" class="mobile-menu-link mobile-menu-logout">
            <i class="ki-outline ki-exit-left"></i><span>Cerrar sesión</span>
        </a>
    </div>
</div>
<!--end::Menu móvil-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var headerSearch = document.getElementById('header-search-input');
    var clearBtn = document.getElementById('search-clear-btn');
    
    if (!headerSearch) return;
    
    var searchContext = headerSearch.getAttribute('data-search-context');
    var userRole = headerSearch.getAttribute('data-user-role');
    var defaultPlaceholder = headerSearch.getAttribute('data-default-placeholder');
    var currentSearchTarget = searchContext;
    
    var tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    if (tabButtons.length > 0) {
        tabButtons.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(e) {
                var target = e.target.getAttribute('data-search-target');
                if (target) {
                    currentSearchTarget = target;
                    updateSearchPlaceholder(target);
                    headerSearch.value = '';
                    clearBtn.style.display = 'none';
                    clearAllFilters();
                }
            });
        });
    }
    
    function updateSearchPlaceholder(target) {
        var placeholders = {
            'solicitudes': 'Buscar solicitudes...', 'incidencias': 'Buscar incidencias...',
            'revisiones': 'Buscar revisiones...', 'aplazadas': 'Buscar aplazadas...',
            'notificaciones': 'Buscar notificaciones...', 'vencimientos': 'Buscar vencimientos...',
            'ofertas': 'Buscar ofertas...', 'provider-solicitudes': 'Buscar solicitudes...',
            'provider-incidencias': 'Buscar incidencias...', 'solicitudes-comercial': 'Buscar solicitudes...',
            'aplazadas-comercial': 'Buscar aplazadas...', 'clientes-comercial': 'Buscar clientes...',
            'notifications': 'Buscar notificaciones...',
            'log': 'Buscar en seguimiento...', 'customers': 'Buscar clientes...',
            'customer': 'Buscar solicitudes...', 'commissions': 'Buscar comisiones...',
            'users': 'Buscar usuarios...', 'users-sales': 'Buscar comerciales...',
            'users-providers': 'Buscar colaboradores...', 'services': 'Buscar servicios...',
            'my-services': 'Buscar solicitudes...', 'my-incidents': 'Buscar incidencias...',
            'invoices': 'Buscar facturas...', 'advisory-invoices': 'Buscar facturas...',
            'appointments': 'Buscar citas...', 'advisory-requests': 'Buscar solicitudes...',
            'advisory-commissions': 'Buscar comisiones...', 'advisory-customers': 'Buscar clientes...',
            'communications': 'Buscar comunicaciones...', 'home': 'Buscar...', 'resumen': 'Buscar...',
            'advisories': 'Buscar asesorías...', 'advisory': 'Buscar en asesoría...'
        };
        headerSearch.placeholder = placeholders[target] || defaultPlaceholder;
    }
    
    headerSearch.addEventListener('input', function(e) {
        var query = e.target.value;
        clearBtn.style.display = query.length > 0 ? 'block' : 'none';
        executeSearch(currentSearchTarget, query);
    });
    
    clearBtn.addEventListener('click', function() {
        headerSearch.value = '';
        clearBtn.style.display = 'none';
        executeSearch(currentSearchTarget, '');
        headerSearch.focus();
    });
    
    function executeSearch(target, query) {
        var filterFunctions = {
            'solicitudes': 'filterSolicitudesRecientes', 'incidencias': 'filterIncidencias',
            'revisiones': 'filterRevisiones', 'aplazadas': 'filterAplazadas',
            'notificaciones': 'filterNotificaciones', 'vencimientos': 'filterVencimientos',
            'ofertas': 'filterOfertas', 'provider-solicitudes': 'filterSolicitudesProveedor',
            'provider-incidencias': 'filterIncidenciasProveedor', 'solicitudes-comercial': 'filterSolicitudesComercial',
            'aplazadas-comercial': 'filterAplazadasComercial', 'clientes-comercial': 'filterClientesComercial',
            'notifications': 'filterNotifications',
            'log': 'filterLogs', 'customers': 'filterCustomers', 'customer': 'filterCustomerRequests',
            'commissions': 'filterCommissions', 'users': 'filterUsers', 'users-sales': 'filterUsers',
            'users-providers': 'filterUsers', 'services': 'filterServices', 'my-services': 'filterMyServices',
            'my-incidents': 'filterMyIncidents', 'invoices': 'filterInvoices',
            'advisory-invoices': 'filterAdvisoryInvoices', 'appointments': 'filterAppointments',
            'home': 'filterHomeContent', 'advisory-requests': 'filterAdvisoryRequests',
            'advisory-commissions': 'filterAdvisoryCommissions', 'advisory-customers': 'filterAdvisoryCustomers',
            'communications': 'filterCommunications',
            'advisories': 'filterAdvisories', 'advisory': 'filterAdvisoryContent'
        };
        
        var functionName = filterFunctions[target];
        if (functionName && typeof window[functionName] === 'function') {
            window[functionName](query);
        }
        
        document.dispatchEvent(new CustomEvent('facilitame:search', {
            detail: { query: query, target: target, role: userRole }
        }));
    }
    
    function clearAllFilters() {
        var allFilters = ['filterSolicitudesRecientes', 'filterIncidencias', 'filterRevisiones',
            'filterAplazadas', 'filterLogs', 'filterCustomers', 'filterCustomerRequests',
            'filterCommissions', 'filterUsers', 'filterServices', 'filterMyServices',
            'filterMyIncidents', 'filterInvoices', 'filterAdvisoryInvoices', 'filterAppointments',
            'filterAdvisoryRequests', 'filterAdvisoryCommissions', 'filterAdvisoryCustomers', 
            'filterCommunications', 'filterAdvisories', 'filterAdvisoryContent'];
        allFilters.forEach(function(fn) {
            if (typeof window[fn] === 'function') window[fn]('');
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            headerSearch.focus();
            headerSearch.select();
        }
        if (e.key === 'Escape' && document.activeElement === headerSearch) {
            headerSearch.value = '';
            clearBtn.style.display = 'none';
            executeSearch(currentSearchTarget, '');
            headerSearch.blur();
        }
    });
    
    window.FacilitameSearch = {
        setTarget: function(target) { currentSearchTarget = target; updateSearchPlaceholder(target); },
        clear: function() { headerSearch.value = ''; clearBtn.style.display = 'none'; clearAllFilters(); },
        getValue: function() { return headerSearch.value; },
        focus: function() { headerSearch.focus(); }
    };
});
</script>