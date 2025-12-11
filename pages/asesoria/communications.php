<?php
$currentPage = 'communications';

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    echo '<div class="alert alert-danger m-5">Asesoría no encontrada.</div>';
    return;
}

$advisory_id = $advisory['id'];

// Obtener clientes para el modal de envío
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.lastname, u.email
    FROM users u
    INNER JOIN customers_advisories ca ON ca.customer_id = u.id
    WHERE ca.advisory_id = ?
    ORDER BY u.name, u.lastname
");
$stmt->execute([$advisory_id]);
$clients = $stmt->fetchAll();

$importanceLabels = [
    'leve' => 'Informativa',
    'media' => 'Normal',
    'importante' => 'Importante'
];

$importanceClasses = [
    'leve' => 'info',
    'media' => 'primary',
    'importante' => 'danger'
];
?>

<div id="facilita-app">
    <div class="customers-page">
        
        <!-- Card principal -->
        <div class="card">
            
            <!-- Controles -->
            <div class="list-controls">
                <div class="results-info">
                    <span id="comm-results-count">Cargando...</span>
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
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modal_new_communication">
                        <i class="ki-outline ki-plus fs-4 me-1"></i>NUEVO AVISO
                    </button>
                </div>
            </div>
            
            <!-- Listado -->
            <div class="card-body">
                <div class="tab-list-container" id="comm-list">
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
        
    </div>
</div>

<!-- Modal Nuevo Comunicado -->
<div class="modal fade" id="modal_new_communication" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-message-add"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Nueva Comunicación</h5>
                        <p class="text-muted fs-7 mb-0">Envía un comunicado a tus clientes</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_new_communication" enctype="multipart/form-data">
                <div class="modal-body pt-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Asunto <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" required placeholder="Asunto del comunicado">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Mensaje <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Escribe tu mensaje..."></textarea>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prioridad <span class="text-danger">*</span></label>
                            <select name="importance" class="form-select" required>
                                <option value="leve">Informativa</option>
                                <option value="media" selected>Normal</option>
                                <option value="importante">Importante</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Destinatarios <span class="text-danger">*</span></label>
                            <select name="target_type" class="form-select" required id="target_type_select">
                                <option value="all">Todos los clientes</option>
                                <option value="autonomo">Solo Autónomos</option>
                                <option value="empresa">Solo Empresas</option>
                                <option value="particular">Solo Particulares</option>
                                <option value="comunidad">Solo Comunidades</option>
                                <option value="asociacion">Solo Asociaciones</option>
                                <option value="selected">Selección manual</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4" id="clients_selector" style="display: none;">
                        <label class="form-label fw-semibold">Seleccionar clientes</label>
                        <div class="border rounded p-3 clients-selector-list">
                            <?php if (empty($clients)): ?>
                            <p class="text-muted mb-0">No tienes clientes registrados</p>
                            <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="selected_clients[]" value="<?php echo $client['id']; ?>" id="client_<?php echo $client['id']; ?>">
                                <label class="form-check-label" for="client_<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars(ucwords($client['name'] . ' ' . $client['lastname'])); ?>
                                    <span class="text-muted ms-2"><?php echo htmlspecialchars($client['email']); ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Archivos adjuntos -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Archivos adjuntos</label>
                        <div class="upload-drop-zone" id="comm-drop-zone">
                            <i class="ki-outline ki-file-up"></i>
                            <span class="drop-zone-text">Arrastra archivos o haz clic para seleccionar</span>
                            <input type="file" name="attachments[]" id="comm-attachments" class="drop-zone-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                        </div>
                        <div class="form-text">PDF, Word, Excel, imágenes. Máx. 10MB por archivo, 25MB total.</div>
                        <div id="comm-files-preview" class="mt-2"></div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">¿Cómo funciona?</span>
                            <span class="info-box-text">El comunicado se enviará por email y quedará visible en el panel de cada cliente.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-send me-1"></i>
                        Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Comunicado -->
