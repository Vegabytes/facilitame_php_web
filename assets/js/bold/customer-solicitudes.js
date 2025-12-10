$(document).ready(function() {
    
    // Función para cargar solicitudes del cliente
    function loadSolicitudes(customerId) {
        const container = $('#customer-requests-list');
        const loadingState = $('#loading-state');
        
        $.post('/api/admin-customer-get-requests', { customerId: customerId }, function(response) {
            // Parsear respuesta si es string
            response = typeof response === 'string' ? JSON.parse(response) : response;
            
            console.log('Respuesta del servidor:', response); // Para debug
            
            // Remover spinner de carga
            if (loadingState.length) {
                loadingState.remove();
            }
            
            if (response.status === "ok") {
                const solicitudes = Array.isArray(response.data) ? response.data : [];
                
                // Limpiar contenedor
                container.empty();
                
                // Si no hay solicitudes
                if (solicitudes.length === 0) {
                    showEmptyState();
                    return;
                }
                
                // Renderizar cada solicitud
                solicitudes.forEach(function(request) {
                    container.append(renderRequestMiniCard(request));
                });
                
            } else {
                // Error en la respuesta o sin datos
                container.empty();
                container.html(`
                    <div class="text-center py-10">
                        <i class="ki-outline ki-information-5 fs-3x text-warning mb-3 d-block"></i>
                        <h5 class="text-gray-700 fw-bold mb-2">Sin solicitudes</h5>
                        <p class="text-muted mb-0">No hay solicitudes para este cliente.</p>
                    </div>
                `);
            }
            
        }).fail(function(xhr, status, error) {
            console.error('Error al cargar solicitudes:', error, xhr.responseText); // Para debug
            
            // Remover spinner
            if (loadingState.length) {
                loadingState.remove();
            }
            
            // Mostrar error
            container.empty();
            container.html(`
                <div class="text-center py-10">
                    <i class="ki-outline ki-information-5 fs-3x text-danger mb-3 d-block"></i>
                    <h5 class="text-gray-700 fw-bold mb-2">Error al cargar solicitudes</h5>
                    <p class="text-muted mb-4">No se pudieron cargar las solicitudes del cliente</p>
                    <button onclick="location.reload()" class="btn btn-sm btn-primary-facilitame">
                        <i class="ki-outline ki-arrows-circle fs-5"></i>
                        Reintentar
                    </button>
                </div>
            `);
        });
    }
    
    // Función para renderizar una solicitud como list-card
    function renderRequestMiniCard(request) {
        const requestDate = request.request_date ? formatDate(request.request_date) : '-';
        const updatedAt = request.updated_at ? formatDate(request.updated_at) : '-';
        const categoryName = request.category_name || 'Sin categoría';
        const requestInfo = request.request_info || '';
        
        return `
            <div class="list-card list-card-primary">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <a href="request?id=${request.id}" class="text-hover-primary">
                            Solicitud #${request.id}
                        </a>
                    </div>
                    <div class="list-card-subtitle">
                        <i class="ki-outline ki-category fs-7"></i>
                        ${categoryName}
                    </div>
                    <div class="list-card-meta">
                        <div class="list-card-meta-item">
                            <i class="ki-outline ki-calendar fs-7"></i>
                            Creada: ${requestDate}
                        </div>
                        <div class="list-card-meta-item">
                            <i class="ki-outline ki-time fs-7"></i>
                            Actualizada: ${updatedAt}
                        </div>
                    </div>
                    ${requestInfo ? `<div class="small text-gray-600 mt-2">${requestInfo}</div>` : ''}
                </div>
                <div class="list-card-actions">
                    ${getBadgeHtml(request.status)}
                    <a href="request?id=${request.id}" class="btn btn-sm-facilitame btn-primary-facilitame">
                        <i class="ki-outline ki-eye fs-5 text-white"></i>
                        Ver
                    </a>
                </div>
            </div>
        `;
    }
    
    // Función para mostrar estado vacío
    function showEmptyState() {
        const container = $('#customer-requests-list');
        container.html(`
            <div class="text-center py-10">
                <i class="ki-outline ki-file-deleted fs-5x text-muted mb-5 d-block"></i>
                <h4 class="text-gray-700 fw-bold mb-2">No hay solicitudes</h4>
                <p class="text-muted mb-0">Este cliente no tiene solicitudes registradas aún.</p>
            </div>
        `);
    }
    
    // Función auxiliar para formatear fechas
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Función auxiliar para obtener el badge HTML según el estado
    function getBadgeHtml(status) {
        const badges = {
            'Iniciada': 'badge-facilitame badge-info-facilitame',
            'Aceptada': 'badge-facilitame badge-success-facilitame',
            'En progreso': 'badge-facilitame badge-warning-facilitame',
            'Activada': 'badge-facilitame badge-success-facilitame',
            'Eliminada': 'badge-facilitame badge-danger-facilitame',
            'Cerrada': 'badge-facilitame'
        };
        
        const badgeClass = badges[status] || 'badge-facilitame';
        return `<span class="badge ${badgeClass}">${status}</span>`;
    }
    
    // Cargar solicitudes si existe CUSTOMER_ID
    if (typeof CUSTOMER_ID !== "undefined" && CUSTOMER_ID) {
        loadSolicitudes(CUSTOMER_ID);
    } else {
        console.error('CUSTOMER_ID no está definido');
        $('#loading-state').remove();
        showEmptyState();
    }
    
});