<?php
/**
 * Detalle de Cita - Cliente
 * Usa clases de dashboard-common.css
 */
$currentPage = 'appointments';

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    echo '<div class="customers-page"><div class="card"><div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-information-2 text-danger"></i></div><div class="empty-state-title">ID de cita no válido</div></div></div></div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT 
        a.*,
        adv.razon_social as advisory_name,
        u_adv.email as advisory_email,
        u_adv.phone as advisory_phone
    FROM advisory_appointments a
    INNER JOIN advisories adv ON adv.id = a.advisory_id
    INNER JOIN users u_adv ON u_adv.id = adv.user_id
    WHERE a.id = ? AND a.customer_id = ?
");
$stmt->execute([$appointment_id, USER['id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    echo '<div class="customers-page"><div class="card"><div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-lock text-danger"></i></div><div class="empty-state-title">Cita no encontrada</div><p class="empty-state-text">No tienes acceso a esta cita</p></div></div></div>';
    return;
}

if (function_exists('mark_appointment_messages_read')) {
    mark_appointment_messages_read($appointment_id, 'customer');
}

$typeLabels = ['llamada' => 'Llamada', 'reunion_virtual' => 'Videollamada', 'reunion_presencial' => 'Presencial'];
$typeIcons = ['llamada' => 'phone', 'reunion_virtual' => 'screen', 'reunion_presencial' => 'home-2'];
$deptLabels = ['contabilidad' => 'Contabilidad', 'fiscalidad' => 'Fiscalidad', 'laboral' => 'Laboral', 'gestion' => 'Gestión'];
$statusLabels = ['solicitado' => 'Pendiente', 'agendado' => 'Confirmada', 'finalizado' => 'Finalizada', 'cancelado' => 'Cancelada'];
$statusClasses = ['solicitado' => 'warning', 'agendado' => 'info', 'finalizado' => 'success', 'cancelado' => 'danger'];

$needsConfirmation = ($appointment['needs_confirmation_from'] === 'customer');
$hasScheduledDate = !empty($appointment['scheduled_date']);
$hasProposedDate = !empty($appointment['proposed_date']);
$proposedBy = $appointment['proposed_by'] ?? null;
$statusClass = $statusClasses[$appointment['status']] ?? 'muted';
?>

<div id="facilita-app">
    <div class="appointment-detail-page">
        
        <!-- Botón volver -->
        <div class="mb-3">
            <a href="/appointments" class="btn btn-light btn-sm">
                <i class="ki-outline ki-arrow-left me-1"></i> Volver a Mis Citas
            </a>
        </div>
        
        <div class="row g-3">
            
            <!-- Columna Izquierda: Detalles -->
            <div class="col-xl-6">
                <div class="card appointment-detail-card">
                    
                    <!-- Header con gradiente -->
                    <div class="card-header appointment-detail-header">
                        <div class="d-flex align-items-center gap-3 w-100">
                            <div class="appointment-header-icon">
                                <i class="ki-outline ki-calendar"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="appointment-header-title">Cita #<?php echo $appointment['id']; ?></h5>
                                <div class="appointment-header-subtitle">
                                    <i class="ki-outline ki-<?php echo $typeIcons[$appointment['type']] ?? 'calendar'; ?>"></i>
                                    <?php echo $typeLabels[$appointment['type']] ?? $appointment['type']; ?>
                                </div>
                            </div>
                            <span class="badge-status badge-status-<?php echo $statusClass; ?>">
                                <?php echo $statusLabels[$appointment['status']] ?? $appointment['status']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        
                        <?php if ($needsConfirmation && $hasProposedDate): ?>
                        <!-- Alerta: Necesita confirmación -->
                        <div class="list-card list-card-warning needs-confirmation mb-3">
                            <div class="list-card-content">
                                <div class="list-card-title">
                                    <i class="ki-outline ki-notification-bing text-warning"></i>
                                    <span>Tu asesoría te ha propuesto una fecha</span>
                                </div>
                                <div class="list-card-meta">
                                    <strong class="text-dark"><?php echo !empty($appointment['proposed_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['proposed_date'])) : '-'; ?></strong>
                                </div>
                                <button class="btn btn-success btn-sm mt-2" onclick="confirmAppointment()">
                                    <i class="ki-outline ki-check me-1"></i> Confirmar esta fecha
                                </button>
                            </div>
                        </div>
                        
                        <?php elseif ($hasScheduledDate): ?>
                        <div class="list-card list-card-success mb-3">
                            <div class="list-card-content">
                                <div class="list-card-title">
                                    <i class="ki-outline ki-calendar-tick text-success"></i>
                                    <span>Cita Confirmada</span>
                                </div>
                                <div class="list-card-meta">
                                    <strong class="text-dark"><?php echo !empty($appointment['scheduled_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['scheduled_date'])) : '-'; ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <?php elseif ($hasProposedDate && $proposedBy === 'customer'): ?>
                        <div class="list-card list-card-warning mb-3">
                            <div class="list-card-content">
                                <div class="list-card-title">
                                    <i class="ki-outline ki-time text-warning"></i>
                                    <span>Esperando confirmación</span>
                                </div>
                                <div class="list-card-meta">
                                    Propusiste: <strong><?php echo !empty($appointment['proposed_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['proposed_date'])) : '-'; ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <?php elseif ($appointment['status'] === 'solicitado'): ?>
                        <div class="list-card list-card-warning mb-3">
                            <div class="list-card-content">
                                <div class="list-card-title">
                                    <i class="ki-outline ki-information-2 text-warning"></i>
                                    <span>Pendiente de Agendar</span>
                                </div>
                                <div class="list-card-meta">Tu asesoría revisará tu solicitud</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($appointment['status'] === 'cancelado'): ?>
                        <div class="list-card list-card-danger mb-3">
                            <div class="list-card-content">
                                <div class="list-card-title">
                                    <i class="ki-outline ki-cross-circle text-danger"></i>
                                    <span>Cita Cancelada</span>
                                </div>
                                <div class="list-card-meta">
                                    Por: <?php echo $appointment['cancelled_by'] === 'advisory' ? 'Asesoría' : 'Cliente'; ?>
                                    <?php if (!empty($appointment['cancelled_at'])): ?>
                                        el <?php echo fdatetime($appointment['cancelled_at']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Información de la cita -->
                        <div class="detail-section">
                            <h6 class="detail-section-title">
                                <i class="ki-outline ki-information-2"></i>
                                Información de la Cita
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="detail-box">
                                        <div class="detail-box-label">Departamento</div>
                                        <div class="detail-box-value"><?php echo $deptLabels[$appointment['department']] ?? $appointment['department']; ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="detail-box">
                                        <div class="detail-box-label">Tipo</div>
                                        <div class="detail-box-value"><?php echo $typeLabels[$appointment['type']] ?? $appointment['type']; ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="detail-box">
                                        <div class="detail-box-label">Fecha Solicitud</div>
                                        <div class="detail-box-value"><?php echo fdatetime($appointment['created_at']); ?></div>
                                    </div>
                                </div>
                                <?php if ($hasScheduledDate): ?>
                                <div class="col-6">
                                    <div class="detail-box detail-box-success">
                                        <div class="detail-box-label">Fecha Confirmada</div>
                                        <div class="detail-box-value"><?php echo fdatetime($appointment['scheduled_date']); ?></div>
                                    </div>
                                </div>
                                <?php elseif ($hasProposedDate): ?>
                                <div class="col-6">
                                    <div class="detail-box detail-box-warning">
                                        <div class="detail-box-label">Fecha Propuesta</div>
                                        <div class="detail-box-value"><?php echo fdatetime($appointment['proposed_date']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($appointment['reason'])): ?>
                                <div class="col-12">
                                    <div class="detail-box">
                                        <div class="detail-box-label">Motivo</div>
                                        <div class="detail-box-value"><?php echo nl2br(htmlspecialchars($appointment['reason'])); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Datos de contacto -->
                        <div class="detail-section">
                            <h6 class="detail-section-title">
                                <i class="ki-outline ki-briefcase"></i>
                                Datos de Contacto
                            </h6>
                            
                            <div class="list-card">
                                <div class="list-card-icon">
                                    <i class="ki-outline ki-profile-circle"></i>
                                </div>
                                <div class="list-card-content">
                                    <div class="list-card-title">
                                        <?php echo htmlspecialchars($appointment['advisory_name']); ?>
                                    </div>
                                    <div class="list-card-meta flex-column align-items-start gap-1">
                                        <a href="mailto:<?php echo htmlspecialchars($appointment['advisory_email']); ?>" class="text-muted">
                                            <i class="ki-outline ki-sms me-1"></i><?php echo htmlspecialchars($appointment['advisory_email']); ?>
                                        </a>
                                        <?php if (!empty($appointment['advisory_phone'])): ?>
                                        <a href="tel:<?php echo $appointment['advisory_phone']; ?>" class="text-muted">
                                            <i class="ki-outline ki-phone me-1"></i><?php echo function_exists('phone') ? phone($appointment['advisory_phone']) : $appointment['advisory_phone']; ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <?php if ($needsConfirmation): ?>
                            <button class="btn btn-success" onclick="confirmAppointment()">
                                <i class="ki-outline ki-check me-1"></i> Confirmar Fecha
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal_request_change">
                                <i class="ki-outline ki-pencil me-1"></i> Solicitar Cambio
                            </button>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
            
            <!-- Columna Derecha: Chat -->
            <div class="col-xl-6">
                <div class="card appointment-chat-card">
                    
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="ki-outline ki-message-text-2"></i>
                            Chat con la Asesoría
                        </h6>
                        <span class="badge-status badge-status-danger" id="unreadBadge" style="display: none;">
                            <span id="unreadCount">0</span> nuevos
                        </span>
                    </div>
                    
                    <div class="appointment-chat-body">
                        <div id="chatMessages" class="appointment-chat-messages">
                            <div class="loading-state">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <span class="ms-2">Cargando mensajes...</span>
                            </div>
                        </div>
                        
                        <div class="appointment-chat-input">
                            <form id="chatForm" onsubmit="sendMessage(event)" class="d-flex gap-2">
                                <input type="text" id="messageInput" class="form-control" placeholder="Escribe tu mensaje..." required autocomplete="off">
                                <button type="submit" class="btn btn-primary" id="sendButton">
                                    <i class="ki-outline ki-send"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
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
                        <p class="text-muted fs-7 mb-0">Indica qué fechas te vendrían mejor</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_request_change">
                <div class="modal-body pt-4">
                    <div class="info-box info-box-warning mb-4">
                        <div class="info-box-icon">
                            <i class="ki-outline ki-information-2"></i>
                        </div>
                        <div class="info-box-content">
                            <span class="info-box-title">¿No te viene bien?</span>
                            <span class="info-box-text">Escribe indicando qué fechas te vendrían mejor.</span>
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Tu mensaje <span class="text-danger">*</span></label>
                        <textarea name="message" id="change_message" class="form-control" rows="4" required placeholder="Ej: Me gustaría cambiar la cita al jueves por la tarde..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ki-outline ki-send me-1"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var APPOINTMENT_ID = <?php echo $appointment_id; ?>;
var chatInterval;
var lastMessageCount = 0;
var isFirstLoad = true;

function loadMessages() {
    fetch('/api/customer-appointment-chat-messages?appointment_id=' + APPOINTMENT_ID)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok') {
                var msgs = data.data.messages || [];
                displayMessages(msgs);
                if (!isFirstLoad && msgs.length > lastMessageCount) {
                    showUnreadBadge(msgs.length - lastMessageCount);
                }
                lastMessageCount = msgs.length;
                isFirstLoad = false;
            }
        })
        .catch(function(err) { console.error('Error:', err); });
}

function displayMessages(msgs) {
    var c = document.getElementById('chatMessages');
    
    if (!msgs || !msgs.length) {
        c.innerHTML = '<div class="empty-state empty-state-compact"><div class="empty-state-icon"><i class="ki-outline ki-message-text-2"></i></div><div class="empty-state-title">No hay mensajes</div><p class="empty-state-text">¡Inicia la conversación!</p></div>';
        return;
    }
    
    var h = '';
    msgs.forEach(function(m) {
        var isCustomer = m.sender_type === 'customer';
        h += '<div class="chat-message ' + (isCustomer ? 'chat-message-right' : 'chat-message-left') + '">' +
            '<div class="chat-message-wrapper">' +
            '<div class="chat-message-sender">' + (isCustomer ? 'Tú' : 'Asesoría') + '</div>' +
            '<div class="chat-message-bubble ' + (isCustomer ? 'chat-bubble-customer' : 'chat-bubble-advisory') + '">' + escapeHtml(m.content || m.message) + '</div>' +
            '<div class="chat-message-time">' + formatTime(m.created_at) + '</div>' +
            '</div></div>';
    });
    
    c.innerHTML = h;
    c.scrollTop = c.scrollHeight;
}

function sendMessage(e) {
    e.preventDefault();
    var inp = document.getElementById('messageInput');
    var btn = document.getElementById('sendButton');
    var msg = inp.value.trim();
    if (!msg) return;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    var fd = new FormData();
    fd.append('appointment_id', APPOINTMENT_ID);
    fd.append('message', msg);
    
    fetch('/api/customer-appointment-chat-send', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'ok') {
                inp.value = '';
                loadMessages();
            } else {
                Swal.fire({icon: 'error', title: 'Error', text: d.message || 'Error al enviar'});
            }
        })
        .catch(function() { Swal.fire({icon: 'error', title: 'Error', text: 'Error de conexión'}); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ki-outline ki-send"></i>';
            inp.focus();
        });
}

function showUnreadBadge(count) {
    var badge = document.getElementById('unreadBadge');
    var countEl = document.getElementById('unreadCount');
    if (count > 0) { countEl.textContent = count; badge.style.display = 'inline-flex'; }
    else { badge.style.display = 'none'; }
}

function formatTime(d) {
    if (!d) return '';
    var dt = new Date(d);
    return dt.toLocaleDateString('es-ES') + ' ' + dt.toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit'});
}

function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

window.confirmAppointment = function() {
    Swal.fire({
        title: '¿Confirmar cita?',
        text: 'Confirmarás la fecha propuesta por la asesoría',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        
        var fd = new FormData();
        fd.append('appointment_id', APPOINTMENT_ID);
        
        fetch('/api/customer-confirm-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.status === 'ok') {
                    Swal.fire({icon: 'success', title: '¡Cita confirmada!', timer: 1500, showConfirmButton: false});
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: d.message || 'Error al confirmar'});
                }
            })
            .catch(function() { Swal.fire({icon: 'error', title: 'Error', text: 'Error de conexión'}); });
    });
};

<?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
document.getElementById('form_request_change').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var orig = btn.innerHTML;
    var message = document.getElementById('change_message').value;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
    var fd = new FormData();
    fd.append('appointment_id', APPOINTMENT_ID);
    fd.append('message', message);
    
    fetch('/api/customer-appointment-chat-send', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'ok') {
                Swal.fire({icon: 'success', title: 'Solicitud enviada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                bootstrap.Modal.getInstance(document.getElementById('modal_request_change')).hide();
                document.getElementById('change_message').value = '';
                loadMessages();
            } else {
                Swal.fire({icon: 'error', title: 'Error', text: d.message || 'Error al enviar'});
            }
        })
        .catch(function() { Swal.fire({icon: 'error', title: 'Error', text: 'Error de conexión'}); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = orig;
        });
});
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    chatInterval = setInterval(loadMessages, 5000);
});

window.addEventListener('beforeunload', function() { if (chatInterval) clearInterval(chatInterval); });
</script>