<div class="card card-flush h-xl-100 pb-5">

    <div class="card-header pt-7">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-800 mb-5">Solicitudes<span id="dt-solicitudes-cliente-nombre"></span></span>
            <!--begin::Search-->
            <div class="d-flex align-items-center position-relative">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <input type="text" data-dt-solicitudes-filter-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="Búsqueda" />
            </div>
            <!--end::Search-->
        </h3>
    </div>

    <div class="card-body pt-2 card-scroll" style="height:65vh">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="dt-solicitudes">
            <thead>
                <tr class="text-start text-500 fw-bold fs-7 text-uppercase gs-0">
                    <?php if (0) : ?>
                        <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#dt-solicitudes .form-check-input" value="1" />
                            </div>
                        </th>
                    <?php endif; ?>
                    <th class="">ID</th>
                    <th class="">Categoría</th>
                    <th class="min-w-150px">Información</th>
                    <th class="">Estado</th>
                    <th class="">Fecha solicitud</th>
                    <th class="">Fecha actualización</th>
                    <th class="min-w-70px">Acciones</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600" id="provider-customer-requests-table">
            </tbody>
        </table>
    </div>

</div>