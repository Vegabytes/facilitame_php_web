(function ($)
{
    $(document).ready(function ()
    {
        console.log(`%c  profile  `, `background: #222; color: #bada55`); // Black / Green // DEV

        $(document).on("click", ".btn-invoice-upload", function(){
            $("#request-id-to-upload").val($(this).data("request-id"));
        })

        $(".bold-submit-file").on("click", function (e)
        {
            bold_form_submit_file.call(this, e);
        });
        async function bold_form_submit_file(e)
        {
            console.log(`%c  bold_form_submit_file()  `, `background: #004080; color: white`); // Blue / White // DEV
            e.preventDefault();

            let form = $(this).closest("form")[0];
            let reload = $(form).data("reload");
            let ajaxurl = $(form).attr("action");

            // Crear un FormData y agregar todos los campos del formulario
            let formData = new FormData(form);

            let response;
            try
            {
                response = await $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: formData,
                    processData: false, // Evitar que jQuery procese los datos
                    contentType: false, // Evitar que jQuery establezca el Content-Type
                }).fail(() => { return; });

                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    Swal.fire({
                        icon: "success",
                        html: response.message_html,
                        buttonsStyling: false,
                        showConfirmButton: reload != 1, // Mostrar botón sólo si reload != 1
                        confirmButtonText: reload != 1 ? "Cerrar" : null, // Texto del botón si se muestra
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });

                    if (reload == 1)
                    {
                        setTimeout(() =>
                        {
                            location.reload();
                        }, 4000);
                    }
                } else
                {
                    Swal.fire({
                        icon: "warning",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        },
                        showCloseButton: (reload == 1) ? false : true
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
                let dismiss = $(form).find(".btn.dismiss");
                dismiss.click();
            }
        }






    });
})(jQuery);
