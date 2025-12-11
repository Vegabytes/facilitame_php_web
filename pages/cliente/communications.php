<?php
$currentPage = 'communications';

$customer_advisory_id = 0;
$advisory_name = '';
if (!guest()) {
    $stmt = $pdo->prepare("
        SELECT ca.advisory_id, a.razon_social 
        FROM customers_advisories ca
        INNER JOIN advisories a ON a.id = ca.advisory_id
        WHERE ca.customer_id = ?
    ");
    $stmt->execute([USER['id']]);
    $result = $stmt->fetch();
    if ($result) {
        $customer_advisory_id = $result['advisory_id'];
        $advisory_name = $result['razon_social'];
    }
}

if ($customer_advisory_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE advisory_communication_recipients 
        SET is_read = 1, read_at = NOW() 
        WHERE customer_id = ? AND is_read = 0
    ");
    $stmt->execute([USER['id']]);
}

$importanceLabels = [
    'leve' => 'Informativa',
    'media' => 'Normal',
    'importante' => 'Importante'
];

$importanceIcons = [
    'leve' => 'information-2',
    'media' => 'message-text',
    'importante' => 'notification-bing'
];

$importanceClasses = [
    'leve' => 'info',
    'media' => 'primary',
    'importante' => 'danger'
];
?>

<div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <?php if (!$customer_advisory_id): ?>
    <!-- Sin asesoría -->
    <div class="card" style="flex: 1; display: flex; align-items: center; justify-content: center;">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-information-2"></i>
            </div>
            <div class="empty-state-title">No tienes una asesoría vinculada</div>
            <p class="empty-state-text">Para recibir comunicaciones, primero debes estar vinculado a una asesoría</p>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Card principal -->
    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        
        <!-- Controles -->
        <div class="list-controls">
            <div class="results-info">
                <span id="comm-results-count">Cargando...</span>
                <span class="text-muted ms-2" style="font-size: 0.75rem;">— <?php echo htmlspecialchars($advisory_name); ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="pagination-size">
                    <label for="comm-filter-importance">Tipo:</label>
                    <select id="comm-filter-importance" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="leve">Informativa</option>
                        <option value="media">Normal</option>
                        <option value="importante">Importante</option>
                    </select>
                </div>
                <div class="pagination-size">
                    <label for="comm-filter-month">Mes:</label>
                    <select id="comm-filter-month" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php 
                        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        foreach ($months as $idx => $month): 
                        ?>
                        <option value="<?php echo $idx + 1; ?>"><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="comm-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando comunicaciones...</span>
                </div>
            </div>
        </div>
        
        <!-- Paginador -->
        <div class="pagination-container" id="comm-pagination" style="display: none;">
            <div class="pagination-info" id="comm-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="comm-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="comm-page-current">1 / 1</span>
                <button class="btn-pagination" id="comm-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>
        
    </div>
    
    <?php endif; ?>
    
</div>

<!-- Modal Ver Detalle -->
<?php if ($customer_advisory_id): ?>
<div class="modal fade" id="modal_view_communication" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal_comm_title">Detalle</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal_comm_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    'use strict';
    
    const API_URL = '/api-customer-communications-list';
    
    const importanceLabels = <?php echo json_encode($importanceLabels); ?>;
    const importanceIcons = <?php echo json_encode($importanceIcons); ?>;
    const importanceClasses = <?php echo json_encode($importanceClasses); ?>;
    
    const state = {
        currentPage: 1,
        pageSize: 25,
        search: '',
        importance: '',
        month: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    // Cache de comunicaciones cargadas
    let loadedCommunications = [];
    
    const listContainer = document.getElementById('comm-list');
    const resultsCount = document.getElementById('comm-results-count');
    const pageInfo = document.getElementById('comm-page-info');
    const pageCurrent = document.getElementById('comm-page-current');
    const prevBtn = document.getElementById('comm-prev');
    const nextBtn = document.getElementById('comm-next');
    const importanceFilter = document.getElementById('comm-filter-importance');
    const monthFilter = document.getElementById('comm-filter-month');
    const paginationContainer = document.getElementById('comm-pagination');
    
    let searchTimeout = null;
    
    function init() {
        if (!listContainer) return;
        
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        importanceFilter.addEventListener('change', (e) => { state.importance = e.target.value; state.currentPage = 1; loadData(); });
        monthFilter.addEventListener('change', (e) => { state.month = e.target.value; state.currentPage = 1; loadData(); });
        
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
                search: state.search,
                importance: state.importance,
                month: state.month
            });
            
            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                state.totalPages = result.data.pagination.total_pages;
                state.totalRecords = result.data.pagination.total_records;
                
                // Guardar en cache
                loadedCommunications = result.data.communications || [];
                
                renderList(loadedCommunications);
                updateResultsCount(result.data.pagination);
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
                        <i class="ki-outline ki-sms"></i>
                    </div>
                    <div class="empty-state-title">${state.search || state.importance || state.month ? 'Sin resultados' : 'No hay comunicaciones'}</div>
                    <p class="empty-state-text">
                        ${state.search || state.importance || state.month 
                            ? 'No se encontraron comunicaciones con los filtros aplicados' 
                            : 'No has recibido comunicaciones de tu asesoría todavía'}
                    </p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }
        
        listContainer.innerHTML = data.map((item, index) => {
            const label = importanceLabels[item.importance] || item.importance;
            const colorClass = importanceClasses[item.importance] || 'muted';
            const dateDisplay = item.created_at_display || formatDate(item.created_at);
            const preview = item.message.length > 80 ? item.message.substring(0, 80) + '...' : item.message;
            
            const attachmentsBadge = item.attachments && item.attachments.length > 0
                ? `<span class="text-primary"><i class="ki-outline ki-paperclip"></i> ${item.attachments.length}</span>`
                : '';

            return `
                <div class="list-card list-card-${colorClass}" style="cursor: pointer;" onclick="viewCommunication(${index})">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${item.id}</span>
                            <span class="fw-semibold">${escapeHtml(item.subject)}</span>
                            <span class="badge-status badge-status-${colorClass}">${label}</span>
                        </div>
                        <div class="text-muted" style="font-size: 0.8125rem; margin-bottom: 0.25rem;">
                            ${escapeHtml(preview)}
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-calendar"></i>
                                ${dateDisplay}
                            </span>
                            ${attachmentsBadge}
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <button class="btn-icon" title="Ver completo">
                            <i class="ki-outline ki-eye"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
        
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
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
                <span class="ms-2">Cargando comunicaciones...</span>
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
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadCommunications()">
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
    
    // Función para ver comunicación usando el cache
    window.viewCommunication = function(index) {
        const comm = loadedCommunications[index];
        if (!comm) return;
        
        const modal = new bootstrap.Modal(document.getElementById('modal_view_communication'));
        const label = importanceLabels[comm.importance] || comm.importance;
        const colorClass = importanceClasses[comm.importance] || 'muted';
        const dateDisplay = comm.created_at_display || formatDate(comm.created_at);
        
        // Archivos adjuntos
        let attachmentsHtml = '';
        if (comm.attachments && comm.attachments.length > 0) {
            attachmentsHtml = `
                <div class="mt-4">
                    <h6 class="mb-2"><i class="ki-outline ki-paperclip me-1"></i>Archivos adjuntos (${comm.attachments.length})</h6>
                    <div class="d-flex flex-wrap gap-2">
                        ${comm.attachments.map(file => {
                            let icon = 'ki-document';
                            if (file.mime_type && file.mime_type.includes('image')) icon = 'ki-picture';
                            else if (file.mime_type && file.mime_type.includes('pdf')) icon = 'ki-document';
                            else if (file.mime_type && (file.mime_type.includes('sheet') || file.mime_type.includes('excel'))) icon = 'ki-chart-simple';
                            return `<a href="/api/file-download?type=communication_file&id=${file.id}" target="_blank" class="btn btn-sm btn-light-primary">
                                <i class="ki-outline ${icon} me-1"></i>
                                ${escapeHtml(file.filename)}
                                ${file.filesize ? `<span class="text-muted">(${file.filesize} MB)</span>` : ''}
                            </a>`;
                        }).join('')}
                    </div>
                </div>`;
        }

        document.getElementById('modal_comm_title').textContent = comm.subject;
        document.getElementById('modal_comm_body').innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge-status badge-status-${colorClass}">${label}</span>
                <span class="text-muted small"><i class="ki-outline ki-calendar me-1"></i>${dateDisplay}</span>
            </div>
            <hr class="my-3">
            <div class="bg-light rounded p-4" style="white-space: pre-wrap; line-height: 1.7; font-size: 0.9375rem;">
                ${escapeHtml(comm.message)}
            </div>
            ${attachmentsHtml}`;

        modal.show();
    };
    
    window.filterCommunications = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.search = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCommunications = () => loadData();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>