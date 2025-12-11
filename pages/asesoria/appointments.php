<?php
/**
 * Listado de Citas - Asesoría
 * Usando estilos genéricos de dashboard-common.css
 */
$currentPage = 'appointments';

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();
$advisory_id = $advisory ? $advisory['id'] : null;

if (!$advisory_id) {
    echo '<div class="alert alert-danger m-5">No tienes una asesoría configurada.</div>';
    return;
}

// KPIs
$counts = ['solicitado' => 0, 'agendado' => 0, 'finalizado' => 0, 'cancelado' => 0, 'total' => 0, 'pendiente_confirmacion' => 0];
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM advisory_appointments WHERE advisory_id = ? GROUP BY status");
$stmt->execute([$advisory_id]);
while ($row = $stmt->fetch()) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = (int)$row['count'];
    }
    $counts['total'] += (int)$row['count'];
}

// Contar citas pendientes de confirmación de la asesoría
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM advisory_appointments WHERE advisory_id = ? AND needs_confirmation_from = 'advisory'");
$stmt->execute([$advisory_id]);
$row = $stmt->fetch();
$counts['pendiente_confirmacion'] = (int)($row['count'] ?? 0);

// Contar mensajes no leídos
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM advisory_messages am
        INNER JOIN advisory_appointments aa ON aa.id = am.appointment_id
        WHERE aa.advisory_id = ? 
        AND am.sender_type = 'customer' 
        AND (am.is_read = 0 OR am.is_read IS NULL)
        AND aa.status IN ('solicitado', 'agendado')
    ");
    $stmt->execute([$advisory_id]);
    $row = $stmt->fetch();
    $counts['mensajes_sin_leer'] = (int)($row['count'] ?? 0);
} catch (Exception $e) {
    $counts['mensajes_sin_leer'] = 0;
}

// Obtener clientes para el modal
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.lastname, u.email 
    FROM users u
    INNER JOIN customers_advisories ca ON ca.customer_id = u.id
    WHERE ca.advisory_id = ?
    ORDER BY u.name, u.lastname
");
$stmt->execute([$advisory_id]);
$customers = $stmt->fetchAll();

$typeLabels = ['llamada' => 'Llamada', 'reunion_virtual' => 'Videollamada', 'reunion_presencial' => 'Presencial'];
$deptLabels = ['contabilidad' => 'Contabilidad', 'fiscalidad' => 'Fiscalidad', 'laboral' => 'Laboral', 'gestion' => 'Gestión'];
$statusLabels = ['solicitado' => 'Pendiente', 'agendado' => 'Confirmada', 'finalizado' => 'Finalizada', 'cancelado' => 'Cancelada'];
?>

