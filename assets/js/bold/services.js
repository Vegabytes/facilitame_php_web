(function ($)
{
    $(document).ready(function ()
    {
        console.log(`%c  services  `, `background: #222; color: #bada55`);

        var elements_make_service_form = $(".service-make-form");
        elements_make_service_form.on("click", make_service_form);
        async function make_service_form()
        {
            $('html, body').animate({
                scrollTop: $('#service-form-container').offset().top
            }, 1000);

            let service_id = $(this).data("service-id");
            let data = {
                "service_id": service_id
            };
            let response;
            try
            {
                response = await $.post("api/service-get-form", data).fail(() => { return; });
                
                // FIX: Verificar si ya es objeto o necesita parsear
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.status == "ok")
                {
                    let elements_container = $("#service-form-elements-container");
                    let service_info = response.data.service_info;
                    let form_elements = response.data.form_elements;
                    let service_phone = $(this).data("phone");

                    $("#service-form-content").removeClass("hidden");
                    $("#service-name").html(service_info.name);
                    elements_container.html(make_form(form_elements));
                    $("#service-id").val(service_id);
                    $("#btn-call-now").attr("href", service_phone);

                    $("#service-form-container").removeClass("h-100");
                }

            } catch (error)
            {
                console.error(error);
                return;
            }
        }

        function make_form(form_elements)
        {
            let html_form = ``;

            form_elements.forEach(function (form_element, index)
            {
                let html_el = ``;
                switch (form_element.type)
                {
                    case "select":
                        html_el = make_select(form_element);
                        break;
                    case "input":
                    case "text":
                        html_el = make_input_text(form_element);
                        break;
                    case "number":
                        html_el = make_input_number(form_element);
                        break;
                    case "date":
                        html_el = make_input_date(form_element);
                        break;
                    case "textarea":
                        html_el = make_input_textarea(form_element);
                        break;
                    default:
                        html_el = ``;
                        break;
                }
                html_form = html_form + html_el;
            });

            return html_form;
        }

        function make_select(form_element)
        {
            let required = form_element.required == 1 ? " required " : "";
            
            // FIX: Verificar si values ya es array o necesita parsear
            let values = form_element.values;
            if (typeof values === 'string') {
                values = JSON.parse(values);
            }

            let html = 
            `<div class="fv-row mb-4">
                <label for="${form_element.name}" class="form-label">${form_element.name}</label>
                <select ${required} class="form-control service-form-input" name="${form_element.name}" data-key="${form_element.key}">
                    <option value="">Selecciona una opción</option>`;

            values.forEach(function (value, index)
            {
                html += `<option value="${value}">${value}</option>`;
            });

            html += `</select></div>`;

            return html;
        }

        function make_input_text(form_element)
        {
            let required = form_element.required == 1 ? " required " : "";

            let html = `
            <div class="fv-row mb-4">
                <label for="${form_element.name}" class="form-label">${form_element.name}</label> 
                <input ${required} type="text" placeholder="${form_element.name}" name="${form_element.name}" data-key="${form_element.key}" autocomplete="off" id="" class="form-control bg-transparent service-form-input">
            </div>`;

            return html;
        }

        function make_input_number(form_element)
        {
            let required = form_element.required == 1 ? " required " : "";

            let html = `
            <div class="fv-row mb-4">
                <label for="${form_element.name}" class="form-label">${form_element.name}</label> 
                <input ${required} type="number" placeholder="${form_element.name}" name="${form_element.name}" data-key="${form_element.key}" autocomplete="off" id="" class="form-control bg-transparent service-form-input">
            </div>`;

            return html;
        }

        function make_input_date(form_element)
        {
            let required = form_element.required == 1 ? " required " : "";            

            let html = `
            <div class="fv-row mb-4">
                <label for="${form_element.name}" class="form-label">${form_element.name}</label> 
                <input ${required} type="date" placeholder="${form_element.name}" name="${form_element.name}" data-key="${form_element.key}" autocomplete="off" id="" class="form-control bg-transparent service-form-input">
            </div>`;

            return html;
        }

        function make_input_textarea(form_element)
        {
            let required = form_element.required == 1 ? " required " : "";

            let html = 
            `<div class="fv-row mb-4">
                <label for="${form_element.name}" class="form-label">${form_element.name}</label>
                <textarea ${required} placeholder="${form_element.name}" name="${form_element.name}" data-key="${form_element.key}" autocomplete="off" id="" class="form-control bg-transparent service-form-input"></textarea>
            </div>`;

            return html;
        }




        $(document).on("click", "#services-form-main button[type='submit']", function (e)
        {
            e.preventDefault();
            setTimeout(() =>
            {
                let form = document.getElementById("services-form-main");

                let documentFile = $("#document")[0].files[0];
                let fileType = $("#file_type").val();

                if (documentFile && fileType)
                {
                    $("#services-form-main").submit();
                }
                else
                {
                    if (form.reportValidity())
                    {
                        $("#services-form-main").submit();
                    }
                    else
                    {
                    }
                }
            }, 500);
        });




        $("#services-form-main").on("submit", async function (e)
        {
            e.preventDefault();

            let form_input_elements = $(".service-form-input");
            let form = [];

            form_input_elements.each(function (index, input_element)
            {
                let aux = {
                    value: $(input_element).val(),
                    name: $(input_element).attr("name"),
                    key: $(input_element).data("key")
                };
                form.push(aux);
            });

            let formData = new FormData();
            formData.append("category_id", $("#service-id").val());
            formData.append("form", JSON.stringify(form));

            let documentFile = $("#document")[0].files[0];
            if (documentFile)
            {
                if ($("#file_type").val() == "")
                {
                    Swal.fire({
                        icon: "warning",
                        html: "Selecciona el tipo de documento que estás enviando, por favor",
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return;
                }
                formData.append("document", documentFile);
                formData.append("file_type_id", $("#file_type").val())
            }

            try
            {
                let response = await $.ajax({
                    url: "api/services-form-main-submit",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false
                });

                // FIX: Verificar si ya es objeto
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                
                if (response.status === "ok")
                {
                    Swal.fire({
                        icon: "success",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        },
                        showConfirmButton: false
                    });
                    setTimeout(() =>
                    {
                        location.href = "my-services";
                    }, 3000);
                } else
                {
                    Swal.fire({
                        icon: response.icon != "" ? response.icon : "warning",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            } catch (error)
            {
                Swal.fire({
                    icon: "warning",
                    html: "Ha ocurrido un error. Inténtalo de nuevo, por favor.",
                    buttonsStyling: false,
                    confirmButtonText: "Cerrar",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            }
        });




        // Llámanos ya
        $(document).on("click", "#btn-call-now", make_call_lead);
        async function make_call_lead()
        {
            let form_input_elements = $(".service-form-input");
            let form = [];

            form_input_elements.each(function (index, input_element)
            {
                let aux = {
                    value: $(input_element).val(),
                    name: $(input_element).attr("name"),
                    key: $(input_element).data("key")
                };
                form.push(aux);
            });

            let formData = new FormData();
            formData.append("category_id", $("#service-id").val());
            formData.append("form", JSON.stringify(form));

            let response = await $.ajax({
                url: "api/make-call-lead",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false
            });
        }




        // Input de texto libre si se selecciona opción 'Otro'
        $(document).on("change", "#service-form-elements-container select.service-form-input", handle_input_other);
        function handle_input_other()
        {
            const ref = $(this).closest(".fv-row");
            const target = ref.next();

            if ($(this).val().toLowerCase() == "otro")
            {
                if (target.length && target.hasClass("other"))
                {
                    return;
                }
                const name = $(this).attr("name");
                const key = $(this).data("key");
                const el = `
                    <div class="fv-row mb-4 other">
                        <input type="text" placeholder="Por favor, especifica" name="Más info" data-key="${key}_other" autocomplete="off" class="form-control bg-transparent service-form-input">
                    </div>
                `;

                ref.after($(el));
            }
            else
            {
                const target = ref.next();
                if (target.length && target.hasClass("other"))
                {
                    target.remove();
                }
            }            
        }

    });
})(jQuery)