<div class="modal fade" id="modal_view_communication" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="view_comm_title">Detalle</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="view_comm_body">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <span class="ms-2">Cargando...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var API_URL = '/api-advisory-communications-list';
    
    var importanceLabels = <?php echo json_encode($importanceLabels); ?>;
    var importanceClasses = <?php echo json_encode($importanceClasses); ?>;
    
    var state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        importance: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    var listContainer = document.getElementById('comm-list');
    var resultsCount = document.getElementById('comm-results-count');
    var pageInfo = document.getElementById('comm-page-info');
    var pageCurrent = document.getElementById('comm-page-current');
    var prevBtn = document.getElementById('comm-prev');
    var nextBtn = document.getElementById('comm-next');
    var importanceFilter = document.getElementById('comm-filter-importance');
    var paginationContainer = document.getElementById('comm-pagination');
    
    var searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        importanceFilter.addEventListener('change', handleImportanceFilter);
        loadData();
    }
    
    function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        var params = new URLSearchParams({
            page: state.currentPage,
            limit: state.pageSize,
            search: state.searchQuery,
            importance: state.importance
        });
        
        fetch(API_URL + '?' + params)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    state.totalPages = result.data.pagination.total_pages;
                    state.totalRecords = result.data.pagination.total_records;
                    renderList(result.data.data || []);
                    updateResultsCount(result.data.pagination);
                    updatePaginationControls();
                } else {
                    showError(result.message || 'Error al cargar datos');
                }
            })
            .catch(function(err) { showError('Error de conexión'); })
            .finally(function() { state.isLoading = false; });
    }
    
    function renderList(data) {
        if (!data || data.length === 0) {
            var msg = state.searchQuery || state.importance ? 'No se encontraron comunicaciones' : 'No hay comunicaciones enviadas';
            var title = state.searchQuery || state.importance ? 'Sin resultados' : 'Sin comunicaciones';
            listContainer.innerHTML = 
                '<div class="empty-state">' +
                    '<div class="empty-state-icon"><i class="ki-outline ki-sms"></i></div>' +
                    '<div class="empty-state-title">' + title + '</div>' +
                    '<p class="empty-state-text">' + msg + '</p>' +
                '</div>';
            paginationContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(item) {
            var colorClass = importanceClasses[item.importance] || 'primary';
            var label = importanceLabels[item.importance] || item.importance;
            var preview = item.message.length > 80 ? item.message.substring(0, 80) + '...' : item.message;
            
            var attachmentsBadge = item.attachments_count > 0
                ? '<span class="text-primary"><i class="ki-outline ki-paperclip"></i> ' + item.attachments_count + ' archivo' + (item.attachments_count > 1 ? 's' : '') + '</span>'
                : '';

            html += '<div class="list-card list-card-' + colorClass + '">' +
                '<div class="list-card-content">' +
                    '<div class="list-card-title">' +
                        '<span class="fw-semibold">' + escapeHtml(item.subject) + '</span>' +
                        '<span class="badge-status badge-status-' + colorClass + '">' + label + '</span>' +
                    '</div>' +
                    '<div class="list-card-desc">' + escapeHtml(preview) + '</div>' +
                    '<div class="list-card-meta">' +
                        '<span><i class="ki-outline ki-calendar"></i> ' + item.created_at + '</span>' +
                        '<span><i class="ki-outline ki-people"></i> ' + item.total_recipients + ' destinatarios</span>' +
                        '<span class="text-success"><i class="ki-outline ki-check"></i> ' + item.read_count + ' leídos</span>' +
                        (item.pending_count > 0 ? '<span class="text-warning"><i class="ki-outline ki-time"></i> ' + item.pending_count + ' pendientes</span>' : '') +
                        attachmentsBadge +
                    '</div>' +
                '</div>' +
                '<div class="list-card-actions">' +
                    '<button class="btn-icon" onclick="viewCommunication(' + item.id + ')" title="Ver detalle">' +
                        '<i class="ki-outline ki-eye"></i>' +
                    '</button>' +
                '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
        listContainer.scrollTop = 0;
    }
    
    function handleImportanceFilter(e) {
        state.importance = e.target.value;
        state.currentPage = 1;
        loadData();
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        pageInfo.textContent = 'Página ' + state.currentPage + ' de ' + state.totalPages;
        pageCurrent.textContent = state.currentPage + ' / ' + state.totalPages;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
    }
    
    function updateResultsCount(p) {
        resultsCount.innerHTML = p.total_records === 0 
            ? 'No hay resultados' 
            : 'Mostrando <strong>' + p.from + '-' + p.to + '</strong> de <strong>' + p.total_records + '</strong>';
    }
    
    function showLoading() {
        listContainer.innerHTML = 
            '<div class="loading-state">' +
                '<div class="spinner-border spinner-border-sm text-primary"></div>' +
                '<span class="ms-2">Cargando comunicaciones...</span>' +
            '</div>';
    }
    
    function showError(msg) {
        listContainer.innerHTML = 
            '<div class="empty-state">' +
                '<div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>' +
                '<div class="empty-state-title">Error</div>' +
                '<p class="empty-state-text">' + escapeHtml(msg) + '</p>' +
                '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadCommunications()">' +
                    '<i class="ki-outline ki-arrows-circle me-1"></i>Reintentar' +
                '</button>' +
            '</div>';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Función para el buscador del header
    window.filterCommunications = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    window.reloadCommunications = function() { loadData(); };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// Toggle selector de clientes
document.getElementById('target_type_select').addEventListener('change', function() {
    document.getElementById('clients_selector').style.display = this.value === 'selected' ? 'block' : 'none';
});

// Formulario nuevo comunicado
document.getElementById('form_new_communication').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
fetch('/api/advisory-send-communication', { method: 'POST', body: new FormData(this) })

        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Comunicación enviada',
                    text: data.message || 'Se ha enviado correctamente',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                bootstrap.Modal.getInstance(document.getElementById('modal_new_communication')).hide();
                document.getElementById('form_new_communication').reset();
                document.getElementById('clients_selector').style.display = 'none';
                window.reloadCommunications();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al enviar'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
});

// Ver comunicación
function viewCommunication(id) {
    var modal = new bootstrap.Modal(document.getElementById('modal_view_communication'));
    modal.show();
    document.getElementById('view_comm_body').innerHTML = 
        '<div class="loading-state">' +
            '<div class="spinner-border spinner-border-sm text-primary"></div>' +
            '<span class="ms-2">Cargando...</span>' +
        '</div>';
    
    var importanceLabels = <?php echo json_encode($importanceLabels); ?>;
    var importanceClasses = <?php echo json_encode($importanceClasses); ?>;
    
fetch('/api/advisory-get-communication?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok') {
                var c = data.data;
                var colorClass = importanceClasses[c.importance] || 'primary';
                var label = importanceLabels[c.importance] || c.importance;
                
                // Archivos adjuntos
                var attachmentsHtml = '';
                if (c.attachments && c.attachments.length > 0) {
                    attachmentsHtml = '<div class="mb-4">' +
                        '<h6 class="mb-2"><i class="ki-outline ki-paperclip me-1"></i>Archivos adjuntos (' + c.attachments.length + ')</h6>' +
                        '<div class="d-flex flex-wrap gap-2">';
                    c.attachments.forEach(function(file) {
                        var icon = 'ki-document';
                        if (file.mime_type && file.mime_type.includes('image')) icon = 'ki-picture';
                        else if (file.mime_type && file.mime_type.includes('pdf')) icon = 'ki-document';
                        else if (file.mime_type && (file.mime_type.includes('sheet') || file.mime_type.includes('excel'))) icon = 'ki-chart-simple';

                        attachmentsHtml += '<a href="/api/file-download?type=communication_file&id=' + file.id + '" target="_blank" class="btn btn-sm btn-light-primary">' +
                            '<i class="ki-outline ' + icon + ' me-1"></i>' +
                            escapeHtml(file.filename) +
                            (file.filesize ? ' <span class="text-muted">(' + file.filesize + ' MB)</span>' : '') +
                        '</a>';
                    });
                    attachmentsHtml += '</div></div>';
                }

                var html = '<div class="mb-4">' +
                    '<div class="d-flex gap-2 mb-3">' +
                        '<span class="badge-status badge-status-' + colorClass + '">' + label + '</span>' +
                        '<span class="badge-status badge-status-muted">' + (c.target_label || 'Todos') + '</span>' +
                    '</div>' +
                    '<div class="text-muted small mb-3"><i class="ki-outline ki-calendar me-1"></i>' + c.created_at + '</div>' +
                    '<div class="bg-light rounded p-3 mb-4" style="white-space: pre-wrap; line-height: 1.6;">' + escapeHtml(c.message) + '</div>' +
                    attachmentsHtml +
                '</div>' +
                '<hr class="my-3">' +
                '<div class="d-flex justify-content-between align-items-center mb-3">' +
                    '<h6 class="mb-0">Destinatarios (' + c.recipients.length + ')</h6>' +
                    '<div>' +
                        '<span class="badge-status badge-status-success me-1">' + c.stats.read + ' leídos</span>' +
                        '<span class="badge-status badge-status-warning">' + c.stats.pending + ' pendientes</span>' +
                    '</div>' +
                '</div>' +
                '<div class="table-responsive">' +
                    '<table class="table table-sm table-row-bordered align-middle fs-7">' +
                        '<thead><tr class="text-muted fw-bold"><th>Cliente</th><th>Email</th><th>Estado</th></tr></thead>' +
                        '<tbody>';
                
                c.recipients.forEach(function(r) {
                    html += '<tr>' +
                        '<td>' + escapeHtml(r.name) + '</td>' +
                        '<td class="text-muted">' + escapeHtml(r.email) + '</td>' +
                        '<td>' + (r.is_read 
                            ? '<span class="badge-status badge-status-success">Leído</span>' 
                            : '<span class="badge-status badge-status-warning">Pendiente</span>') + 
                        '</td>' +
                    '</tr>';
                });
                
                html += '</tbody></table></div>';
                
                document.getElementById('view_comm_title').textContent = c.subject;
                document.getElementById('view_comm_body').innerHTML = html;
            } else {
                document.getElementById('view_comm_body').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(function() {
            document.getElementById('view_comm_body').innerHTML = '<div class="alert alert-danger">Error al cargar</div>';
        });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Preview de archivos seleccionados
document.getElementById('comm-attachments').addEventListener('change', function() {
    var preview = document.getElementById('comm-files-preview');
    var files = this.files;

    if (files.length === 0) {
        preview.innerHTML = '';
        return;
    }

    var html = '<div class="d-flex flex-wrap gap-2">';
    var totalSize = 0;

    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        totalSize += parseFloat(sizeMB);

        var icon = 'ki-document';
        if (file.type.includes('image')) icon = 'ki-picture';
        else if (file.type.includes('pdf')) icon = 'ki-document';
        else if (file.type.includes('sheet') || file.type.includes('excel')) icon = 'ki-chart-simple';

        html += '<span class="badge bg-light text-dark border">' +
            '<i class="ki-outline ' + icon + ' me-1"></i>' +
            escapeHtml(file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name) +
            ' <span class="text-muted">(' + sizeMB + ' MB)</span>' +
        '</span>';
    }

    html += '</div>';

    if (totalSize > 25) {
        html += '<div class="text-danger small mt-1"><i class="ki-outline ki-information me-1"></i>El tamaño total excede 25MB</div>';
    }

    preview.innerHTML = html;
});

// Limpiar preview al cerrar modal
document.getElementById('modal_new_communication').addEventListener('hidden.bs.modal', function() {
    document.getElementById('comm-files-preview').innerHTML = '';
    document.getElementById('comm-attachments').value = '';
});
</script>