"use strict";

var KTSignupGeneral = function ()
{
    var form;
    var submitButton;
    var validator;
    var passwordMeter;

    var handleForm = function (e)
    {
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'nif_cif': {
                        validators: {
                            callback: {
                                message: 'El CIF/NIF es obligatorio, comprueba si es correcto.',
                                callback: function (input)
                                {
                                    let aux_input = $("#nif-cif");
                                    let parent = aux_input.closest("div");
                                    return (parent.is(":visible")) ? validate_nif_cif(aux_input.val()) : true;
                                }
                            }
                        }
                    },
                    'cif': {
                        validators: {
                            callback: {
                                message: 'El CIF es obligatorio y debe ser valido',
                                callback: function (input)
                                {
                                    let role = $("select[name='role']").val();
                                    if (role === "asesoria") {
                                        let cif = $("#cif").val();
                                        if (!cif || cif.trim() === "") return false;
                                        return /^[A-Za-z]\d{7}[A-Za-z0-9]$/.test(cif);
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    'razon_social': {
                        validators: {
                            callback: {
                                message: 'La razon social es obligatoria',
                                callback: function (input)
                                {
                                    let role = $("select[name='role']").val();
                                    if (role === "asesoria") {
                                        return $("#razon_social").val().trim() !== "";
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    'direccion': {
                        validators: {
                            callback: {
                                message: 'La direccion es obligatoria',
                                callback: function (input)
                                {
                                    let role = $("select[name='role']").val();
                                    if (role === "asesoria") {
                                        return $("#direccion").val().trim() !== "";
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    'email_empresa': {
                        validators: {
                            callback: {
                                message: 'El email de empresa es obligatorio y debe ser valido',
                                callback: function (input)
                                {
                                    let role = $("select[name='role']").val();
                                    if (role === "asesoria") {
                                        let email = $("#email_empresa").val().trim();
                                        if (email === "") return false;
                                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    'plan': {
                        validators: {
                            callback: {
                                message: 'Debes seleccionar un plan',
                                callback: function (input)
                                {
                                    let role = $("select[name='role']").val();
                                    if (role === "asesoria") {
                                        return $("#plan").val() !== null && $("#plan").val() !== "";
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    'lastname': {
                        validators: {
                            callback: {
                                message: 'El apellido es obligatorio',
                                callback: function (input)
                                {
                                    let aux_input = $("#lastname");
                                    let parent = aux_input.closest("div");
                                    return (parent.is(":visible")) ? (aux_input.val() != "") : true;
                                }
                            }
                        }
                    },
                    'role': {
                        validators: {
                            notEmpty: {
                                message: 'Debes seleccionar un tipo de cuenta'
                            }
                        }
                    },
                    'region_code': {
                        validators: {
                            notEmpty: {
                                message: 'Debes seleccionar una provincia'
                            }
                        }
                    },
                    'name': {
                        validators: {
                            notEmpty: {
                                message: 'El nombre es obligatorio'
                            }
                        }
                    },
                    'email': {
                        validators: {
                            regexp: {
                                regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                message: 'Escribe un email valido',
                            },
                            notEmpty: {
                                message: 'El email es obligatorio'
                            }
                        }
                    },
                    'phone': {
                        validators: {
                            notEmpty: {
                                message: 'El telefono es obligatorio'
                            }
                        }
                    },
                    'password': {
                        validators: {
                            notEmpty: {
                                message: 'La contrasena es obligatoria'
                            },
                            callback: {
                                message: 'Escribe una contrasena valida',
                                callback: function (input)
                                {
                                    if (input.value.length > 0)
                                    {
                                        return validatePassword();
                                    }
                                }
                            }
                        }
                    },
                    'confirm-password': {
                        validators: {
                            notEmpty: {
                                message: 'Confirma tu contrasena'
                            },
                            identical: {
                                compare: function ()
                                {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Las contrasenas no coinciden'
                            }
                        }
                    },
'privacy-policy': {
    validators: {
        notEmpty: {
            message: 'Debes aceptar la politica de privacidad'
        }
    }
},
'client_subtype': {
    validators: {
        callback: {
            message: 'Debes seleccionar el tamaño',
            callback: function (input)
            {
                let advisory_code = $("#advisory_code").val().trim();
                let role = $("select[name='role']").val();
                let subtypeFields = $("#client-subtype-fields");
                
                if (advisory_code && subtypeFields.is(":visible") && (role === 'autonomo' || role === 'empresa')) {
                    let subtype = $("select[name='client_subtype']:visible").val();
                    return subtype !== null && subtype !== "" && subtype !== undefined;
                }
                return true;
            }
        }
    }
}
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger({
                        event: {
                            password: false
                        }
                    }),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        submitButton.addEventListener('click', function (e)
        {
            e.preventDefault();

            validator.revalidateField('password');

            validator.validate().then(function (status)
            {
                if (status == 'Valid')
                {
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    submitButton.disabled = true;

                    axios.post(submitButton.closest('form').getAttribute('action'), new FormData(form)).then(function (response)
                    {
                        if (response)
                        {
                            if (response.data.status == "ok")
                            {
                                Swal.fire({
                                    html: response.data.message_html,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Cerrar",
                                    customClass: {
                                        confirmButton: "btn btn-primary-facilitame"
                                    }
                                });
                            }
                            else
                            {
                                Swal.fire({
                                    html: response.data.message_html,
                                    icon: "warning",
                                    buttonsStyling: false,
                                    confirmButtonText: "Cerrar",
                                    customClass: {
                                        confirmButton: "btn btn-primary-facilitame"
                                    }
                                });
                            }
                        } else
                        {
                            Swal.fire({
                                html: "Ha ocurrido un error.<br>Intentalo de nuevo, por favor.",
                                icon: "warning",
                                buttonsStyling: false,
                                confirmButtonText: "Cerrar",
                                customClass: {
                                    confirmButton: "btn btn-primary-facilitame"
                                }
                            });
                        }
                    }).catch(function (error)
                    {
                        Swal.fire({
                            html: "Ha ocurrido un error.<br>Intentalo de nuevo, por favor.",
                            icon: "warning",
                            buttonsStyling: false,
                            confirmButtonText: "Cerrar",
                            customClass: {
                                confirmButton: "btn btn-primary-facilitame"
                            }
                        });
                    }).then(() =>
                    {
                        submitButton.removeAttribute('data-kt-indicator');
                        submitButton.disabled = false;
                    });

                } else
                {
                    Swal.fire({
                        text: "Parece que hay algunos fallos. Comprueba el formulario.",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary-facilitame"
                        }
                    });
                }
            });
        });

        form.querySelector('input[name="password"]').addEventListener('input', function ()
        {
            if (this.value.length > 0)
            {
                validator.updateFieldStatus('password', 'NotValidated');
                passwordMeter.setScore(evaluatePassword(this.value));
            }
        });
    }

    var validatePassword = function ()
    {
        var password = form.querySelector('input[name="password"]').value;
        return (evaluatePassword(password) > 75);
    }

    var evaluatePassword = function (password)
    {
        var score = 0;

        if (password.length >= 8)
        {
            score += 50;
        }
        if (/\d/.test(password))
        {
            score += 25;
        }
        if (/[a-zA-Z]/.test(password))
        {
            score += 25;
        }

        return score;
    }

    function validate_nif_cif(valor)
    {
        var regex = /^(?:[A-Za-z]\d{8}|\d{8}[A-Za-z]|[A-Za-z]\d{7}[A-Za-z])$/;
        return regex.test(valor);
    }

    return {
        init: function ()
        {
            form = document.querySelector('#kt_sign_up_form');
            submitButton = document.querySelector('#kt_sign_up_submit');
            passwordMeter = KTPasswordMeter.getInstance(form.querySelector('[data-kt-password-meter="true"]'));

            handleForm();
        }
    };
}();

KTUtil.onDOMContentLoaded(function ()
{
    KTSignupGeneral.init();

    $("select[name='role']").on("change", function ()
    {
        let selected_role = $(this).val();
        switch (selected_role)
        {
            case "particular":
                $("input[name='lastname']").closest("div").removeClass("hidden").show();
                $("input[name='nif_cif']").closest("div").addClass("hidden").hide();
                break;
            case "autonomo":
                $("input[name='lastname']").closest("div").removeClass("hidden").show();
                $("input[name='nif_cif']").closest("div").removeClass("hidden").show();
                break;
            case "empresa":
                $("input[name='lastname']").closest("div").addClass("hidden").hide();
                $("input[name='nif_cif']").closest("div").removeClass("hidden").show();
                break;
            case "asesoria":
                $("input[name='lastname']").closest("div").removeClass("hidden").show();
                $("input[name='nif_cif']").closest("div").addClass("hidden").hide();
                break;
            default:
                break;
        }
    });

    $('#role-id').on('change', function ()
    {
        var selectedValue = $(this).val();

        if (selectedValue === 'autonomo' || selectedValue === 'particular')
        {
            $("input[name='name']").attr("placeholder", "Nombre de contacto");
        }
        else
        {
            $("input[name='name']").attr("placeholder", "Nombre");
        }
    });
});