(function ($)
{
    $(document).ready(function ()
    {

        console.log(`%c  salesrep  `, `background: #222; color: #bada55`); // Black / Green // DEV

        $(".copy").on("click", copy);
        function copy()
        {
            var contenidoHtml = $(this).html(); // Obtiene el HTML del elemento clicado
            console.log("Contenido copiado:", contenidoHtml);

            navigator.clipboard.writeText(contenidoHtml).then(() =>
            {
                alert('Código copiado');
            }).catch(err =>
            {
                console.error('Error al copiar:', err);
            });
        }




        // sales rep customers datatables :: inicio
        var datatablesSalesRepCustomers = function ()
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

                });
            }

            // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
            var handleSearchDatatable = () =>
            {
                const filterSearch = document.querySelector('#datatables-sales-rep-customers-search');
                filterSearch.addEventListener('keyup', function (e)
                {
                    datatable.search(e.target.value).draw();
                });
            }

            var handleRowDisplay = () =>
            {
                $(document).on("click", ".row-customer", async function (e)
                {
                    e.preventDefault();

                    const customer_id = $(this).data("customer-id");
                    const ref_id = `container-customer-${customer_id}-requests`;

                    if ($(`#${ref_id}`).length)
                    {
                        $(`#${ref_id}`).closest("tr").remove();
                        return;
                    }


                    const numero_columnas = document.querySelectorAll('#datatables-sales-rep-customers tr:first-child th').length;

                    const all_customer_requests_rows = $(`.row-customer-request`);
                    all_customer_requests_rows.remove();


                    let response;
                    try
                    {
                        const data = {
                            customer_id
                        }
                        response = await $.post("api/customer-get-requests", data).fail(() => { return; });

                        response = JSON.parse(response);                        

                        if (response.data.length < 1)
                        {
                            return;
                        }

                        let el = 
                            `<tr class="row-customer-request">
                            <td colspan="${numero_columnas}">
                                <div style="width:100%; line-height:1rem; display:flex; flex-direction:column;" id="${ref_id}">
                                </div>
                            </td>
                        </tr>`;
    
                        el = $(el);    
                        $(this).after(el);

                        response.data.forEach(item => {                            
                            let request = `
                                <div style="padding: 0 3rem; margin-bottom:1rem; display:flex; flex-direction:row; justify-content:flex-start;">
                                    <div style="width:10%">${item.category_name}<br><span style="font-size:0.85rem;font-weight:normal">${item.id}</span></div>
                                    <div style="width:20%">${item.status_display}</span></div>
                                </div>
                            `;
                            request = $(request);
                            $(`#${ref_id}`).append(request);
                        });

                    } catch (error)
                    {
                        console.error(error);
                        return;
                    }

                });
            }

            return {
                init: function ()
                {
                    table = document.querySelector('#datatables-sales-rep-customers');

                    if (!table)
                    {
                        return;
                    }

                    initDatatable();
                    handleSearchDatatable();
                    handleRowDisplay();
                }
            }
        }();

        if (typeof module !== 'undefined')
        {
            module.exports = datatablesSalesRepCustomers;
        }

        KTUtil.onDOMContentLoaded(function ()
        {
            datatablesSalesRepCustomers.init();
        });
        // sales rep customers datatables :: fin




    });
})(jQuery)