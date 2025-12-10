<?php
header('Content-Type: text/html; charset=utf-8');
$scripts = [];

$TRANSLATIONS = [
    "target_type" => [
        "customer" => "Cliente", "request" => "Petición", "offer" => "Oferta",
        "message" => "Mensaje", "message_provider" => "Mensaje (proveedor)",
        "notification" => "Notificación", "incident" => "Incidencia",
        "invite" => "Invitación", "document" => "Documento", "invoice" => "Factura"
    ],
    "event" => [
        "sign_up" => "Registro", "activate" => "Activación", "create" => "Creación",
        "delete" => "Eliminación", "offer_available" => "Oferta disponible",
        "mark_read" => "Marcado como leído", "mark_open" => "Marcado como abierto",
        "accept" => "Aceptada", "in_progress" => "En progreso", "confirmed" => "Confirmada",
        "active" => "Activa", "send" => "Envío", "report" => "Reportar"
    ]
];
?>

<div class="logs-page">
    
    <div class="card">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="logs-results-count">Cargando...</span>
            </div>
            <div class="pagination-size">
                <label for="logs-page-size">Mostrar:</label>
                <select id="logs-page-size" class="form-select form-select-sm">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="tab-list-container" id="logs-list">
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="logs-pagination" style="display: none;">
            <div class="pagination-info" id="logs-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="logs-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="logs-page-current">1 / 1</span>
                <button class="btn-pagination" id="logs-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api/logs-paginated';
    const TRANSLATIONS = <?php echo json_encode($TRANSLATIONS); ?>;
    
    const EVENT_STATUS = {
        sign_up: 'success', activate: 'success', create: 'primary', 
        delete: 'danger', accept: 'success', in_progress: 'warning', 
        confirmed: 'info', active: 'success', send: 'info', 
        report: 'danger', mark_open: 'muted', offer_available: 'warning',
        mark_read: 'muted'
    };
    
    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    const listContainer = document.getElementById('logs-list');
    const resultsCount = document.getElementById('logs-results-count');
    const pageInfo = document.getElementById('logs-page-info');
    const pageCurrent = document.getElementById('logs-page-current');
    const prevBtn = document.getElementById('logs-prev');
    const nextBtn = document.getElementById('logs-next');
    const pageSizeSelect = document.getElementById('logs-page-size');
    const paginationContainer = document.getElementById('logs-pagination');
    
    let searchTimeout = null;
    
    function translate(type, key) {
        if (!key) return '';
        return TRANSLATIONS[type]?.[key] || key;
    }
    
    function getStatusClass(event) {
        return EVENT_STATUS[event] || 'muted';
    }
    
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    
    function formatTime(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
    
    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        loadData();
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
                    <div class="empty-state-icon"><i class="ki-outline ki-archive"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay registros'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron logs para "${escapeHtml(state.searchQuery)}"` : 'Los eventos del sistema aparecerán aquí'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map(log => {
            const statusClass = getStatusClass(log.event);
            const eventText = translate('event', log.event);
            const targetText = translate('target_type', log.target_type);
            const linkText = translate('target_type', log.link_type);
            const hasLink = log.link_type && log.link_id;
            
            return `
                <div class="list-card list-card-${statusClass}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="fw-semibold">${escapeHtml(log.triggered_by_name)}</span>
                            <span class="text-muted">›</span>
                            <span class="badge-status badge-status-${statusClass}">${eventText}</span>
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-sms"></i>
                                ${escapeHtml(log.triggered_by_email)}
                            </span>
                            <span>
                                <i class="ki-outline ki-abstract-26"></i>
                                ${hasLink 
                                    ? `<a href="/${log.link_type}?id=${log.link_id}" class="text-primary">${targetText} #${log.target_id}</a>`
                                    : `${targetText} #${log.target_id}`
                                }
                            </span>
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${formatDate(log.created_at)} ${formatTime(log.created_at)}
                            </span>
                            ${log.data && log.data !== '[]' ? `
                                <span class="text-muted" title="${escapeHtml(log.data)}">
                                    <i class="ki-outline ki-information-2"></i>
                                    Datos adicionales
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    ${hasLink ? `
                        <div class="list-card-actions">
                            <a href="/${log.link_type}?id=${log.link_id}" class="btn-icon" title="Ver ${linkText.toLowerCase()}">
                                <i class="ki-outline ki-eye"></i>
                            </a>
                        </div>
                    ` : ''}
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
        pageInfo.innerHTML = `Mostrando página ${state.currentPage} de ${state.totalPages}`;
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
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>
            <div class="skeleton-tab-card"><div class="skeleton-tab-content"><div class="skeleton-tab-line title"></div><div class="skeleton-tab-line subtitle"></div></div></div>`;
    }
    
    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadLogs()">
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
    
    window.filterLogs = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadLogs = () => loadData();
    
    // Init inmediato
    init();
})();
</script>