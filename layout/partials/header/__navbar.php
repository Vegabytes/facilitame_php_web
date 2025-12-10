<!--begin::Navbar-->
<div class="app-navbar flex-shrink-0">
    <!--begin::Notifications-->
    <div class="app-navbar-item ms-2 ms-md-5">
        <!--begin::Menu- wrapper-->
        <div class="btn btn-icon btn-custom w-35px h-35px w-md-40px h-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
            <i class="ki-outline ki-notification-status fs-1"></i>
        </div>
        <?php require ROOT_DIR . "/partials/menus/_notifications-menu.php" ?>
        <!--end::Menu wrapper-->
    </div>
    <!--end::Notifications-->
    <!--begin::User menu-->
    <div class="app-navbar-item ms-2 ms-md-5" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
            <img class="symbol symbol-35px symbol-md-40px" style="box-shadow: rgba(255, 255, 255, 0.12) 0px 2px 4px 0px, rgba(255, 255, 255, 0.32) 0px 2px 16px 0px;" src="<?php echo MEDIA_DIR . "/" . USER["profile_picture"] ?>" alt="user" />
        </div>
        <?php require ROOT_DIR . "/partials/menus/_user-account-menu.php" ?>
        <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->

    <?php if (0) : ?>
        <!--begin::Action-->
        <div class="app-navbar-item ms-2 ms-md-5">
            <a href="#" class="btn btn-flex btn-sm fw-bold btn-dark py-3" data-bs-toggle="modal" data-bs-target="#kt_modal_upgrade_plan">
                Try Now
            </a>
        </div>
        <!--end::Action-->    
    <?php endif ; ?>
    <!--begin::Sidebar menu toggle-->
    <!--end::Sidebar menu toggle-->
</div>
<!--end::Navbar-->