<div id="facilita-app">
    <div class="dashboard-asesoria-home">
        
        <?php if ($counts['pendiente_confirmacion'] > 0): ?>
        <div class="appointments-alert appointments-alert-warning">
            <i class="ki-outline ki-notification-bing"></i>
            <div class="appointments-alert-content">
                <h4>¡Tienes <?php echo $counts['pendiente_confirmacion']; ?> propuesta(s) de clientes!</h4>
                <p>Revisa las fechas propuestas y acepta o reprograma.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($counts['mensajes_sin_leer'] > 0): ?>
        <div class="appointments-alert appointments-alert-info">
            <i class="ki-outline ki-message-text-2"></i>
            <div class="appointments-alert-content">
                <h4>Tienes <?php echo $counts['mensajes_sin_leer']; ?> mensaje(s) sin leer</h4>
                <p>Revisa los chats de tus citas activas.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-card-warning kpi-clickable" onclick="filterByStatus('solicitado')" id="kpi-solicitado">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-time"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Pendientes</div>
                            <div class="kpi-value"><?php echo $counts['solicitado']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-card-info kpi-clickable" onclick="filterByStatus('agendado')" id="kpi-agendado">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-calendar-tick"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Confirmadas</div>
                            <div class="kpi-value"><?php echo $counts['agendado']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-card-success kpi-clickable" onclick="filterByStatus('finalizado')" id="kpi-finalizado">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-check-circle"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Finalizadas</div>
                            <div class="kpi-value"><?php echo $counts['finalizado']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-card-primary kpi-clickable" onclick="filterByStatus('')" id="kpi-total">
                    <div class="kpi-card-content">
                        <div class="kpi-icon"><i class="ki-outline ki-calendar"></i></div>
                        <div class="kpi-info">
                            <div class="kpi-label">Total</div>
                            <div class="kpi-value"><?php echo $counts['total']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card principal -->
        <div class="card dashboard-tabs-card">
            
            <div class="list-controls">
                <div class="results-info">
                    <span id="apt-results-count">Cargando...</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="pagination-size">
                        <label for="apt-filter-status">Estado:</label>
                        <select id="apt-filter-status" class="form-select form-select-sm">
                            <option value="activas" selected>Activas</option>
                            <option value="solicitado">Pendientes</option>
                            <option value="agendado">Confirmadas</option>
                            <option value="finalizado">Finalizadas</option>
                            <option value="cancelado">Canceladas</option>
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modal_create_appointment">
                        <i class="ki-outline ki-plus me-1"></i>NUEVA CITA
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="tab-list-container" id="apt-list">
                    <div class="loading-state">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Cargando citas...</span>
                    </div>
                </div>
            </div>
            
            <div class="pagination-container" id="apt-pagination" style="display: none;">
                <div class="pagination-info" id="apt-page-info">Página 1 de 1</div>
                <div class="pagination-nav">
                    <button class="btn-pagination" id="apt-prev" disabled>
                        <i class="ki-outline ki-left"></i>
                    </button>
                    <span class="pagination-current" id="apt-page-current">1 / 1</span>
                    <button class="btn-pagination" id="apt-next" disabled>
                        <i class="ki-outline ki-right"></i>
                    </button>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- Modal Crear Cita -->
