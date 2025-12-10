"use strict";

console.log(`%c  my-incidents  `, `background: #1976D2; color: white`); // Azul / White // DEV

// Class definition
var KTIncidentsList = function () {
    // Define shared variables
    var datatable;
    var table;

    // Private functions
    var initIncidentsList = function () {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": false,
            'order': [0, 'desc'],
            'columnDefs': [
                { orderable: false, targets: "_all" }
            ]
        });

        datatable.on('draw', function () {
            // Aqu¨ª puedes re-inicializar tooltips, menus, etc si los necesitas
        });
    }

    // Search Datatable
    var handleSearchDatatable = () => {
        const filterSearch = document.querySelector('[data-kt-incidents-table-filter="search"]');
        if (!filterSearch) return;
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Public methods
    return {
        init: function () {
            table = document.querySelector('#kt_incidents_table');
            if (!table) return;

            initIncidentsList();
            handleSearchDatatable();
        }
    }
}();

// On document ready
// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTIncidentsList.init();

    // Abrir modal valoraci¨®n de incidencia
    $(document).on('click', '.incident-valoracion', function (e) {
        e.preventDefault();
        let requestId = $(this).data('request-id');
        let incidentId = $(this).data('incident-id');
        $('#modal-incidence-valoracion-request-id').val(requestId);
        $('#modal-incidence-valoracion-incident-id').val(incidentId);
        $('#modal-incidence-valoracion').modal('show');
    });

    // Bot¨®n: S¨ª, se ha resuelto
    $('#btn-incidence-resuelta').on('click', async function () {
        let requestId = $('#modal-incidence-valoracion-request-id').val();
        let incidentId = $('#modal-incidence-valoracion-incident-id').val();
        let newStatusId = 3; // Validada

        try {
            let response = await $.post('/api/incident-mark-validated', {
                request_id: requestId,
                incident_id: incidentId,
                new_status_id: newStatusId
            });

            if (typeof response === "string") response = JSON.parse(response);

            Swal.fire({
                icon: "success",
                html: response.message_html,
                buttonsStyling: false,
                confirmButtonText: "Cerrar",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });

            $('#modal-incidence-valoracion').modal('hide');
            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            Swal.fire({
                icon: "error",
                html: "No se pudo actualizar la incidencia.",
                buttonsStyling: false,
                confirmButtonText: "Cerrar",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        }
    });

    // Bot¨®n: No, sigue sin resolverse
    $('#btn-incidence-noresuelta').on('click', async function () {
        let requestId = $('#modal-incidence-valoracion-request-id').val();
        let incidentId = $('#modal-incidence-valoracion-incident-id').val();
        let newStatusId = 2; // Gestionando

        try {
            let response = await $.post('/api/incident-mark-validated', {
                request_id: requestId,
                incident_id: incidentId,
                new_status_id: newStatusId
            });

            if (typeof response === "string") response = JSON.parse(response);

            Swal.fire({
                icon: "success",
               html: "Un responsable de Facilitame se pondra en contacto contigo para ayudarte a resolver esta incidencia.",
                buttonsStyling: false,
                confirmButtonText: "Cerrar",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });

            $('#modal-incidence-valoracion').modal('hide');
            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            Swal.fire({
                icon: "error",
                html: "No se pudo actualizar la incidencia.",
                buttonsStyling: false,
                confirmButtonText: "Cerrar",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        }
    });

});


