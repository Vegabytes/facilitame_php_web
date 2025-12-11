(function ($) {
    $(document).ready(function () {
        // Configuración global de SweetAlert para esta página
        const SwalCustom = Swal.mixin({
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light',
                denyButton: 'btn btn-light'
            }
        });
        
        const SwalSuccess = Swal.mixin({
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-success'
            }
        });
        
        const SwalError = Swal.mixin({
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger'
            }
        });
        
        const SwalWarning = Swal.mixin({
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-warning',
                cancelButton: 'btn btn-light'
            }
        });

        // Inicializar validadores para cada formulario
        const validators = {};

        // Validaciones de los formularios
        const formValidators = {
            '#form-user-profile-password-update': {
                fields: {
                    'current_password': {
                        validators: {
                            notEmpty: {
                                message: 'La contraseña es obligatoria'
                            },
                        }
                    },
                    'new_password': {
                        validators: {
                            notEmpty: {
                                message: 'Escribe una nueva contraseña'
                            },
                            callback: {
                                message: 'Escribe una contraseña válida',
                                callback: function (input) {
                                    if (input.value.length > 0) {
                                        return validatePassword(input.value);
                                    }
                                }
                            }
                        }
                    },
                    'new_password_confirm': {
                        validators: {
                            notEmpty: {
                                message: 'Confirma tu contraseña'
                            },
                            identical: {
                                compare: function () {
                                    return document.querySelector('#form-user-profile-password-update [name="new_password"]').value;
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
            },
            '#form-user-profile-details-update': {
                fields: {
                    'name': {
                        validators: {
                            notEmpty: {
                                message: 'El nombre es obligatorio'
                            },
                        }
                    },
                    'lastname': {
                        validators: {
                            notEmpty: {
                                message: 'El apellido es obligatorio'
                            },
                        }
                    },
                    'email': {
                        validators: {
                            notEmpty: {
                                message: 'Escribe una dirección de email'
                            },
                            emailAddress: {
                                message: 'Escribe una dirección de correo válida'
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
            },
            '#form-user-profile-invoice-access-grant': {
                fields: {
                    'allow-invoice-acces': {
                        validators: {
                            notEmpty: {
                                message: 'Debes aceptar el consentimiento para actualizarlo'
                            },
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
            },
        };

        var validatePassword = function (password) {
            var lengthCheck = /.{8,}/;
            var numberCheck = /[0-9]/;
            var letterCheck = /[a-zA-Z]/;
            var specialCharCheck = /[_!@#$%^&*(),.?":{}|<>]/;

            if (!lengthCheck.test(password)) return false;
            if (!numberCheck.test(password)) return false;
            if (!letterCheck.test(password)) return false;
            if (!specialCharCheck.test(password)) return false;

            return true;
        };

        Object.keys(formValidators).forEach(function (formSelector) {
            const form = document.querySelector(formSelector);

            if (!$(formSelector).length) {
                return;
            }

            validators[formSelector] = FormValidation.formValidation(form, formValidators[formSelector]);

            $(form).find('.bold-submit').on('click', function (e) {
                e.preventDefault();

                validators[formSelector].validate().then(function (status) {
                    if (status === 'Valid') {
                        bold_form_submit.call(this, e);
                    } else {
                        SwalError.fire({
                            icon: "error",
                            title: "Error de validación",
                            html: "Por favor, corrige los errores en el formulario antes de enviarlo.",
                            confirmButtonText: "Entendido"
                        });
                    }
                }.bind(this));
            });
        });

        // Envío del formulario
        async function bold_form_submit(e) {
            e.preventDefault();

            let form = $(this).closest("form");
            let btn = $(this);
            let originalText = btn.html();
            let reload = form.data("reload");
            let ajaxurl = form.attr("action");
            let data = form.serialize();

            // Deshabilitar botón y mostrar loading
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

            let response;
            try {
                response = await $.post(ajaxurl, data).fail(() => { return; });
                
                // Parsear solo si es string
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                
                if (response.status == "ok") {
                    SwalSuccess.fire({
                        icon: "success",
                        title: "¡Listo!",
                        html: response.message_html,
                        showConfirmButton: reload != 1,
                        confirmButtonText: "Cerrar",
                        timer: reload == 1 ? 2000 : null,
                        timerProgressBar: reload == 1
                    });

                    if (reload == 1) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    SwalWarning.fire({
                        icon: "warning",
                        title: "Atención",
                        html: response.message_html,
                        confirmButtonText: "Entendido"
                    });
                }
            } catch (error) {
                SwalError.fire({
                    icon: "error",
                    title: "Error",
                    html: "Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.",
                    confirmButtonText: "Cerrar"
                });
                return;
            } finally {
                btn.prop('disabled', false).html(originalText);
                let dismiss = form.find(".btn.dismiss");
                dismiss.click();
            }
        }

        // Envío de formulario con archivo
        $(".bold-submit-file").on("click", function (e) {
            bold_form_submit_file.call(this, e);
        });

        async function bold_form_submit_file(e) {
            e.preventDefault();

            let form = $(this).closest("form")[0];
            let btn = $(this);
            let originalText = btn.html();
            let reload = $(form).data("reload");
            let ajaxurl = $(form).attr("action");
            let formData = new FormData(form);

            // Deshabilitar botón y mostrar loading
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...');

            let response;
            try {
                response = await $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                }).fail(() => { return; });

                // Parsear solo si es string
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                
                if (response.status == "ok") {
                    SwalSuccess.fire({
                        icon: "success",
                        title: "¡Listo!",
                        html: response.message_html,
                        showConfirmButton: reload != 1,
                        confirmButtonText: "Cerrar",
                        timer: reload == 1 ? 2000 : null,
                        timerProgressBar: reload == 1
                    });

                    if (reload == 1) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    SwalWarning.fire({
                        icon: "warning",
                        title: "Atención",
                        html: response.message_html,
                        confirmButtonText: "Entendido"
                    });
                }
            } catch (error) {
                SwalError.fire({
                    icon: "error",
                    title: "Error",
                    html: "Ha ocurrido un error al subir el archivo. Por favor, inténtalo de nuevo.",
                    confirmButtonText: "Cerrar"
                });
                return;
            } finally {
                btn.prop('disabled', false).html(originalText);
                let dismiss = $(form).find(".btn.dismiss");
                dismiss.click();
            }
        }
    });
})(jQuery);