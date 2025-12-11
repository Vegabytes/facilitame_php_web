<!-- Componente: home-datatable-citas-asesoria.php -->

<div class="list-controls">
    <div class="results-info"><span id="citas-results-count">Cargando...</span></div>
    <div class="d-flex align-items-center gap-3">
        <div class="filter-buttons">
            <button class="btn btn-sm btn-filter active" data-filter="">Todas</button>
            <button class="btn btn-sm btn-filter" data-filter="solicitado">Solicitadas</button>
            <button class="btn btn-sm btn-filter" data-filter="agendado">Agendadas</button>
        </div>
        <div class="pagination-size">
            <label for="citas-page-size">Mostrar:</label>
            <select id="citas-page-size" class="form-select form-select-sm">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
</div>

<div class="tab-list-container" id="citas-list">
    <div class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span class="ms-2">Cargando...</span>
    </div>
</div>

<div class="pagination-container" id="citas-pagination" style="display: none;">
    <div class="pagination-info" id="citas-page-info"></div>
    <div class="pagination-nav">
        <button class="btn-pagination" id="citas-prev" disabled><i class="ki-outline ki-left"></i></button>
        <span class="pagination-current" id="citas-page-current">1 / 1</span>
        <button class="btn-pagination" id="citas-next" disabled><i class="ki-outline ki-right"></i></button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api-advisory-appointments-paginated';
    const state = { currentPage: 1, pageSize: 25, searchQuery: '', filter: '', totalPages: 1, totalRecords: 0, isLoading: false };
    
    const els = {
        list: document.getElementById('citas-list'),
        resultsCount: document.getElementById('citas-results-count'),
        pageInfo: document.getElementById('citas-page-info'),
        pageCurrent: document.getElementById('citas-page-current'),
        prevBtn: document.getElementById('citas-prev'),
        nextBtn: document.getElementById('citas-next'),
        pageSizeSelect: document.getElementById('citas-page-size'),
        pagination: document.getElementById('citas-pagination'),
        filterBtns: document.querySelectorAll('#tab-citas .btn-filter')
    };
    
    let searchTimeout = null;
    
    function init() {
        els.prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        els.nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        els.pageSizeSelect.addEventListener('change', e => {
            state.pageSize = +e.target.value;
            state.currentPage = 1;
            loadData();
        });
        
        els.filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                els.filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                state.filter = this.dataset.filter;
                state.currentPage = 1;
                loadData();
            });
        });
        
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
            
            if (state.filter) {
                params.append('status', state.filter);
            }
            
            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                const { appointments: items, pagination } = result.data;
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
            els.list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-calendar"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay citas'}</div>
                    <p class="empty-state-text">${state.searchQuery ? 'No se encontraron resultados' : 'No tienes citas pendientes'}</p>
                </div>`;
            els.pagination.style.display = 'none';
            return;
        }
        
        els.list.innerHTML = data.map(c => {
            const statusBadge = getStatusBadge(c.status);
            const needsAction = c.needs_confirmation_from === 'advisory';
            const dateStr = formatAppointmentDate(c);
            const departmentLabel = getDepartmentLabel(c.department);
            
            // Color de la card según estado
            let cardColor = 'primary'; // azul por defecto
            if (c.status === 'solicitado') cardColor = 'warning';
            else if (c.status === 'agendado') cardColor = 'info';
            else if (c.status === 'completado') cardColor = 'success';
            else if (c.status === 'cancelado') cardColor = 'danger';
            
            return `
                <div class="list-card list-card-${cardColor}">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="d-flex align-items-center gap-2">
                                <i class="ki-outline ki-profile-user text-muted"></i>
                                <strong>${escapeHtml(c.customer_name)}</strong>
                            </span>
                            ${statusBadge}
                            ${needsAction ? '<span class="badge-status badge-status-danger">Requiere acción</span>' : ''}
                            ${c.unread_messages > 0 ? `<span class="badge-status badge-status-info">${c.unread_messages} mensaje${c.unread_messages > 1 ? 's' : ''}</span>` : ''}
                        </div>
                        <div class="list-card-meta">
                            ${dateStr ? `<span><i class="ki-outline ki-calendar"></i> ${dateStr}</span>` : ''}
                            ${departmentLabel ? `<span><i class="ki-outline ki-briefcase"></i> ${departmentLabel}</span>` : ''}
                            ${c.reason ? `<span><i class="ki-outline ki-message-text"></i> ${escapeHtml(truncate(c.reason, 50))}</span>` : ''}
                            <span><i class="ki-outline ki-time"></i> ${formatDate(c.updated_at || c.created_at)}</span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/appointment?id=${c.id}" class="btn-icon btn-icon-info" title="Ver cita">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                        ${c.unread_messages > 0 ? `
                            <a href="/appointment?id=${c.id}#chat" class="btn-icon btn-icon-info" title="Ver mensajes">
                                <i class="ki-outline ki-message-text"></i>
                            </a>
                        ` : ''}
                        <a href="/customer?id=${c.customer_id}" class="btn-icon btn-icon-primary" title="Ver cliente">
                            <i class="ki-outline ki-profile-user"></i>
                        </a>
                    </div>
                </div>`;
        }).join('');
        
        els.pagination.style.display = 'flex';
        els.list.scrollTop = 0;
    }
    
    function getStatusBadge(status) {
        const badges = {
            'solicitado': '<span class="badge-status badge-status-warning">Solicitada</span>',
            'agendado': '<span class="badge-status badge-status-info">Agendada</span>',
            'completado': '<span class="badge-status badge-status-success">Completada</span>',
            'cancelado': '<span class="badge-status badge-status-danger">Cancelada</span>',
            'propuesto': '<span class="badge-status badge-status-primary">Propuesta</span>'
        };
        return badges[status] || `<span class="badge-status badge-status-light">${status}</span>`;
    }
    
    function getDepartmentLabel(dept) {
        if (!dept) return '';
        const labels = {
            'fiscal': 'Fiscal',
            'laboral': 'Laboral',
            'contable': 'Contable',
            'juridico': 'Jurídico',
            'general': 'General'
        };
        return labels[dept] || dept;
    }
    
    function formatAppointmentDate(c) {
        const date = c.scheduled_date || c.proposed_date;
        if (!date) return '';
        
        const d = new Date(date);
        const options = { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' };
        let str = d.toLocaleDateString('es-ES', options);
        
        if (c.scheduled_date) {
            return str;
        } else if (c.proposed_date) {
            return `Propuesta: ${str}`;
        }
        return str;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
    }
    
    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        els.pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        els.pageInfo.textContent = `Página ${state.currentPage} de ${state.totalPages}`;
        els.prevBtn.disabled = state.currentPage <= 1;
        els.nextBtn.disabled = state.currentPage >= state.totalPages;
        els.pagination.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(p) {
        els.resultsCount.innerHTML = p.total_records === 0
            ? 'No hay resultados'
            : `Mostrando <strong>${p.from}-${p.to}</strong> de <strong>${p.total_records}</strong>`;
    }
    
    function showLoading() {
        els.list.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }
    
    function showError(msg) {
        els.list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(msg)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadCitas()">
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
    
    // Exports
    window.initCitasTab = function() {
        if (!window._citasInit) {
            window._citasInit = true;
            init();
        }
    };
    
    window.filterCitas = function(query) {
        if (!window._citasInit) return;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCitas = () => loadData();
})();
</script>