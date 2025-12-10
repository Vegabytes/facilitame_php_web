(function ($)
{
    $(document).ready(function ()
    {
        console.log(`%c  log  `, `background: #222; color: #bada55`); // Black / Green // DEV

        "use strict";

        // Class definition
        var datatablesLog = function ()
        {
            var table;
            var datatable;            

            // Private methods
            const initDatatable = () =>
            {
                datatable = $(table).DataTable({
                    "info": false,
                    'order': [],
                    "lengthChange": false,
                    'pageLength': 10,
                    'ordering': true,
                    'paging': true,
                    // 'columnDefs': [
                    //     { orderable: false, targets: 0 },
                    //     { orderable: false, targets: 6 },
                    // ]
                });

                datatable.on('draw', function ()
                {
                    // Aqu铆 puedes meter l贸gica tras el redibujado si quieres
                });
            }

            // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
            var handleSearchDatatable = () => {
                const filterSearch = document.querySelector('#datatables-log-search');
                filterSearch.addEventListener('keyup', function (e) {
                    datatable.search(e.target.value).draw();
                });
            }

            return {
                init: function ()
                {
                    table = document.querySelector('#datatables-log');

                    if (!table)
                    {
                        return;
                    }

                    initDatatable();
                    handleSearchDatatable();
                    // handleActionButton();
                }
            }
        }();

        if (typeof module !== 'undefined')
        {
            module.exports = datatablesLog;
        }

        KTUtil.onDOMContentLoaded(function ()
        {
            datatablesLog.init();
        });

    });
})(jQuery);
