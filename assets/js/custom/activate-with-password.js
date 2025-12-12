"use strict";

// Class definition
var KTActivatePassword = function ()
{
    // Elements
    var form;
    var submitButton;
    var validator;

    // Handle form
    var handleForm = function (e)
    {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'password': {
                        validators: {
                            notEmpty: {
                                message: 'La contraseña es obligatoria'
                            },
                            callback: {
                                message: 'La contraseña debe tener mínimo 8 caracteres con letras y números',
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
                    'password_confirm': {
                        validators: {
                            notEmpty: {
                                message: 'Confirma tu contraseña'
                            },
                            identical: {
                                compare: function ()
                                {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Las contraseñas no coinciden'
                            }
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger({
                        event: {
                            password: false
                        }
                    }),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                    })
                }
            }
        );

        // Handle form submit
        submitButton.addEventListener('click', function (e)
        {
            e.preventDefault();

            validator.revalidateField('password');

            validator.validate().then(function (status)
            {
                if (status == 'Valid')
                {
                    // Show loading indication
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    submitButton.disabled = true;

                    // Submit form via axios
                    axios.post(form.getAttribute('action'), new FormData(form)).then(function (response)
                    {
                        if (response && response.data)
                        {
                            if (response.data.status == "ok")
                            {
                                Swal.fire({
                                    html: response.data.message_html || '¡Cuenta activada correctamente!',
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Iniciar sesión",
                                    customClass: {
                                        confirmButton: "btn btn-primary-facilitame"
                                    }
                                }).then(function () {
                                    location.href = "/login";
                                });
                            }
                            else
                            {
                                Swal.fire({
                                    html: response.data.message_html || 'Ha ocurrido un error',
                                    icon: "warning",
                                    buttonsStyling: false,
                                    confirmButtonText: "Cerrar",
                                    customClass: {
                                        confirmButton: "btn btn-primary-facilitame"
                                    }
                                });
                            }
                        }
                        else
                        {
                            Swal.fire({
                                html: "Ha ocurrido un error.<br>Inténtalo de nuevo, por favor.",
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
                        var message = "Ha ocurrido un error.<br>Inténtalo de nuevo, por favor.";
                        if (error.response && error.response.data && error.response.data.message_html) {
                            message = error.response.data.message_html;
                        }
                        Swal.fire({
                            html: message,
                            icon: "warning",
                            buttonsStyling: false,
                            confirmButtonText: "Cerrar",
                            customClass: {
                                confirmButton: "btn btn-primary-facilitame"
                            }
                        });
                    }).then(() =>
                    {
                        // Hide loading indication
                        submitButton.removeAttribute('data-kt-indicator');
                        submitButton.disabled = false;
                    });
                }
                else
                {
                    Swal.fire({
                        text: "Parece que hay algunos errores. Comprueba el formulario.",
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
    }

    // Password validation
    var validatePassword = function ()
    {
        var password = form.querySelector('input[name="password"]').value;

        // Minimum 8 characters
        if (password.length < 8) return false;

        // Must contain letters
        if (!/[a-zA-Z]/.test(password)) return false;

        // Must contain numbers
        if (!/\d/.test(password)) return false;

        return true;
    }

    // Public functions
    return {
        // Initialization
        init: function ()
        {
            form = document.querySelector('#kt_activate_form');
            submitButton = document.querySelector('#kt_activate_submit');

            if (form && submitButton)
            {
                handleForm();
            }
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function ()
{
    KTActivatePassword.init();
});
