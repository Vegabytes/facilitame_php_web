"use strict";

var KTActivateAccount = function() {
    var form, submitButton, validator;

    return {
        init: function() {
            form = document.querySelector('#kt_activate_form');
            submitButton = document.querySelector('#kt_activate_submit');

            validator = FormValidation.formValidation(form, {
                fields: {
                    'password': {
                        validators: {
                            notEmpty: { message: 'La contraseña es obligatoria' },
                            stringLength: {
                                min: 8,
                                message: 'La contraseña debe tener mínimo 8 caracteres'
                            }
                        }
                    },
                    'password_confirm': {
                        validators: {
                            notEmpty: { message: 'Confirma tu contraseña' },
                            identical: {
                                compare: function() {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Las contraseñas no coinciden'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            });

            submitButton.addEventListener('click', function(e) {
                e.preventDefault();

                validator.validate().then(function(status) {
                    if (status == 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        axios.post(form.getAttribute('action'), new FormData(form))
                        .then(function(response) {
                            submitButton.removeAttribute('data-kt-indicator');
                            submitButton.disabled = false;

                            if (response.data.status === 'ok') {
                                Swal.fire({
                                    text: response.data.message_html,
                                    icon: 'success',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Iniciar sesión',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    }
                                }).then(function() {
                                    window.location.href = '/login';
                                });
                            } else {
                                Swal.fire({
                                    text: response.data.message_html,
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Entendido',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    }
                                });
                            }
                        })
                        .catch(function(error) {
                            submitButton.removeAttribute('data-kt-indicator');
                            submitButton.disabled = false;
                            
                            Swal.fire({
                                text: 'Error al activar la cuenta. Inténtalo de nuevo.',
                                icon: 'error',
                                buttonsStyling: false,
                                confirmButtonText: 'Entendido',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                }
                            });
                        });
                    }
                });
            });
        }
    };
}();

KTUtil.onDOMContentLoaded(function() {
    KTActivateAccount.init();
});