<div class="modal fade" id="modal_create_appointment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-calendar-add"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Nueva Cita</h5>
                        <p class="text-muted fs-7 mb-0">Propón una fecha y el cliente la confirmará</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_create_appointment">
                <div class="modal-body pt-4">
                    
                    <div class="info-box mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">¿Cómo funciona?</span>
                            <span class="info-box-text">Propones una fecha y el cliente la confirmará o chateará para negociar.</span>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Selecciona un cliente...</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' ' . $c['lastname'] . ' (' . $c['email'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Cita <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <option value="llamada">Llamada</option>
                                <option value="reunion_virtual">Videollamada</option>
                                <option value="reunion_presencial">Presencial</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Departamento <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <option value="contabilidad">Contabilidad</option>
                                <option value="fiscalidad">Fiscalidad</option>
                                <option value="laboral">Laboral</option>
                                <option value="gestion">Gestión</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha y Hora Propuesta <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="proposed_date" id="create_proposed_date" class="form-control" required>
                            <div class="form-text">El cliente deberá confirmar esta fecha.</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Motivo de la cita..."></textarea>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Notas internas (solo asesoría)</label>
                        <textarea name="notes_advisory" class="form-control" rows="2" placeholder="Notas internas..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-check me-1"></i>Crear Cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reprogramar Cita -->
<div class="modal fade" id="modal_reschedule_appointment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper modal-icon-warning">
                        <i class="ki-outline ki-calendar-edit"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Reprogramar Cita</h5>
                        <p class="text-muted fs-7 mb-0">Propón una nueva fecha</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_reschedule_appointment">
                <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
                <input type="hidden" name="action" value="reschedule">
                <div class="modal-body pt-4">
                    
                    <div class="info-box info-box-warning mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">Nueva fecha propuesta</span>
                            <span class="info-box-text">El cliente recibirá la nueva propuesta y deberá confirmarla.</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Nueva Fecha y Hora <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="proposed_date" id="reschedule_proposed_date" class="form-control" required>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ki-outline ki-calendar-edit me-1"></i>Proponer Fecha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cancelar Cita -->
<div class="modal fade" id="modal_cancel_appointment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper modal-icon-danger">
                        <i class="ki-outline ki-cross-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Cancelar Cita</h5>
                        <p class="text-muted fs-7 mb-0">Esta acción notificará al cliente</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_cancel_appointment">
                <input type="hidden" name="appointment_id" id="cancel_appointment_id">
                <input type="hidden" name="action" value="cancel">
                <div class="modal-body pt-4">
                    
                    <div class="info-box info-box-danger mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-text">Esta acción cancelará la cita y notificará al cliente.</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Motivo de cancelación (opcional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Indica el motivo de la cancelación..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ki-outline ki-cross me-1"></i>Cancelar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var API_URL = '/api-advisory-appointments-paginated';
    
    var typeLabels = <?php echo json_encode($typeLabels); ?>;
    var deptLabels = <?php echo json_encode($deptLabels); ?>;
    var statusLabels = <?php echo json_encode($statusLabels); ?>;
    
    var state = {
        currentPage: 1,
        pageSize: 20,
        status: 'activas', // Por defecto mostrar solo activas (no finalizadas ni canceladas)
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    var listContainer = document.getElementById('apt-list');
    var resultsCount = document.getElementById('apt-results-count');
    var pageInfo = document.getElementById('apt-page-info');
    var pageCurrent = document.getElementById('apt-page-current');
    var prevBtn = document.getElementById('apt-prev');
    var nextBtn = document.getElementById('apt-next');
    var statusFilter = document.getElementById('apt-filter-status');
    var paginationContainer = document.getElementById('apt-pagination');
    
    // Fecha mínima para crear/reprogramar: ahora mismo (permite mismo día con hora futura)
    var now = new Date();
    // Redondear al próximo intervalo de 15 minutos
    now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
    var minDate = now.toISOString().slice(0, 16);

    var createDateInput = document.getElementById('create_proposed_date');
    var rescheduleDateInput = document.getElementById('reschedule_proposed_date');
    if (createDateInput) createDateInput.min = minDate;
    if (rescheduleDateInput) rescheduleDateInput.min = minDate;
    
    function init() {
        if (!listContainer) return;
        
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        statusFilter.addEventListener('change', function(e) {
            state.status = e.target.value;
            state.currentPage = 1;
            loadData();
            updateKpiActive(e.target.value);
        });
        
        loadData();
    }
    
    function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        var params = new URLSearchParams({
            page: state.currentPage,
            limit: state.pageSize,
            status: state.status
        });
        
        fetch(API_URL + '?' + params)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    var pagination = result.data.pagination;
                    state.totalPages = pagination.total_pages;
                    state.totalRecords = pagination.total_records;
                    renderList(result.data.appointments || []);
                    updateResultsCount(pagination);
                    updatePaginationControls();
                } else {
                    showError(result.message || 'Error al cargar datos');
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                showError('Error de conexión');
            })
            .finally(function() { state.isLoading = false; });
    }
    
    function renderList(data) {
        if (!data || data.length === 0) {
            var hasFilters = state.status;
            listContainer.innerHTML = 
                '<div class="empty-state">' +
                    '<div class="empty-state-icon"><i class="ki-outline ki-calendar"></i></div>' +
                    '<div class="empty-state-title">' + (hasFilters ? 'Sin resultados' : 'No hay citas') + '</div>' +
                    '<p class="empty-state-text">' + (hasFilters ? 'No hay citas con ese estado' : 'Las citas de tus clientes aparecerán aquí') + '</p>' +
                '</div>';
            paginationContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(apt) {
            var needsAction = apt.needs_confirmation_from === 'advisory';
            var unreadMessages = parseInt(apt.unread_messages) || 0;
            
            var statusClassMap = {
                'solicitado': 'warning',
                'agendado': 'info',
                'finalizado': 'success',
                'cancelado': 'danger'
            };
            var statusClass = statusClassMap[apt.status] || 'muted';
            
            var dateToShow = apt.scheduled_date || apt.proposed_date;
            var dateDisplay = dateToShow ? formatDateTime(dateToShow) : 'Sin fecha';
            var isConfirmed = !!apt.scheduled_date;
            var dateLabel = isConfirmed ? '(Confirmada)' : (needsAction ? '(Propuesta cliente)' : '(Tu propuesta)');
            
            var customerName = escapeHtml(apt.customer_name || 'Cliente');
            
            // Badges adicionales
            var extraBadges = '';
            if (needsAction) {
                extraBadges += '<span class="badge-status badge-status-warning badge-blink">¡Revisar!</span>';
            }
            if (unreadMessages > 0) {
                extraBadges += '<span class="badge-status badge-status-info"><i class="ki-outline ki-message-text-2"></i> ' + unreadMessages + '</span>';
            }
            
            // Acciones
            var actions = '<a href="/appointment?id=' + apt.id + '" class="btn-icon btn-icon-info" title="Ver/Editar"><i class="ki-outline ki-eye"></i></a>';
            
            if (needsAction && apt.proposed_date) {
                actions += '<button class="btn-icon btn-icon-success" onclick="acceptProposal(' + apt.id + ')" title="Aceptar propuesta"><i class="ki-outline ki-check"></i></button>';
            }
            
            if (apt.status === 'solicitado' || apt.status === 'agendado') {
                actions += '<button class="btn-icon btn-icon-warning" onclick="openRescheduleModal(' + apt.id + ')" title="Reprogramar"><i class="ki-outline ki-calendar-edit"></i></button>';
            }
            
            if (apt.status === 'agendado') {
                actions += '<button class="btn-icon btn-icon-success" onclick="finalizeAppointment(' + apt.id + ')" title="Finalizar"><i class="ki-outline ki-check-circle"></i></button>';
            }
            
            if (apt.status === 'solicitado' || apt.status === 'agendado') {
                actions += '<button class="btn-icon btn-icon-danger" onclick="openCancelModal(' + apt.id + ')" title="Cancelar"><i class="ki-outline ki-cross-circle"></i></button>';
            }
            
            var cardClass = 'list-card list-card-' + statusClass;
            if (needsAction) cardClass += ' list-card-highlight';
            
            html += '<div class="' + cardClass + '">' +
                '<div class="list-card-content">' +
                    '<div class="list-card-title">' +
                        '<span class="badge-status badge-status-neutral">#' + apt.id + '</span>' +
                        '<a href="/appointment?id=' + apt.id + '" class="list-card-customer">Cita</a>' +
                        '<span class="badge-status badge-status-' + statusClass + '">' + (statusLabels[apt.status] || apt.status) + '</span>' +
                        extraBadges +
                        '<span class="badge-status badge-status-muted">' + (typeLabels[apt.type] || apt.type) + '</span>' +
                    '</div>' +
                    '<div class="list-card-meta">' +
                        '<span><i class="ki-outline ki-profile-user"></i> <a href="/customer?id=' + apt.customer_id + '" class="text-muted">' + customerName + '</a></span>' +
                        '<span><i class="ki-outline ki-briefcase"></i> ' + (deptLabels[apt.department] || apt.department) + '</span>' +
                        '<span class="' + (isConfirmed ? 'text-info fw-semibold' : 'text-warning') + '">' +
                            '<i class="ki-outline ki-calendar"></i> ' + dateDisplay + ' ' + dateLabel +
                        '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="list-card-actions">' + actions + '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
        listContainer.scrollTop = 0;
    }
    
    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        var options1 = { day: 'numeric', month: 'short' };
        var options2 = { hour: '2-digit', minute: '2-digit' };
        return d.toLocaleDateString('es-ES', options1) + ' ' + d.toLocaleTimeString('es-ES', options2);
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        pageCurrent.textContent = state.currentPage + ' / ' + state.totalPages;
        pageInfo.textContent = 'Página ' + state.currentPage + ' de ' + state.totalPages;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
    }
    
    function updateResultsCount(pagination) {
        resultsCount.innerHTML = pagination.total_records === 0 
            ? 'No hay resultados' 
            : 'Mostrando <strong>' + pagination.from + '-' + pagination.to + '</strong> de <strong>' + pagination.total_records + '</strong>';
    }
    
    function updateKpiActive(status) {
        document.querySelectorAll('.kpi-clickable').forEach(function(el) { el.classList.remove('active'); });
        var id = status ? 'kpi-' + status : 'kpi-total';
        var el = document.getElementById(id);
        if (el) el.classList.add('active');
    }
    
    function showLoading() {
        listContainer.innerHTML = 
            '<div class="loading-state">' +
                '<div class="spinner-border spinner-border-sm text-primary"></div>' +
                '<span class="ms-2">Cargando citas...</span>' +
            '</div>';
    }
    
    function showError(msg) {
        listContainer.innerHTML = 
            '<div class="empty-state">' +
                '<div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>' +
                '<div class="empty-state-title">Error al cargar</div>' +
                '<p class="empty-state-text">' + escapeHtml(msg) + '</p>' +
                '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadAppointments()">' +
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
    
    // Funciones globales
    window.reloadAppointments = function() { loadData(); };
    
    window.filterByStatus = function(status) {
        statusFilter.value = status;
        state.status = status;
        state.currentPage = 1;
        loadData();
        updateKpiActive(status);
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// ============================================
// MODALES Y ACCIONES
// ============================================

// Crear cita
document.getElementById('form_create_appointment')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';
    
fetch('/api/advisory-create-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Cita creada', text: 'El cliente recibirá la propuesta', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            bootstrap.Modal.getInstance(document.getElementById('modal_create_appointment')).hide();
            document.getElementById('form_create_appointment').reset();
            window.reloadAppointments();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al crear cita' });
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); })
    .finally(function() { btn.disabled = false; btn.innerHTML = originalText; });
});

