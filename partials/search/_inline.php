<!--begin::Search-->
<div
    id="kt_header_search"
    class="header-search d-flex align-items-center w-lg-300px"
    data-kt-search-keypress="true"
    data-kt-search-min-length="2"
    data-kt-search-enter="enter"
    data-kt-search-layout="menu"
    data-kt-search-responsive="lg"
    data-kt-menu-trigger="auto"
    data-kt-menu-permanent="true"
    data-kt-menu-placement="bottom-end"
        >
            <!--begin::Tablet and mobile search toggle-->
        <div data-kt-search-element="toggle" class="search-toggle-mobile d-flex d-lg-none align-items-center">
            <div class="d-flex ">
                                    <i class="ki-outline ki-magnifier fs-1 "></i>                            </div>
        </div>
        <!--end::Tablet and mobile search toggle-->
<?php require ROOT_DIR . "/partials/search/partials/_form-inline.php" ?>
    <!--begin::Menu-->
    <div data-kt-search-element="content" class="menu menu-sub menu-sub-dropdown py-7 px-7 overflow-hidden w-300px w-md-350px">
        <!--begin::Wrapper-->
        <div data-kt-search-element="wrapper">
<?php require ROOT_DIR . "/partials/search/partials/_results.php" ?>
<?php require ROOT_DIR . "/partials/search/partials/_main.php" ?>
<?php require ROOT_DIR . "/partials/search/partials/_empty.php" ?>
        </div>
        <!--end::Wrapper-->
<?php require ROOT_DIR . "/partials/search/partials/_advanced-options.php" ?>
<?php require ROOT_DIR . "/partials/search/partials/_preferences.php" ?>
    </div>
    <!--end::Menu-->
</div>
<!--end::Search-->