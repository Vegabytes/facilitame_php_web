"use strict";

console.log(`%c  login  `, `background: #222; color: #bada55`); // Black / Green // DEV

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
                            regexp: {
                                regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                message: 'La dirección no es válida',
                            },
                            notEmpty: {
                                message: 'Campo obligatorio'
                            }
                        }
                    },
                    'password': {
                        validators: {
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
                        eleInvalidClass: '',  // comment to enable invalid state icons
                        eleValidClass: '' // comment to enable valid state icons
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
                            if (response.data.status == "ok")
                            {
                                const redirectUrl = form.getAttribute('data-kt-redirect-url');

                                if (redirectUrl)
                                {
                                    console.log(`redirectUrl:`);
                                    console.log(redirectUrl);
                                    location.href = redirectUrl;
                                }
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
                    console.log(`%c  c8  `, `background: #222; color: #bada55`); // Black / Green // DEV
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
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

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



(function ($)
{
    $(document).ready(function ()
    {
        // Prevenir espacios en blanco y mayúsculas en email
        $('input[name="email"]').on('input', function ()
        {            
            let value = $(this).val();
            value = value.replace(/\s+/g, '');
            value = value.toLowerCase();
            $(this).val(value);
        });




        // Acceso como invitado :: inicio
        $("#login-guest").on("click", async function(e)
        {
            e.preventDefault();


            axios.post("api/login", 
                new URLSearchParams({
                    email: "guest@facilitame.es",
                    password: "pass"
                })
            ).then(function (response)
            {
                if (response)
                {
                    if (response.data.status == "ok")
                    {
                        const redirectUrl = "home";

                        if (redirectUrl)
                        {
                            console.log(`redirectUrl:`);
                            console.log(redirectUrl);
                            location.href = redirectUrl;
                        }
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

        });
        // Acceso como invitado :: fin

    });
})(jQuery)