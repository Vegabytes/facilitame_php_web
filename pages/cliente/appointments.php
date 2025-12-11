<?php
$currentPage = 'appointments';

$stmt = $pdo->prepare("SELECT advisory_id FROM customers_advisories WHERE customer_id = ?");
$stmt->execute([USER['id']]);
$customer_advisory = $stmt->fetch();
$customer_advisory_id = $customer_advisory ? $customer_advisory['advisory_id'] : null;

$counts = ['solicitado' => 0, 'agendado' => 0, 'finalizado' => 0, 'cancelado' => 0, 'total' => 0, 'pendiente_confirmacion' => 0];
if ($customer_advisory_id) {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM advisory_appointments WHERE customer_id = ? GROUP BY status");
    $stmt->execute([USER['id']]);
    while ($row = $stmt->fetch()) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int)$row['count'];
        }
        $counts['total'] += (int)$row['count'];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM advisory_appointments WHERE customer_id = ? AND needs_confirmation_from = 'customer'");
    $stmt->execute([USER['id']]);
    $row = $stmt->fetch();
    $counts['pendiente_confirmacion'] = (int)($row['count'] ?? 0);
}
?>

<div id="facilita-app">
    
    <?php if (!$customer_advisory_id): ?>
    <div class="customers-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
        <div class="card" style="flex: 1; display: flex; align-items: center; justify-content: center;">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-information-2"></i>
                </div>
                <div class="empty-state-title">No tienes una asesoria asignada</div>
                <p class="empty-state-text">Para solicitar citas, primero debes tener una asesoria vinculada</p>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <div class="dashboard-cliente-home">
        
        <?php if ($counts['pendiente_confirmacion'] > 0): ?>
        <div class="appointments-alert">
            <i class="ki-outline ki-notification-bing appointments-alert-icon"></i>
            <div class="appointments-alert-content">
                <h4 class="appointments-alert-title">Tienes <?php echo $counts['pendiente_confirmacion']; ?> cita(s) pendiente(s) de confirmar</h4>
                <p style="margin: 0; font-size: 0.8125rem; color: var(--f-text-medium);">Tu asesoria ha propuesto una fecha. Revisala y confirma, o escribeles por el chat.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="kpi-card kpi-card-warning">
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
                <div class="kpi-card kpi-card-info">
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
                <div class="kpi-card kpi-card-success">
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
                <div class="kpi-card kpi-card-primary">
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
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modal_request_appointment">
                        <i class="ki-outline ki-plus fs-4 me-1"></i>Solicitar Cita
                    </button>
                </div>
            </div>
            
            <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                <div class="tab-list-container" id="apt-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                    <div class="loading-state">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Cargando citas...</span>
                    </div>
                </div>
            </div>
            
            <div class="pagination-container" id="apt-pagination" style="display: none;">
                <div class="pagination-info" id="apt-page-info">Pagina 1 de 1</div>
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
    
    <?php endif; ?>
    
</div>