// Aceptar propuesta del cliente
window.acceptProposal = function(appointmentId) {
    Swal.fire({
        title: '¿Aceptar propuesta?',
        text: 'Se confirmará la fecha propuesta por el cliente',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, aceptar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('appointment_id', appointmentId);
            fd.append('action', 'accept_proposal');
            
            fetch('/api/advisory-update-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Confirmada', text: 'La cita ha sido agendada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo confirmar' });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); });
        }
    });
};

// Reprogramar cita
window.openRescheduleModal = function(appointmentId) {
    document.getElementById('reschedule_appointment_id').value = appointmentId;
    new bootstrap.Modal(document.getElementById('modal_reschedule_appointment')).show();
};

document.getElementById('form_reschedule_appointment')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
    fetch('/api/advisory-update-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Propuesta enviada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            bootstrap.Modal.getInstance(document.getElementById('modal_reschedule_appointment')).hide();
            window.reloadAppointments();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al reprogramar' });
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); })
    .finally(function() { btn.disabled = false; btn.innerHTML = originalText; });
});

// Finalizar cita
window.finalizeAppointment = function(appointmentId) {
    Swal.fire({
        title: '¿Finalizar cita?',
        text: 'Se marcará la cita como completada',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('appointment_id', appointmentId);
            fd.append('action', 'finalize');
            
            fetch('/api/advisory-update-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Finalizada', text: 'La cita ha sido completada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo finalizar' });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); });
        }
    });
};

// Cancelar cita
window.openCancelModal = function(appointmentId) {
    document.getElementById('cancel_appointment_id').value = appointmentId;
    new bootstrap.Modal(document.getElementById('modal_cancel_appointment')).show();
};

document.getElementById('form_cancel_appointment')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelando...';
    
    fetch('/api/advisory-update-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Cita cancelada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            bootstrap.Modal.getInstance(document.getElementById('modal_cancel_appointment')).hide();
            window.reloadAppointments();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al cancelar' });
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); })
    .finally(function() { btn.disabled = false; btn.innerHTML = originalText; });
});
</script>