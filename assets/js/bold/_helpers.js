/**
 * Helpers unificados para Facilitame
 * Usar estos métodos en lugar de toastr o Swal directamente
 */

const FacilitameHelpers = {
    /**
     * Mostrar notificación de éxito
     * @param {string} message - Mensaje a mostrar
     * @param {string} title - Título opcional
     */
    success: function(message, title = '¡Éxito!') {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },

    /**
     * Mostrar notificación de error
     * @param {string} message - Mensaje de error
     * @param {string} title - Título opcional
     */
    error: function(message, title = 'Error') {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    },

    /**
     * Mostrar notificación de advertencia
     * @param {string} message - Mensaje de advertencia
     * @param {string} title - Título opcional
     */
    warning: function(message, title = 'Atención') {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            timer: 4000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },

    /**
     * Mostrar notificación informativa
     * @param {string} message - Mensaje informativo
     * @param {string} title - Título opcional
     */
    info: function(message, title = 'Información') {
        Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },

    /**
     * Diálogo de confirmación
     * @param {string} message - Mensaje de confirmación
     * @param {string} title - Título
     * @param {string} confirmText - Texto del botón confirmar
     * @param {string} cancelText - Texto del botón cancelar
     * @returns {Promise<boolean>}
     */
    confirm: async function(message, title = '¿Estás seguro?', confirmText = 'Sí, continuar', cancelText = 'Cancelar') {
        const result = await Swal.fire({
            icon: 'question',
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light ms-2'
            }
        });
        return result.isConfirmed;
    },

    /**
     * Diálogo de confirmación para acciones peligrosas (eliminar, etc)
     * @param {string} message - Mensaje
     * @param {string} title - Título
     * @returns {Promise<boolean>}
     */
    confirmDanger: async function(message, title = '¿Estás seguro?') {
        const result = await Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-light ms-2'
            }
        });
        return result.isConfirmed;
    },

    /**
     * Mostrar loading
     * @param {string} message - Mensaje mientras carga
     */
    loading: function(message = 'Procesando...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    /**
     * Cerrar loading
     */
    closeLoading: function() {
        Swal.close();
    },

    /**
     * Verificar si la sesión sigue activa con el mismo usuario
     * @param {number} expectedUserId - ID del usuario esperado
     * @returns {Promise<boolean>}
     */
    verifySession: async function(expectedUserId) {
        try {
            const response = await fetch('/api/verify-session');
            const data = await response.json();

            if (data.status === 'ok' && data.data && data.data.user_id === expectedUserId) {
                return true;
            }

            // Sesión cambió
            this.error('Tu sesión ha cambiado. Por favor, recarga la página.', 'Sesión cambiada');
            setTimeout(() => location.reload(), 2000);
            return false;
        } catch (e) {
            console.error('Error verificando sesión:', e);
            return false;
        }
    },

    /**
     * Hacer fetch con manejo de errores unificado
     * @param {string} url - URL del API
     * @param {object} options - Opciones de fetch
     * @param {boolean} showLoading - Mostrar loading
     * @returns {Promise<object>}
     */
    fetch: async function(url, options = {}, showLoading = true) {
        if (showLoading) {
            this.loading();
        }

        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            };

            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (showLoading) {
                this.closeLoading();
            }

            if (data.status === 'ko') {
                this.error(data.message || 'Ha ocurrido un error');
                return null;
            }

            return data;
        } catch (error) {
            if (showLoading) {
                this.closeLoading();
            }
            console.error('Fetch error:', error);
            this.error('Error de conexión. Por favor, inténtalo de nuevo.');
            return null;
        }
    },

    /**
     * Escapar HTML para prevenir XSS
     * @param {string} text - Texto a escapar
     * @returns {string}
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Formatear fecha
     * @param {string} dateStr - Fecha en formato ISO
     * @returns {string}
     */
    formatDate: function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },

    /**
     * Formatear fecha y hora
     * @param {string} dateStr - Fecha en formato ISO
     * @returns {string}
     */
    formatDateTime: function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

// Alias global para compatibilidad
window.FH = FacilitameHelpers;