<?php if ($customer_advisory_id): ?>
<!-- Modal Solicitar Cita - Diseno Mejorado -->
<div class="modal fade" id="modal_request_appointment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper">
                        <i class="ki-outline ki-calendar-add"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Solicitar Cita</h5>
                        <p class="text-muted fs-7 mb-0">Propon una fecha y tu asesoria la confirmara</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_request_appointment">
                <input type="hidden" name="advisory_id" value="<?php echo $customer_advisory_id; ?>">
                <div class="modal-body pt-4">
                    
                    <!-- Info box -->
                    <div class="info-box mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">Como funciona?</span>
                            <span class="info-box-text">Propon una fecha y hora. La asesoria confirmara o te propondra otra alternativa.</span>
                        </div>
                    </div>
                    
                    <!-- Tipo de cita -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Tipo de Cita <span class="text-danger">*</span></label>
                        <div class="type-selector type-selector-3" id="type-selector">
                            <label class="type-option type-llamada">
                                <input type="radio" name="type" value="llamada">
                                <span class="type-content">
                                    <i class="ki-outline ki-phone"></i>
                                    <span>Llamada</span>
                                </span>
                            </label>
                            <label class="type-option type-virtual">
                                <input type="radio" name="type" value="reunion_virtual">
                                <span class="type-content">
                                    <i class="ki-outline ki-screen"></i>
                                    <span>Videollamada</span>
                                </span>
                            </label>
                            <label class="type-option type-presencial">
                                <input type="radio" name="type" value="reunion_presencial">
                                <span class="type-content">
                                    <i class="ki-outline ki-home-2"></i>
                                    <span>Presencial</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Departamento y Fecha -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Departamento <span class="text-danger">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="ki-outline ki-briefcase input-icon"></i>
                                <select name="department" class="form-select form-select-icon" required>
                                    <option value="">Selecciona...</option>
                                    <option value="contabilidad">Contabilidad</option>
                                    <option value="fiscalidad">Fiscalidad</option>
                                    <option value="laboral">Laboral</option>
                                    <option value="gestion">Gestion</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha y Hora <span class="text-danger">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="ki-outline ki-calendar input-icon"></i>
                                <input type="datetime-local" name="proposed_date" id="proposed_date_input" class="form-control form-control-icon" required>
                            </div>
                            <div class="form-text">Selecciona cuando te viene mejor</div>
                        </div>
                    </div>
                    
                    <!-- Motivo -->
                    <div>
                        <label class="form-label fw-semibold">Motivo de la Cita <span class="text-danger">*</span></label>
                        <div class="input-icon-wrapper textarea-wrapper">
                            <i class="ki-outline ki-message-text input-icon"></i>
                            <textarea name="reason" class="form-control form-control-icon" rows="3" required placeholder="Describe brevemente el motivo de tu cita..."></textarea>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-send me-1"></i>
                        Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Solicitar Cambio -->
<div class="modal fade" id="modal_request_change" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-wrapper modal-icon-warning">
                        <i class="ki-outline ki-calendar-edit"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Solicitar Cambio</h5>
                        <p class="text-muted fs-7 mb-0">Indica que fechas te vendrian mejor</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_request_change">
                <input type="hidden" name="appointment_id" id="change_appointment_id">
                <div class="modal-body pt-4">
                    
                    <div class="info-box info-box-warning mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">No te viene bien?</span>
                            <span class="info-box-text">Escribe indicando que fechas te vendrian mejor.</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-semibold">Tu mensaje <span class="text-danger">*</span></label>
                        <textarea name="message" id="change_message" class="form-control" rows="4" required placeholder="Ej: Me gustaria cambiar la cita al jueves por la tarde..."></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ki-outline ki-send me-1"></i>
                        Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Icon */
.modal-icon-wrapper {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--f-primary) 0%, #00a8b0 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.modal-icon-wrapper i {
    font-size: 1.5rem;
    color: white;
}

.modal-icon-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

/* Info Box */
.info-box {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    background: rgba(0, 194, 203, 0.08);
    border: 1px solid rgba(0, 194, 203, 0.2);
    border-radius: 12px;
}

.info-box-warning {
    background: rgba(245, 158, 11, 0.08);
    border-color: rgba(245, 158, 11, 0.2);
}

.info-box-icon {
    width: 36px;
    height: 36px;
    background: rgba(0, 194, 203, 0.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-box-icon i {
    font-size: 1.125rem;
    color: var(--f-primary);
}

.info-box-warning .info-box-icon {
    background: rgba(245, 158, 11, 0.15);
}

.info-box-warning .info-box-icon i {
    color: #f59e0b;
}

.info-box-content {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.info-box-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--f-text-dark);
}

.info-box-text {
    font-size: 0.8125rem;
    color: var(--f-text-medium);
}

/* Type Selector */
.type-selector {
    display: flex;
    gap: 0.75rem;
}

.type-selector-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.type-option {
    cursor: pointer;
}

.type-option input {
    display: none;
}

.type-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 12px;
    border: 2px solid var(--f-border);
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    background: white;
    text-align: center;
}

