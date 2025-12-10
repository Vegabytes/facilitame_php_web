<?php if (!isset(USER['role']) || USER['role'] === null): ?>
    <!-- Página pública (login, sign-up, recovery, restore) -->
    <?php require ROOT_DIR . "/pages" . PUBLIC_PAGE . "-content.php" ?>
<?php else: ?>
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            
            <!-- Sidebar -->
            <?php 
            $currentPage = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $currentPage = $currentPage ?: 'home';
            if ($currentPage === 'users') {
                $type = $_GET['type'] ?? '';
                if ($type === 'sales-rep') $currentPage = 'users-sales';
                elseif ($type === 'provider') $currentPage = 'users-providers';
            }
            require ROOT_DIR . "/layout/partials/sidebar/_menu_sidebar.php"; 
            ?>
            
            <!-- Header -->
            <?php require ROOT_DIR . "/layout/partials/_header.php" ?>
            
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <!--begin::Content-->
                    <div class="d-flex flex-column flex-column-fluid">
                        <div class="container-fluid py-2 px-4" id="facilita-app">
                            <?php require ROOT_DIR . RESOURCE . "/" . USER["view"] . PAGE . ".php" ?>
                        </div>
                    </div>
                    <!--end::Content-->
                    
                    <!-- Footer -->
                    <?php require ROOT_DIR . "/layout/partials/_footer.php" ?>
                </div>
                <!--end::Main-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->
<?php endif; ?>