(function ($)
{
    $(document).ready(function ()
    {

        console.log(`%c  bold-submit  `, `background: #222; color: #bada55`);

        $(".bold-submit").on("click", function (e)
        {
            bold_form_submit.call(this, e);
        });
        async function bold_form_submit(e)
        {
            e.preventDefault();
            console.log(`%c  bold_form_submit()  `, `background: #004080; color: white`);

            // Para formularios que pueden desencadenar diferentes acciones, primero se evalúa si el botón que ha hecho submit tiene data-action. Si es así, todas las variables se tomarán del <button>, y no del <form> :: inicio

            const submitButton = $(e.target).closest("button");
            const form = submitButton.closest("form");

            let ajaxurl, confirm_message, reload, redirect, html_validation;

            if (submitButton.data("action")) // Tomar valores de <button>
            {                
                ajaxurl = submitButton.data("action");
                confirm_message = submitButton.data("confirm-message") || false;
                reload = submitButton.data("reload") == "1";
                redirect = submitButton.data("redirect") || false;
                html_validation = submitButton.data("html-validation") == "1";
            }
            else // Tomar valores de <form>
            {                
                ajaxurl = form.attr("action");
                confirm_message = form.data("confirm-message") || false;
                reload = form.data("reload") == "1";
                redirect = form.data("redirect") || false;
                html_validation = form.data("html-validation") == "1";
            }
            // Para formularios que pueden desencadenar diferentes acciones, primero se evalúa si el botón que ha hecho submit tiene data-action. Si es así, todas las variables se tomarán del <button>, y no del <form> :: fin

            let data = form.serialize();

            if (reload == undefined || redirect == undefined || ajaxurl == "" || ajaxurl == undefined)
            {
                alert("4030283490: No válido.");
                return;
            }

            if (confirm_message !== false)
            {
                const result = await Swal.fire({
                    title: 'Atención',
                    html: confirm_message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: "btn btn-primary",
                        cancelButton: "btn"
                    },
                });

                if (result.isConfirmed === false)
                {
                    return;
                }
            }

            let response;
            try
            {
                response = await $.post(ajaxurl, data).fail(() => { return; });
                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    Swal.fire({
                        icon: "success",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        },
                        showConfirmButton: (reload || redirect) ? false : true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false
                    });

                    if (reload === true)
                    {
                        setTimeout(() =>
                        {
                            location.reload();
                        }, 2000);
                    }
                    else if (redirect !== false)
                    {
                        setTimeout(() =>
                        {
                            location.href = redirect;
                        }, 2000);
                    }
                }
                else
                {
                    Swal.fire({
                        icon: (response.icon != "") ? response.icon : "warning",
                        html: response.message_html + `<br><br><span style="font-size:0.75rem">Info: ${response.code}</span>`,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        },
                        showCloseButton: (reload === true) ? false : true
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
                let dismiss = form.find(".btn.dismiss");
                dismiss.click();
            }
        }




        $(".bold-fetch").on("click", function (e)
        {
            bold_fetch.call(this, e);
        });
        async function bold_fetch(e)
        {
            e.preventDefault();
            console.log(`%c  ${arguments.callee.name}()  `, `background: #004080; color: white`); // Blue / White

            let response;
            const data = {
                file_id: $(this).data("doc-id")
            }
            try
            {
                response = await $.post("api/document-fetch", data).fail(() => { return; });

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
                    download_base64_file(response.data.b64, response.data.filename);
                }

            } catch (error)
            {
                console.log(`%c  2436901306  `, `background: #CC0000; color: white`); // Red / White

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
            }

            return;


        }




        function download_base64_file(base64Data, fileName)
        {
            const mimeType = getMimeType(fileName); // Obtener el tipo MIME basado en la extensión
            const base64FileData = `data:${mimeType};base64,${base64Data}`; // Construir el URI base64

            // Crear un enlace temporal
            const link = document.createElement('a');
            link.href = base64FileData;  // Establecer el enlace con el Base64 Data URI
            link.download = fileName; // El nombre del archivo que se descargará
            document.body.appendChild(link); // Añadir el enlace al cuerpo (es necesario para Firefox)
            link.click(); // Simular un clic en el enlace
            document.body.removeChild(link); // Eliminar el enlace del DOM
        }




        function getMimeType(fileName)
        {
            const extension = fileName.split('.').pop().toLowerCase();
            let mimeType = '';

            switch (extension)
            {
                case 'pdf':
                    mimeType = 'application/pdf';
                    break;
                case 'png':
                    mimeType = 'image/png';
                    break;
                case 'jpg':
                case 'jpeg':
                    mimeType = 'image/jpeg';
                    break;
                case 'gif':
                    mimeType = 'image/gif';
                    break;
                case 'txt':
                    mimeType = 'text/plain';
                    break;
                case 'csv':
                    mimeType = 'text/csv';
                    break;
                case 'doc':
                    mimeType = 'application/msword';
                    break;
                case 'docx':
                    mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
                case 'xls':
                    mimeType = 'application/vnd.ms-excel';
                    break;
                case 'xlsx':
                    mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                default:
                    mimeType = 'application/octet-stream'; // Tipo genérico por defecto
            }

            return mimeType;
        }

    });
})(jQuery)