.type-content i {
    font-size: 1.5rem;
    color: var(--f-text-light);
    transition: all 0.2s ease;
}

.type-option:hover .type-content {
    border-color: #d1d5db;
    background: var(--f-bg-light);
}

/* Tipo Llamada */
.type-llamada input:checked + .type-content {
    border-color: var(--f-primary);
    background: rgba(0, 194, 203, 0.08);
    color: var(--f-primary);
}

.type-llamada input:checked + .type-content i {
    color: var(--f-primary);
}

/* Tipo Virtual */
.type-virtual input:checked + .type-content {
    border-color: #8b5cf6;
    background: rgba(139, 92, 246, 0.08);
    color: #7c3aed;
}

.type-virtual input:checked + .type-content i {
    color: #8b5cf6;
}

/* Tipo Presencial */
.type-presencial input:checked + .type-content {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.08);
    color: #d97706;
}

.type-presencial input:checked + .type-content i {
    color: #f59e0b;
}

/* Input with icon */
.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.125rem;
    color: var(--f-text-light);
    z-index: 1;
    pointer-events: none;
}

.textarea-wrapper .input-icon {
    top: 1rem;
    transform: none;
}

.form-control-icon,
.form-select-icon {
    padding-left: 2.75rem;
}

.form-select-icon {
    background-position: right 0.75rem center;
}

/* Modal footer */
#modal_request_appointment .modal-footer,
#modal_request_change .modal-footer {
    padding: 1rem 1.5rem 1.5rem;
}

#modal_request_appointment .btn-primary {
    background: var(--f-primary);
    border-color: var(--f-primary);
    padding: 0.625rem 1.5rem;
}

#modal_request_appointment .btn-primary:hover {
    background: var(--f-primary-dark);
    border-color: var(--f-primary-dark);
}

#modal_request_change .btn-warning {
    background: #f59e0b;
    border-color: #f59e0b;
    color: white;
    padding: 0.625rem 1.5rem;
}

#modal_request_change .btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
    color: white;
}

