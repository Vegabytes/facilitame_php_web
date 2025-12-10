(function ($)
{
    $(document).ready(function ()
    {
        console.log(`%c  invoices-customer v2  `, `background: #222; color: #bada55`);

        "use strict";

        // Manejo del botón expandir/colapsar
        const handleActionButton = () =>
        {
            const buttons = document.querySelectorAll('.toggle-invoices-btn');
            
            console.log(`%c  Botones encontrados: ${buttons.length}  `, `background: #004080; color: white`);

            buttons.forEach(button =>
            {
                button.addEventListener('click', async (e) =>
                {
                    e.stopImmediatePropagation();
                    e.preventDefault();

                    const wrapper = button.closest('.invoice-card-wrapper');
                    const listCard = wrapper.querySelector('.list-card');
                    const subtable = wrapper.querySelector('.invoice-subtable');
                    const container = subtable.querySelector('[data-invoice-container]');
                    const requestId = listCard.getAttribute('data-request-id');
                    const spinner = button.querySelector('.spinner-border');
                    const btnText = button.querySelector('.btn-text');

                    console.log(`%c  Request ID: ${requestId}  `, `background: #9C27B0; color: white`);

                    // Toggle visibilidad
                    const isVisible = subtable.style.display !== 'none' && subtable.style.display !== '';

                    if (isVisible)
                    {
                        // Ocultar
                        subtable.style.display = 'none';
                        btnText.textContent = 'Ver facturas';
                        console.log(`%c  Ocultando facturas  `, `background: #FF5722; color: white`);
                    }
                    else
                    {
                        // Mostrar
                        subtable.style.display = 'block';
                        btnText.textContent = 'Ocultar facturas';

                        // Cargar facturas si no se han cargado antes
                        if (!subtable.dataset.loaded)
                        {
                            console.log(`%c  Cargando facturas por primera vez...  `, `background: #004080; color: white`);

                            // Mostrar loading en el botón
                            button.classList.add('loading');
                            spinner.style.display = 'inline-block';

                            // Mostrar loading en el contenedor
                            container.innerHTML = `
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mb-0 fs-7">Cargando facturas...</p>
                                </div>
                            `;

                            let response;
                            try
                            {
                                const data = {
                                    request_id: requestId
                                };
                                
                                console.log('%c  Enviando petición AJAX...  ', 'background: #2196F3; color: white', data);
                                
                                response = await $.post("api/request-get-invoices", data).fail((jqXHR, textStatus, errorThrown) => {
                                    console.error('%c  Error AJAX:  ', 'background: #CC0000; color: white', textStatus, errorThrown);
                                    throw new Error('Error en la petición AJAX');
                                });

                                console.log('%c  Respuesta recibida:  ', 'background: #4CAF50; color: white', response);

                                response = JSON.parse(response);

                                console.log('%c  Respuesta parseada:  ', 'background: #8BC34A; color: white', response);

                                if (response.status != "ok")
                                {
                                    console.warn('%c  Estado no OK  ', 'background: #FF9800; color: white', response);
                                    
                                    Swal.fire({
                                        icon: "warning",
                                        html: response.message_html || 'Error al cargar facturas',
                                        buttonsStyling: false,
                                        confirmButtonText: "Cerrar",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });

                                    container.innerHTML = `
                                        <div class="text-center py-4 text-warning">
                                            <i class="ki-outline ki-information-2 fs-2x mb-2"></i>
                                            <p class="mb-0 fs-7">${response.message_html || 'No se pudieron cargar las facturas'}</p>
                                        </div>
                                    `;
                                }
                                else
                                {
                                    console.log('%c  Facturas recibidas:  ', 'background: #4CAF50; color: white', response.data.invoices);
                                    
                                    // Renderizar facturas
                                    populateInvoices(response.data.invoices, container);
                                    subtable.dataset.loaded = 'true';
                                }

                            } catch (error)
                            {
                                console.error('%c  EXCEPTION  ', 'background: #CC0000; color: white', error);

                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    html: "Ha ocurrido un error al cargar las facturas.<br><small>Revisa la consola para más detalles.</small>",
                                    buttonsStyling: false,
                                    confirmButtonText: "Cerrar",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });

                                container.innerHTML = `
                                    <div class="text-center py-4 text-danger">
                                        <i class="ki-outline ki-cross-circle fs-2x mb-2"></i>
                                        <p class="mb-0 fs-7">Error al cargar las facturas</p>
                                        <small class="text-muted">Revisa la consola del navegador</small>
                                    </div>
                                `;
                            } finally
                            {
                                // Ocultar loading del botón
                                button.classList.remove('loading');
                                spinner.style.display = 'none';
                            }
                        }
                        else
                        {
                            console.log(`%c  Facturas ya cargadas  `, `background: #607D8B; color: white`);
                        }
                    }
                });
            });
        }

        // Función para renderizar las facturas
        const populateInvoices = (invoices, container) =>
        {
            console.log(`%c  Renderizando ${invoices ? invoices.length : 0} facturas  `, `background: #3F51B5; color: white`);
            
            if (!invoices || invoices.length === 0)
            {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="ki-outline ki-file fs-2x mb-2"></i>
                        <p class="mb-0 fs-7 fw-semibold">No hay facturas disponibles</p>
                        <small class="text-muted">Esta solicitud aún no tiene facturas asociadas</small>
                    </div>
                `;
                return;
            }

            // Limpiar contenedor
            container.innerHTML = '';

            // Renderizar cada factura
            invoices.forEach((invoice, index) =>
            {
                console.log(`%c  Factura ${index + 1}:  `, `background: #00BCD4; color: white`, invoice);
                
                const invoiceHtml = `
                    <div class="invoice-item">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-gray-900 mb-1">
                                ${invoice.description || 'Factura'}
                            </div>
                            <div class="d-flex gap-4">
                                <span class="text-muted fs-7">
                                    <i class="ki-outline ki-category fs-7 me-1"></i>
                                    ${invoice.type || 'N/A'}
                                </span>
                                <span class="text-muted fs-7">
                                    <i class="ki-outline ki-calendar fs-7 me-1"></i>
                                    ${invoice.invoice_date_formatted || invoice.invoice_date || 'N/A'}
                                </span>
                            </div>
                        </div>
                        <div>
                            <a href="#" 
                               class="btn btn-sm-facilitame btn-success-facilitame invoice-download" 
                               data-invoice-id="${invoice.id}">
                                <i class="ki-outline ki-file-down fs-5"></i>
                                Descargar
                            </a>
                        </div>
                    </div>
                `;
                container.innerHTML += invoiceHtml;
            });
        }

        // Inicializar
        console.log('%c  Inicializando...  ', 'background: #673AB7; color: white');
        handleActionButton();

        // Descarga de facturas
        $(document).on("click", ".invoice-download", download_invoice);
        async function download_invoice(e)
        {
            e.preventDefault();
            
            const invoiceId = $(this).data("invoice-id");
            console.log(`%c  Descargando factura ID: ${invoiceId}  `, `background: #009688; color: white`);
            
            let response;
            try
            {
                const data = {
                    invoice_id: invoiceId
                };
                
                response = await $.post("api/invoice-download", data).fail(() => { 
                    throw new Error('Error en descarga');
                });

                response = JSON.parse(response);

                if (response.status != "ok")
                {
                    Swal.fire({
                        icon: "warning",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
                else
                {
                    console.log(`%c  Descargando: ${response.data.filename}  `, `background: #4CAF50; color: white`);
                    download_base64_file(response.data.b64, response.data.filename);
                }

            } catch (error)
            {
                console.error('%c  Error en descarga  ', 'background: #CC0000; color: white', error);

                Swal.fire({
                    icon: "error",
                    html: "Ha ocurrido un error al descargar la factura",
                    buttonsStyling: false,
                    confirmButtonText: "Cerrar",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            }
        }

        function getMimeType(fileName)
        {
            const extension = fileName.split('.').pop().toLowerCase();
            const mimeTypes = {
                'pdf': 'application/pdf',
                'png': 'image/png',
                'jpg': 'image/jpeg',
                'jpeg': 'image/jpeg',
                'gif': 'image/gif',
                'txt': 'text/plain',
                'csv': 'text/csv',
                'doc': 'application/msword',
                'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls': 'application/vnd.ms-excel',
                'xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            };

            return mimeTypes[extension] || 'application/octet-stream';
        }

        function download_base64_file(base64Data, fileName)
        {
            const mimeType = getMimeType(fileName);
            const base64FileData = `data:${mimeType};base64,${base64Data}`;

            const link = document.createElement('a');
            link.href = base64FileData;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            console.log(`%c  Archivo descargado: ${fileName}  `, `background: #4CAF50; color: white`);
        }

    });
})(jQuery);