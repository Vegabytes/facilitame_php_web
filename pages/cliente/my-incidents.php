<?php
$scripts = [];
?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="incidents-results-count">Cargando...</span>
            </div>
            <div class="pagination-size">
                <label for="incidents-page-size">Mostrar:</label>
                <select id="incidents-page-size" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="incidents-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando incidencias...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="incidents-pagination" style="display: none;">
            <div class="pagination-info" id="incidents-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="incidents-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="incidents-page-current">1 / 1</span>
                <button class="btn-pagination" id="incidents-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<!-- Modal valoración -->
<div class="modal fade" id="modal-incidence-valoracion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Valorar incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="modal-incidence-valoracion-request-id">
                <input type="hidden" id="modal-incidence-valoracion-incident-id">
                <p class="fs-5 mb-4">¿Se ha resuelto tu incidencia?</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-success" id="btn-incidence-resuelta">
                        <i class="ki-outline ki-check me-1"></i> Sí, resuelta
                    </button>
                    <button type="button" class="btn btn-warning" id="btn-incidence-noresuelta">
                        <i class="ki-outline ki-cross me-1"></i> No, sigue pendiente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/incidents-paginated-customer';
    
    const STATUS_CLASS = {
        1: 'warning',
        2: 'info',
        3: 'success',
        4: 'muted'
    };
    
    const STATUS_NAME = {
        1: 'Pendiente',
        2: 'Gestionando',
        3: 'Validada',
        4: 'Cerrada'
    };
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('incidents-list');
    const resultsCount = document.getElementById('incidents-results-count');
    const pageInfo = document.getElementById('incidents-page-info');
    const pageCurrent = document.getElementById('incidents-page-current');
    const prevBtn = document.getElementById('incidents-prev');
    const nextBtn = document.getElementById('incidents-next');
    const pageSizeSelect = document.getElementById('incidents-page-size');
    const paginationContainer = document.getElementById('incidents-pagination');
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        listContainer.addEventListener('click', handleListClick);
        initModalHandlers();
        loadData();
    }
    
    function handleListClick(e) {
        if (e.target.closest('.incident-valoracion')) {
            const btn = e.target.closest('.incident-valoracion');
            document.getElementById('modal-incidence-valoracion-request-id').value = btn.dataset.requestId;
            document.getElementById('modal-incidence-valoracion-incident-id').value = btn.dataset.incidentId;
        }
    }
    
    function initModalHandlers() {
        document.getElementById('btn-incidence-resuelta')?.addEventListener('click', () => updateIncidentStatus(3));
        document.getElementById('btn-incidence-noresuelta')?.addEventListener('click', () => updateIncidentStatus(2));
    }
    
    async function updateIncidentStatus(newStatusId) {
        const requestId = document.getElementById('modal-incidence-valoracion-request-id').value;
        const incidentId = document.getElementById('modal-incidence-valoracion-incident-id').value;
        
        try {
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('incident_id', incidentId);
            formData.append('new_status_id', newStatusId);
            
            const response = await fetch('/api/incident-mark-validated', { method: 'POST', body: formData });
            const result = JSON.parse(await response.text());
            
            const message = newStatusId === 3 
                ? result.message_html 
                : 'Un responsable de Facilitame se pondrá en contacto contigo para ayudarte a resolver esta incidencia.';
            
            Swal.fire({ icon: 'success', html: message, timer: 3000, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('modal-incidence-valoracion'))?.hide();
            loadData();
        } catch (error) {
            Swal.fire({ icon: 'error', html: 'No se pudo actualizar la incidencia.' });
        }
    }
    
    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        try {
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.pageSize,
                search: state.searchQuery
            });
            
            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                const { data: items, pagination } = result.data;
                state.totalPages = pagination.total_pages;
                state.totalRecords = pagination.total_records;
                
                renderList(items || []);
                updateResultsCount(pagination);
                updatePaginationControls();
            } else {
                showError(result.message || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        } finally {
            state.isLoading = false;
        }
    }
    
    function renderList(data) {
        if (!data.length) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-shield-tick"></i>
                    </div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'Sin incidencias'}</div>
                    <p class="empty-state-text">
                        ${state.searchQuery 
                            ? 'No se encontraron incidencias para los filtros aplicados' 
                            : 'No tienes incidencias registradas'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(item => {
            const statusClass = STATUS_CLASS[item.status_id] || 'muted';
            const statusName = STATUS_NAME[item.status_id] || 'Desconocido';
            const showValoracion = item.status_id === 1;
            
            return `
                <div class="list-card list-card-warning">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <a href="/request?id=${item.request_id}" class="list-card-customer">
                                Incidencia #${item.id}
                            </a>
                            <span class="badge-status badge-status-${statusClass}">${statusName}</span>
                            <span class="badge-status badge-status-info">${escapeHtml(item.category_name || '')}</span>
                        </div>
                        ${item.details ? `
                            <div class="text-muted" style="font-size: 0.8125rem; margin-bottom: 0.25rem;">
                                <i class="ki-outline ki-message-text" style="font-size: 0.875rem; margin-right: 0.25rem;"></i>
                                ${escapeHtml(item.details)}
                            </div>
                        ` : ''}
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-folder"></i>
                                Solicitud #${item.request_id}
                            </span>
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                Creada: ${item.created_at || '-'}
                            </span>
                            <span>
                                <i class="ki-outline ki-time"></i>
                                Actualizada: ${item.updated_at || '-'}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/request?id=${item.request_id}" class="btn-icon btn-icon-info" title="Ver solicitud">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        ${showValoracion ? `
                            <button type="button" 
                                    class="btn-icon incident-valoracion"
                                    data-request-id="${item.request_id}"
                                    data-incident-id="${item.id}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-incidence-valoracion"
                                    title="Valorar incidencia">
                                <i class="ki-outline ki-check-circle"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function handlePageSizeChange(e) {
        state.pageSize = parseInt(e.target.value, 10);
        state.currentPage = 1;
        loadData();
    }
    
    function updatePaginationControls() {
        pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0 
            ? 'No hay resultados' 
            : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong>`;
    }
    
    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando incidencias...</span>
            </div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-disconnect text-danger"></i>
                </div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadMyIncidents()">
                    <i class="ki-outline ki-arrows-circle me-1"></i>Reintentar
                </button>
            </div>`;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.filterMyIncidents = function(query) {
        state.searchQuery = query.trim();
        state.currentPage = 1;
        loadData();
    };
    
    window.reloadMyIncidents = () => loadData();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>