/* Responsive */
@media (max-width: 576px) {
    .type-selector-3 {
        grid-template-columns: 1fr;
    }
    
    .type-content {
        flex-direction: row;
        padding: 0.875rem 1rem;
    }
    
    .type-content i {
        font-size: 1.25rem;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    var API_URL = '/api-customer-appointments-paginated';
    
    var typeLabels = {'llamada': 'Llamada', 'reunion_virtual': 'Videollamada', 'reunion_presencial': 'Presencial'};
    var deptLabels = {'contabilidad': 'Contabilidad', 'fiscalidad': 'Fiscalidad', 'laboral': 'Laboral', 'gestion': 'Gestion'};
    var statusLabels = {'solicitado': 'Pendiente', 'agendado': 'Confirmada', 'finalizado': 'Finalizada', 'cancelado': 'Cancelada'};
    
    var state = {
        currentPage: 1,
        pageSize: 15,
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
    
    // Configurar fecha mínima: ahora mismo (permite mismo día con hora futura)
    var now = new Date();
    // Redondear al próximo intervalo de 15 minutos
    now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
    var proposedInput = document.getElementById('proposed_date_input');
    if (proposedInput) proposedInput.min = now.toISOString().slice(0, 16);
    
    function init() {
        if (!listContainer) return;
        
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        if (statusFilter) {
            statusFilter.addEventListener('change', function(e) { 
                state.status = e.target.value; 
                state.currentPage = 1; 
                loadData(); 
            });
        }
        
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
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    state.totalPages = result.data.pagination.total_pages;
                    state.totalRecords = result.data.pagination.total_records;
                    
                    renderList(result.data.appointments || []);
                    updateResultsCount(result.data.pagination);
                    updatePaginationControls();
                } else {
                    showError(result.message || 'Error al cargar datos');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                showError('Error de conexion');
            })
            .finally(function() {
                state.isLoading = false;
            });
    }
    
    function renderList(data) {
        if (!data.length) {
            var emptyHtml = '<div class="empty-state">' +
                '<div class="empty-state-icon"><i class="ki-outline ki-calendar"></i></div>' +
                '<div class="empty-state-title">' + (state.status ? 'Sin resultados' : 'No tienes citas') + '</div>' +
                '<p class="empty-state-text">' + (state.status ? 'No hay citas con ese estado' : 'Solicita tu primera cita con la asesoria') + '</p>';
            
            if (!state.status) {
                emptyHtml += '<button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modal_request_appointment">' +
                    '<i class="ki-outline ki-plus me-1"></i>Solicitar Cita</button>';
            }
            emptyHtml += '</div>';
            
            listContainer.innerHTML = emptyHtml;
            paginationContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(apt) {
            var needsAction = apt.needs_confirmation_from === 'customer';
            var statusClassMap = {'solicitado': 'warning', 'agendado': 'info', 'finalizado': 'success', 'cancelado': 'danger'};
            var statusClass = statusClassMap[apt.status] || 'muted';
            var dateToShow = apt.scheduled_date || apt.proposed_date;
            var dateDisplay = dateToShow ? formatDateTime(dateToShow) : 'Sin fecha';
            var isConfirmed = !!apt.scheduled_date;
            
            var actions = '';
            if (needsAction) {
                actions += '<button class="btn-icon btn-icon-success" onclick="confirmAppointment(' + apt.id + ')" title="Confirmar"><i class="ki-outline ki-check"></i></button>';
            }
            actions += '<a href="/appointment?id=' + apt.id + '" class="btn-icon btn-icon-info" title="Ver detalle"><i class="ki-outline ki-eye"></i></a>';
            if (apt.status === 'solicitado' || apt.status === 'agendado') {
                actions += '<button class="btn-icon btn-icon-warning" onclick="openChangeModal(' + apt.id + ')" title="Solicitar cambio"><i class="ki-outline ki-pencil"></i></button>';
            }
            
            var cardClasses = 'list-card list-card-' + statusClass + (needsAction ? ' needs-confirmation' : '');
            
            html += '<div class="' + cardClasses + '">' +
                '<div class="list-card-content">' +
                    '<div class="list-card-title">' +
                        '<a href="/appointment?id=' + apt.id + '" class="list-card-customer">#' + apt.id + '</a>' +
                        '<span class="badge-status badge-status-' + statusClass + '">' + (statusLabels[apt.status] || apt.status) + '</span>' +
                        (needsAction ? '<span class="badge-status badge-status-warning badge-confirm-action">Confirmar!</span>' : '') +
                        '<span class="badge-status badge-status-info">' + (typeLabels[apt.type] || apt.type) + '</span>' +
                    '</div>' +
                    '<div class="list-card-meta">' +
                        '<span><i class="ki-outline ki-briefcase"></i> ' + (deptLabels[apt.department] || apt.department) + '</span>' +
                        '<span class="' + (isConfirmed ? 'text-info fw-semibold' : 'text-warning') + '">' +
                            '<i class="ki-outline ki-calendar"></i> ' + dateDisplay +
                            (isConfirmed ? ' (Confirmada)' : (needsAction ? ' (Propuesta)' : ' (Tu propuesta)')) +
                        '</span>' +
                        '<span><i class="ki-outline ki-home-2"></i> ' + escapeHtml(apt.advisory_name || 'Asesoria') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="list-card-actions">' + actions + '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;
    }
    
    function formatDateTime(d) {
        if (!d) return '-';
        var dt = new Date(d);
        var options1 = {day: 'numeric', month: 'short'};
        var options2 = {hour: '2-digit', minute: '2-digit'};
        return dt.toLocaleDateString('es-ES', options1) + ' ' + dt.toLocaleTimeString('es-ES', options2);
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function updatePaginationControls() {
        pageCurrent.textContent = state.currentPage + ' / ' + state.totalPages;
        pageInfo.textContent = 'Pagina ' + state.currentPage + ' de ' + state.totalPages;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }
    
    function updateResultsCount(pagination) {
        if (pagination.total_records === 0) {
            resultsCount.innerHTML = 'No hay resultados';
        } else {
            resultsCount.innerHTML = 'Mostrando <strong>' + pagination.from + '-' + pagination.to + '</strong> de <strong>' + pagination.total_records + '</strong>';
        }
    }
    
    function showLoading() {
        listContainer.innerHTML = '<div class="loading-state">' +
            '<div class="spinner-border spinner-border-sm text-primary"></div>' +
            '<span class="ms-2">Cargando citas...</span></div>';
    }
    
    function showError(message) {
        listContainer.innerHTML = '<div class="empty-state">' +
            '<div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>' +
            '<div class="empty-state-title">Error al cargar</div>' +
            '<p class="empty-state-text">' + escapeHtml(message) + '</p>' +
            '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadAppointments()">' +
            '<i class="ki-outline ki-arrows-circle me-1"></i>Reintentar</button></div>';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.reloadAppointments = function() { loadData(); };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// Formulario solicitar cita
(function() {
    var form = document.getElementById('form_request_appointment');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validar que se haya seleccionado tipo de cita
            var typeSelected = form.querySelector('input[name="type"]:checked');
            if (!typeSelected) {
                FH.warning('Selecciona un tipo de cita');
                return;
            }

            var btn = form.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

            fetch('/api/customer-request-appointment', {method: 'POST', body: new FormData(form)})
                .then(function(response) { return response.json(); })
                .then(function(result) {
                    if (result.status === 'ok') {
                        bootstrap.Modal.getInstance(document.getElementById('modal_request_appointment')).hide();
                        form.reset();
                        window.reloadAppointments();
                        FH.success('Tu asesoría revisará la fecha propuesta', 'Solicitud enviada');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        throw new Error(result.message || 'Error');
                    }
                })
                .catch(function(error) {
                    FH.error(error.message);
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }
})();

// Confirmar cita
window.confirmAppointment = function(id) {
    if (!confirm('Confirmar esta cita con la fecha propuesta?')) return;

    var fd = new FormData();
    fd.append('appointment_id', id);

    fetch('/api/customer-confirm-appointment', {method: 'POST', body: fd})
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.status === 'ok') {
                FH.success('Cita confirmada!');
                window.reloadAppointments();
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                throw new Error(result.message || 'Error');
            }
        })
        .catch(function(error) {
            FH.error(error.message);
        });
};

// Abrir modal de cambio
window.openChangeModal = function(id) {
    document.getElementById('change_appointment_id').value = id;
    document.getElementById('change_message').value = '';
    new bootstrap.Modal(document.getElementById('modal_request_change')).show();
};

// Formulario solicitar cambio
(function() {
    var form = document.getElementById('form_request_change');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
            
            var appointmentId = document.getElementById('change_appointment_id').value;
            
            fetch('/api/customer-appointment-chat-send', {method: 'POST', body: new FormData(form)})
                .then(function(response) { return response.json(); })
                .then(function(result) {
                    if (result.status === 'ok') {
                        bootstrap.Modal.getInstance(document.getElementById('modal_request_change')).hide();
                        FH.success('Solicitud enviada');
                        setTimeout(function() { window.location.href = '/appointment?id=' + appointmentId; }, 1000);
                    } else {
                        throw new Error(result.message || 'Error');
                    }
                })
                .catch(function(error) {
                    FH.error(error.message);
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }
})();
</script>
<?php endif; ?>