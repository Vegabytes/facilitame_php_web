$(document).ready(function () {
    var datatableSolicitudes = $('#dt-customer-requests').DataTable({
        info: false,
        order: [],
        lengthChange: false,
        pageLength: 6,
        ordering: true,
        paging: false,
        columnDefs: [
            { orderable: false, targets: 0 }
        ],
    });

    function loadSolicitudes(customerId) {
        $.post('/api/provider-customer-get-requests', { customerId: customerId }, function(response) {
            response = typeof response === 'string' ? JSON.parse(response) : response;
            if (response.status === "ok") {
                datatableSolicitudes.clear();

                // CORRECTO: las solicitudes están en response.data
                var solicitudes = Array.isArray(response.data) ? response.data : [];

                // Para depurar:
                // console.log("Solicitudes:", solicitudes);

                datatableSolicitudes.rows.add(
                    solicitudes.map(function(item) {
                        return [
                            `<a href="request?id=${item.id}" class="text-gray-800 text-hover-primary mb-1">${item.id}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.category_name}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.request_info}</a>`,
                            `<span class="fs-6 badge badge-dark">${item.status}</span>`,
                            `${item.request_date}`,
                            `${item.updated_at}`
                        ];
                    })
                );
                datatableSolicitudes.draw();
            } else {
                datatableSolicitudes.clear().draw();
                $('#dt-customer-requests tbody').html('<tr><td colspan="7">No hay solicitudes para este cliente.</td></tr>');
            }
        }).fail(function() {
            datatableSolicitudes.clear().draw();
            $('#dt-customer-requests tbody').html('<tr><td colspan="7">Error al cargar solicitudes.</td></tr>');
        });
    }

    if (typeof CUSTOMER_ID !== "undefined" && CUSTOMER_ID) {
        loadSolicitudes(CUSTOMER_ID);
    }

    // Filtro de búsqueda personalizado
    const filterSearch = document.querySelector('[data-customer-table-filter="search"]');
    if (filterSearch) {
        filterSearch.addEventListener('keyup', function (e) {
            datatableSolicitudes.search(e.target.value).draw();
        });
    }

});
