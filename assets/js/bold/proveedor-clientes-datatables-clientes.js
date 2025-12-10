// Class definition
var DatatablesProveedorClientes = function ()
{
    // ////////////////////////////////////////////
    // Tabla de clientes :: inicio
    var tableClientes;
    var datatableClientes;

    // Private methods
    const initDatatableClientes = () =>
    {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatableClientes = $(tableClientes).DataTable({
            "info": false,
            'order': [],
            "lengthChange": false,
            'pageLength': 6,
            'ordering': true,
            'paging': false,
            'columnDefs': [
                { orderable: false, targets: 0 }, // Disable ordering on column 0 (checkbox)                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatableClientes.on('draw', function ()
        {
            handleCustomerActionClientes();
        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatableClientes = () =>
    {
        const filterSearch = document.querySelector('[data-dt-clientes-filter-filter="search"]');
        filterSearch.addEventListener('keyup', function (e)
        {
            datatableClientes.search(e.target.value).draw();
        });
    }

    const handleCustomerActionClientes = () =>
    {
        $(document).on("click", ".customer-get-info", async function (e)
        {
            e.preventDefault();

            let ajaxurl = "/api/provider-customer-get-requests";
            let data = {
                customerId: $(this).data("customer-id")
            };

            let response;
            try
            {
                response = await $.post(ajaxurl, data).fail(() => { return; });
                
                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    // Limpiar las filas existentes en el DataTable de solicitudes
                    datatableSolicitudes.clear();                    

                    // Reiniciar el filtro de búsqueda
                    datatableSolicitudes.search("").draw();
                    $(`[data-dt-solicitudes-filter-filter="search"]`).val("");


                    // Agregar nuevas filas al DataTable con los datos del JSON
                    datatableSolicitudes.rows.add(response.data.map(item =>
                    {
                        return [
                            `<a href="request?id=${item.id}" class="text-gray-800 text-hover-primary mb-1">${item.id}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.category_name}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.request_info}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1"><span class="fs-6 badge badge-dark">${item.status}</span></a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.request_date}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1">${item.updated_at}</a>`,
                            `<a href="request?id=${item.id}" class="text-gray-600 text-hover-primary mb-1 text-end btn btn-light-primary">Vista</a>`
                        ];
                    }));

                    // Dibujar la tabla con los nuevos datos
                    datatableSolicitudes.draw();

                    $("#dt-solicitudes-cliente-nombre").html( ` de ` + $(this).closest("tr").data("customer-name") );
                }
                else
                {
                    Swal.fire({
                        icon: "warning",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        },
                    });
                }
            } catch (error)
            {
                Swal.fire({
                    icon: "warning",
                    html: "Ha ocurrido un error",
                    buttonsStyling: false,
                    confirmButtonText: "Cerrar",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
                return;
            } finally
            {
                // let dismiss = form.find(".btn.dismiss");
                // dismiss.click();
            }
        });
    }
    // Tabla de clientes :: fin
    // ////////////////////////////////////////////




    // ////////////////////////////////////////////
    // Tabla de solicitudes :: inicio
    var tableSolicitudes;
    var datatableSolicitudes;

    // Private methods
    const initDatatableSolicitudes = () =>
    {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatableSolicitudes = $(tableSolicitudes).DataTable({
            "info": false,
            'order': [],
            "lengthChange": false,
            'pageLength': 6,
            'ordering': true,
            'paging': false,
            'columnDefs': [
                { orderable: false, targets: 0 }, // Disable ordering on column 0 (checkbox)                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatableSolicitudes.on('draw', function ()
        {
            handleCustomerActionSolicitudes();
        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatableSolicitudes = () =>
    {
        const filterSearch = document.querySelector('[data-dt-solicitudes-filter-filter="search"]');
        filterSearch.addEventListener('keyup', function (e)
        {
            datatableSolicitudes.search(e.target.value).draw();
        });
    }

    const handleCustomerActionSolicitudes = () =>
    {
        return;
    }
    // Tabla de solicitudes :: fin
    // ////////////////////////////////////////////






















    // Public methods
    return {
        init: function ()
        {
            tableClientes = document.querySelector('#dt-clientes');
            tableSolicitudes = document.querySelector('#dt-solicitudes');

            if (!tableClientes)
            {
                return;
            }
            if (!tableSolicitudes)
            {
                return;
            }

            initDatatableClientes();
            initDatatableSolicitudes();
            handleSearchDatatableClientes();
            handleSearchDatatableSolicitudes();
            handleCustomerActionClientes();
            handleCustomerActionSolicitudes();
        }
    }
}();

// Webpack support
if (typeof module !== 'undefined')
{
    module.exports = DatatablesProveedorClientes;
}

// On document ready
KTUtil.onDOMContentLoaded(function ()
{
    console.log(`%c  proveedor-clientes-datatables-clientes  `, `background: #222; color: #bada55`); // Black / Green // DEV
    DatatablesProveedorClientes.init();
});