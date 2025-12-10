<?php 
$v = '1.0.1'; 
$isPublicPage = !isset(USER['role']) || USER['role'] === null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="/" />
    <title>Facilítame - Tu gestoría digital | Gestión administrativa simplificada</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Facilítame simplifica tus trámites administrativos. Plataforma de gestión de servicios para empresas, autónomos y particulares. Asesoramiento profesional y centralización de servicios en España." />
    <meta name="keywords" content="gestoría digital, gestión administrativa, autónomos, empresas, asesoría online, trámites, facturación, servicios empresariales" />
    <meta name="author" content="Facilítame 2024 SL" />
    <meta name="robots" content="index, follow" />
    <meta name="theme-color" content="#00c2cb" />
    
    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://app.facilitame.es/" />
    <meta property="og:title" content="Facilítame - Tu gestoría digital" />
    <meta property="og:description" content="Simplifica tus trámites administrativos. Plataforma de gestión para empresas, autónomos y particulares en España." />
    <meta property="og:image" content="https://app.facilitame.es/assets/media/bold/logo-facilitame-letras-negras.png" />
    <meta property="og:locale" content="es_ES" />
    <meta property="og:site_name" content="Facilítame" />
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Facilítame - Tu gestoría digital" />
    <meta name="twitter:description" content="Simplifica tus trámites administrativos." />
    <meta name="twitter:image" content="https://app.facilitame.es/assets/media/bold/logo-facilitame-letras-negras.png" />
    
    <link rel="canonical" href="https://app.facilitame.es/" />
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/media/bold/favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="/assets/media/bold/logo-facilitame-f-negra-fondo-transp.png" />
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />

    <!-- Vendor CSS - Solo si está logueado -->
    <?php if (!$isPublicPage): ?>
    <link href="/assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" />
    <?php endif; ?>

    <!-- Core CSS -->
    <link href="/assets/plugins/global/plugins.bundle.css" rel="stylesheet" />
    <link href="/assets/css/style_bundle.min.css" rel="stylesheet" />
    <link href="/assets/css/bold.css?v=<?= $v ?>" rel="stylesheet" />
    <link href="/assets/css/login.css?v=<?= $v ?>" rel="stylesheet" />
    
    <!-- Component CSS - Solo si está logueado -->
    <?php if (!$isPublicPage): ?>
    <link href="/assets/css/header.css?v=<?= $v ?>" rel="stylesheet" />
    <link href="/assets/css/sidebar.css?v=<?= $v ?>" rel="stylesheet" />
    <?php if (!proveedor()): ?>
    <link href="/assets/css/static.css?v=<?= $v ?>" rel="stylesheet" />
    <?php endif; ?>
    <link href="/assets/css/dashboard-common.css?v=<?= $v ?>" rel="stylesheet" />
    <link href="/assets/css/buttons.css?v=<?= $v ?>" rel="stylesheet" />
    <link href="/assets/css/modals.css?v=<?= $v ?>" rel="stylesheet" />
    <?php
        $roleCSS = match(true) {
            admin() => 'admin',
            cliente() => 'cliente', 
            proveedor() => 'proveedor',
            comercial() => 'comercial',
            asesoria() => 'asesoria',
            default => null
        };
        if ($roleCSS): 
    ?>
    <link href="/assets/css/<?= $roleCSS ?>.css?v=<?= $v ?>" rel="stylesheet" />
    <?php endif; ?>
    <?php endif; ?>

    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Facilítame",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "Plataforma de gestión de servicios administrativos para empresas, autónomos y particulares en España",
        "url": "https://app.facilitame.es",
        "inLanguage": "es",
        "isAccessibleForFree": true,
        "author": {
            "@type": "Organization",
            "name": "Facilítame 2024 SL",
            "url": "https://www.facilitame.es",
            "logo": "https://app.facilitame.es/assets/media/bold/logo-facilitame-letras-negras.png",
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+34-846-631-986",
                "contactType": "customer service",
                "email": "info@facilitame.es",
                "areaServed": "ES",
                "availableLanguage": ["Spanish", "Basque"]
            },
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Bizkai Kalea 13",
                "addressLocality": "Galdakao",
                "postalCode": "48960",
                "addressCountry": "ES"
            },
            "sameAs": [
                "https://www.facebook.com/share/19vFdTUhtY/",
                "https://www.instagram.com/facilitame.app",
                "https://www.linkedin.com/company/facilitame-sl/",
                "https://www.tiktok.com/@facilitame"
            ]
        }
    }
    </script>

    <!-- GTM - Solo con consentimiento -->
    <script>
    if(localStorage.getItem('facilitame-analytics-consent')==='accepted'){
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s);j.async=true;
        j.src='https://www.googletagmanager.com/gtm.js?id='+i;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-KTX7XBXS');
    }
    </script>
</head>

<body id="kt_app_body" data-kt-app-header-stacked="true" data-kt-app-header-primary-enabled="true" class="<?= $isPublicPage ? 'app-blank' : 'app-default' ?>">
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KTX7XBXS" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

    <!-- Skip Link para accesibilidad -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <main id="main-content" role="main">
        <?php require ROOT_DIR . "/layout/_default.php" ?>
    </main>
    
    <?php if (!$isPublicPage): ?>
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true"><i class="ki-outline ki-arrow-up"></i></div>
    <?php endif; ?>

    <!-- JS -->
    <script>var hostUrl="/assets/";</script>
    <script src="/assets/plugins/global/plugins.bundle.js"></script>
    <script src="/assets/js/scripts.bundle.js"></script>
    
    <?php if (!$isPublicPage): ?>
    <script src="/assets/js/bold/_helpers.js?v=<?= $v ?>"></script>
    <script src="/assets/js/bold/logout.js?v=<?= $v ?>"></script>
    <script src="/assets/js/bold/notifications.js?v=<?= $v ?>"></script>
    <?php endif; ?>

    <!-- Lazy Loading para imágenes -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar loading="lazy" a todas las imágenes que no lo tengan
        document.querySelectorAll('img:not([loading])').forEach(function(img) {
            // No aplicar a imágenes pequeñas (logos, iconos) o visibles inmediatamente
            if (!img.closest('.sidebar-logo') && !img.closest('.header-logo')) {
                img.setAttribute('loading', 'lazy');
            }
        });

        // IntersectionObserver para imágenes con data-src
        if ('IntersectionObserver' in window) {
            var lazyImages = document.querySelectorAll('img[data-src]');
            var imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            }, { rootMargin: '50px 0px' });

            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    });
    </script>
    
    <script src="/assets/js/bold/cookie-policy.js?v=<?= $v ?>"></script>

    <?php
    if (!empty($scripts)) {
        foreach ($scripts as $s) echo '<script src="/assets/js/bold/'.$s.'.js?v='.$v.'"></script>';
    }
    if (!empty($metronic_scripts)) {
        foreach ($metronic_scripts as $s) echo '<script src="/'.$s.'.js?v='.$v.'"></script>';
    }
    toastr();
    ?>
</body>
</html>