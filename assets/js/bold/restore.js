"use strict";

// Class definition
var KTSignupGeneral = function ()
{
    // Elements
    var form;
    var submitButton;
    var validator;    

    // Handle form
    var handleForm = function (e)
    {
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
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
                                message: 'Escribe una contraseña válida',
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
                        // eleInvalidClass: '',  // comment to enable invalid state icons
                        // eleValidClass: '' // comment to enable valid state icons
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

                    // Disable button to avoid multiple click
                    submitButton.disabled = true;

                    // Hide loading indication
                    // submitButton.removeAttribute('data-kt-indicator');
                    // Enable button
                    submitButton.disabled = false;




                    // Check axios library docs: https://axios-http.com/docs/intro
                    axios.post(submitButton.closest('form').getAttribute('action'), new FormData(form)).then(function (response)
                    {
                        if (response)
                        {
                            // form.reset();
                            if (response.data.status == "ok")
                            {
                                location.href = "login";
                            }
                            else
                            {
                                // Show message popup.For more info check the plugin's official documentation: https://sweetalert2.github.io/
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
                            // Show error popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
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
                        Swal.fire({
                            html: "Ha ocurrido un error.<br>Inténtalo de nuevo, por favor.",
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

                        // Enable button
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
    }

    // Password input validation
    var validatePassword = function ()
    {
        var password = form.querySelector('input[name="password"]').value;
        return (evaluatePassword(password) > 75);
    }


    var isValidUrl = function (url)
    {
        try
        {
            new URL(url);
            return true;
        } catch (e)
        {
            return false;
        }
    }

    var evaluatePassword = function (password)
    {
        var score = 0;

        if (password.length >= 8)
        {
            score += 25;
        }
        if (/\d/.test(password))
        {
            score += 25;
        }
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password))
        {
            score += 25;
        }
        if (/[a-zA-Z]/.test(password))
        {
            score += 25;
        }

        return score;
    }


    // Public functions
    return {
        // Initialization
        init: function ()
        {
            // Elements
            form = document.querySelector('#kt_restore_form');
            submitButton = document.querySelector('#kt_restore_submit');

            handleForm();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function ()
{
    KTSignupGeneral.init();
});
