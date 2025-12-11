"use strict";


// Class definition
var KTSigninGeneral = function ()
{
    // Elements
    var form;
    var submitButton;
    var validator;

    // Handle form
    var handleValidation = function (e)
    {
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'email': {
                        validators: {
                            emailAddress: {
                                message: 'La dirección no es válida'
                            },
                            notEmpty: {
                                message: 'Campo obligatorio'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        // eleInvalidClass: '',  // comment to enable invalid state icons
                        // eleValidClass: '' // comment to enable valid state icons
                    })
                }
            }
        );
    }

    var handleSubmitAjax = function (e)
    {
        // Handle form submit
        submitButton.addEventListener('click', function (e)
        {
            // Prevent button default action
            e.preventDefault();

            // Validate form
            validator.validate().then(function (status)
            {
                if (status == 'Valid')
                {
                    // Show loading indication
                    submitButton.setAttribute('data-kt-indicator', 'on');

                    // Disable button to avoid multiple click
                    submitButton.disabled = true;

                    // Check axios library docs: https://axios-http.com/docs/intro
                    axios.post(submitButton.closest('form').getAttribute('action'), new FormData(form)).then(function (response)
                    {
                        if (response)
                        {
                            // form.reset();                                                        
                            if (response.data.status == "ok")
                            {
                                Swal.fire({
                                    html: response.data.message_html,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Cerrar",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    },
                                    showConfirmButton: false
                                });

                                setTimeout(() => {
                                    // location.href = "login";
                                }, 8000);
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
                                        confirmButton: "btn btn-primary"
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
                                    confirmButton: "btn btn-primary"
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
                                confirmButton: "btn btn-primary"
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
                    // Show error popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
                    Swal.fire({
                        html: "Ha ocurrido un error.<br>Inténtalo de nuevo, por favor.",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        });
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

    // Public functions
    return {
        // Initialization
        init: function ()
        {
            form = document.querySelector('#kt_recovery_form');
            submitButton = document.querySelector('#kt_recovery_submit');

            handleValidation();

            // if (isValidUrl(submitButton.closest('form').getAttribute('action')))
            handleSubmitAjax(); // use for ajax submit
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function ()
{
    KTSigninGeneral.init();
});
