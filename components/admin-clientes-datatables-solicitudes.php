<div class="card card-flush">
    <!-- Header -->
    <div class="card-header pt-7 pb-5">
        <div class="card-title w-100">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center w-100 gap-4">
                <!-- Título -->
                <div>
                    <h3 class="fw-bold text-gray-800 mb-1">
                        Solicitudes<span id="dt-solicitudes-cliente-nombre" class="text-muted"></span>
                    </h3>
                    <span class="text-muted fs-7">Gestiona tus solicitudes</span>
                </div>
                
                <!-- Búsqueda -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4 text-gray-500" aria-hidden="true"></i>
                    <input type="text" 
                           data-dt-solicitudes-filter-filter="search" 
                           class="form-control form-control-solid w-250px ps-12" 
                           placeholder="Buscar solicitudes..."
                           aria-label="Buscar solicitudes" />
                </div>
            </div>
        </div>
    </div>
    
    <!-- Body con tabla -->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-row-dashed table-hover align-middle fs-6 gy-4" id="dt-solicitudes">
                <thead class="border-bottom border-gray-200">
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                        <th class="min-w-50px">ID</th>
                        <th class="min-w-125px">Categoría</th>
                        <th class="min-w-200px">Información</th>
                        <th class="min-w-100px">Estado</th>
                        <th class="min-w-125px">Fecha solicitud</th>
                        <th class="min-w-125px">Última actualización</th>
                        <th class="min-w-100px text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-700" id="provider-customer-requests-table">
                    <!-- Contenido dinámico -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Mejoras para la tabla */
.table-responsive {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 194, 203, 0.3) transparent;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: rgba(0, 194, 203, 0.3);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 194, 203, 0.5);
}

#dt-solicitudes thead {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

#dt-solicitudes tbody tr {
    transition: all 0.2s ease;
}

#dt-solicitudes tbody tr:hover {
    background: rgba(0, 194, 203, 0.05);
}

/* Responsive */
@media (max-width: 768px) {
    .table-responsive {
        max-height: 400px;
    }
}
</style>