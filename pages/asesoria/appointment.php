<?php
/**
 * Detalle de Cita - Asesoria v2
 * - Ver propuesta del cliente y aceptarla
 * - Reprogramar (proponer nueva fecha, cliente confirma)
 * - Finalizar, cancelar, reactivar
 */
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    echo '<div class="alert alert-danger m-5">ID de cita no valido.</div>';
    return;
}

// Obtener advisory_id
$stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    echo '<div class="alert alert-danger m-5">No tienes una asesoria configurada.</div>';
    return;
}

// Obtener la cita verificando que pertenece a esta asesoria
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.name as customer_name,
        u.lastname as customer_lastname,
        u.email as customer_email,
        u.phone as customer_phone
    FROM advisory_appointments a
    INNER JOIN users u ON u.id = a.customer_id
    WHERE a.id = ? AND a.advisory_id = ?
");
$stmt->execute([$appointment_id, $advisory['id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    echo '<div class="alert alert-danger m-5">Cita no encontrada o no tienes acceso.</div>';
    return;
}

// CONTAR mensajes no leidos ANTES de marcarlos como leidos
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM advisory_messages 
    WHERE appointment_id = ? AND sender_type = 'customer' AND (is_read = 0 OR is_read IS NULL)
");
$stmt->execute([$appointment_id]);
$unreadCount = (int)$stmt->fetchColumn();

// Marcar mensajes como leidos
$stmt = $pdo->prepare("
    UPDATE advisory_messages 
    SET is_read = 1 
    WHERE appointment_id = ? AND sender_type = 'customer' AND (is_read = 0 OR is_read IS NULL)
");
$stmt->execute([$appointment_id]);

// Obtener historial de cambios
$history = [];
$stmt = $pdo->prepare("
    SELECT h.*, u.name as user_name, u.lastname as user_lastname
    FROM advisory_appointment_history h
    LEFT JOIN users u ON u.id = h.user_id
    WHERE h.appointment_id = ?
    ORDER BY h.created_at DESC
    LIMIT 20
");
$stmt->execute([$appointment_id]);
$history = $stmt->fetchAll();

// Traducciones
$typeLabels = ['llamada' => 'Llamada telefonica', 'reunion_virtual' => 'Videollamada', 'reunion_presencial' => 'Reunion presencial'];
$deptLabels = ['contabilidad' => 'Contabilidad', 'fiscalidad' => 'Fiscalidad', 'laboral' => 'Laboral', 'gestion' => 'Gestion'];
$statusLabels = ['solicitado' => 'Pendiente', 'agendado' => 'Confirmada', 'finalizado' => 'Finalizada', 'cancelado' => 'Cancelada'];
$actionLabels = [
    'created' => 'Cita creada',
    'status_changed' => 'Estado cambiado',
    'scheduled' => 'Cita agendada',
    'rescheduled' => 'Cita reprogramada',
    'cancelled' => 'Cita cancelada',
    'reactivated' => 'Cita reactivada',
    'edited' => 'Campo editado',
    'notes_updated' => 'Notas actualizadas',
    'message_sent' => 'Mensaje enviado',
    'finalized' => 'Cita finalizada',
    'date_proposed' => 'Fecha propuesta',
    'proposal_accepted' => 'Propuesta aceptada',
    'confirmed' => 'Cita confirmada'
];

// Variables v2 para estado de fecha
$needsConfirmation = ($appointment['needs_confirmation_from'] === 'advisory');
$hasScheduledDate = !empty($appointment['scheduled_date']);
$hasProposedDate = !empty($appointment['proposed_date']);
$proposedBy = $appointment['proposed_by'] ?? null;
?>

<style>
:root { --facilitame-primary: #00c2cb; --facilitame-primary-dark: #009ba3; --facilitame-primary-light: rgba(0, 194, 203, 0.1); }

.btn-light-modern { background: white; border: 2px solid #e2e8f0; color: #64748b; padding: 0.625rem 1.25rem; font-weight: 600; border-radius: 10px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
.btn-light-modern:hover { background: #f8fafc; border-color: var(--facilitame-primary); color: var(--facilitame-primary); transform: translateX(-4px); }

.card-detail-modern { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.card-detail-header { padding: 1.75rem 2rem; background: linear-gradient(135deg, var(--facilitame-primary) 0%, #00a8b0 100%); display: flex; align-items: flex-start; gap: 1.25rem; position: relative; }
.card-detail-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; }
.card-detail-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.card-detail-title { font-size: 1.5rem; font-weight: 700; color: white; margin: 0 0 0.375rem 0; }
.card-detail-subtitle { font-size: 0.9375rem; color: rgba(255,255,255,0.9); display: flex; align-items: center; gap: 0.375rem; }
.card-detail-body { padding: 2rem; }

.badge-modern { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-left: auto; }
.badge-light-warning { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }
.badge-light-primary { background: var(--facilitame-primary-light); color: var(--facilitame-primary-dark); border: 1px solid rgba(0,194,203,0.3); }
.badge-light-success { background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.3); }
.badge-light-danger { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.3); }

.alert-modern { display: flex; align-items: flex-start; gap: 1rem; padding: 1.25rem; border-radius: 12px; border: 1px solid; margin-bottom: 1.5rem; animation: fadeIn 0.5s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.alert-modern-primary { background: var(--facilitame-primary-light); border-color: rgba(0,194,203,0.3); }
.alert-modern-warning { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); }
.alert-modern-danger { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); }
.alert-modern-success { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); }
.alert-modern-icon { flex-shrink: 0; }
.alert-modern-primary .alert-modern-icon { color: var(--facilitame-primary); }
.alert-modern-warning .alert-modern-icon { color: #f59e0b; }
.alert-modern-danger .alert-modern-icon { color: #dc2626; }
.alert-modern-success .alert-modern-icon { color: #16a34a; }
.alert-modern-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
.alert-modern-text { font-size: 0.875rem; color: #64748b; line-height: 1.5; }

.alert-action-required { background: linear-gradient(135deg, rgba(245,158,11,0.15) 0%, rgba(251,191,36,0.1) 100%); border: 2px solid rgba(245,158,11,0.5); animation: glow 2s infinite; }
@keyframes glow { 0%, 100% { box-shadow: 0 0 5px rgba(245,158,11,0.3); } 50% { box-shadow: 0 0 20px rgba(245,158,11,0.5); } }
.alert-action-required .alert-modern-title { color: #92400e; }
.alert-action-required .btn-accept-inline { margin-top: 0.75rem; }

/* Alert mensajes sin leer en detalle */
.alert-unread-detail { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; background: linear-gradient(135deg, var(--facilitame-primary-light) 0%, rgba(0,194,203,0.08) 100%); border: 2px solid rgba(0,194,203,0.5); border-radius: 12px; animation: glow-facilitame-detail 2s infinite; }
@keyframes glow-facilitame-detail { 0%, 100% { box-shadow: 0 0 5px rgba(0,194,203,0.3); } 50% { box-shadow: 0 0 20px rgba(0,194,203,0.5); } }
.alert-unread-detail-icon { width: 50px; height: 50px; background: linear-gradient(135deg, var(--facilitame-primary) 0%, var(--facilitame-primary-dark) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; animation: pulse-icon 1.5s infinite; }
@keyframes pulse-icon { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
.alert-unread-detail-content { flex: 1; }
.alert-unread-detail-title { font-size: 1rem; font-weight: 700; color: var(--facilitame-primary-dark); margin-bottom: 0.25rem; }
.alert-unread-detail-text { font-size: 0.875rem; color: var(--facilitame-primary); }
.alert-unread-detail .btn-light-primary { background: var(--facilitame-primary-light); border: 1px solid rgba(0,194,203,0.3); color: var(--facilitame-primary-dark); font-weight: 600; transition: all 0.3s ease; }
.alert-unread-detail .btn-light-primary:hover { background: var(--facilitame-primary); color: white; }

.section-detail-modern { margin-bottom: 2rem; }
.section-detail-modern:last-child { margin-bottom: 0; }
.section-detail-header { display: flex; align-items: center; gap: 0.875rem; margin-bottom: 1.25rem; }
.section-detail-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: white; background: linear-gradient(135deg, var(--facilitame-primary) 0%, var(--facilitame-primary-dark) 100%); }
.section-detail-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }

.details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.detail-item { background: #f8fafc; padding: 1rem 1.25rem; border-radius: 10px; border: 1px solid #e2e8f0; transition: all 0.3s ease; }
.detail-item:hover { background: white; border-color: var(--facilitame-primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,194,203,0.1); }
.detail-item-full { grid-column: 1 / -1; }
.detail-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.375rem; }
.detail-value { font-size: 0.9375rem; font-weight: 600; color: #1e293b; line-height: 1.5; }
.detail-value-text { font-weight: 500; line-height: 1.6; }
.detail-value-muted { color: #94a3b8; font-style: italic; }

.detail-item-date { position: relative; }
.detail-item-date.confirmed { border-color: var(--facilitame-primary); background: var(--facilitame-primary-light); }
.detail-item-date.proposed { border-color: #f59e0b; background: rgba(245,158,11,0.1); }
.detail-item-date .date-badge { position: absolute; top: -8px; right: 10px; font-size: 0.625rem; padding: 0.125rem 0.5rem; border-radius: 4px; font-weight: 700; text-transform: uppercase; }
.detail-item-date.confirmed .date-badge { background: var(--facilitame-primary); color: white; }
.detail-item-date.proposed .date-badge { background: #f59e0b; color: white; }

.contact-card { background: linear-gradient(135deg, #f8fafc 0%, #fff 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: all 0.3s ease; }
.contact-card:hover { border-color: var(--facilitame-primary); box-shadow: 0 4px 12px rgba(0,194,203,0.1); }
.contact-card-name { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; }
.contact-card-details { display: flex; flex-direction: column; gap: 0.5rem; }
.contact-link { color: #64748b; text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; font-weight: 500; }
.contact-link:hover { color: var(--facilitame-primary); transform: translateX(4px); }

.separator-modern { height: 1px; background: repeating-linear-gradient(to right, #e2e8f0 0px, #e2e8f0 6px, transparent 6px, transparent 12px); margin: 1.5rem 0; }

.action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1.5rem; }
.btn-action { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: none; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
.btn-action-primary { background: var(--facilitame-primary); color: white; }
.btn-action-primary:hover { background: var(--facilitame-primary-dark); color: white; }
.btn-action-success { background: #059669; color: white; }
.btn-action-success:hover { background: #047857; color: white; }
.btn-action-warning { background: #d97706; color: white; }
.btn-action-warning:hover { background: #b45309; color: white; }
.btn-action-danger { background: #dc2626; color: white; }
.btn-action-danger:hover { background: #b91c1c; color: white; }
.btn-action-secondary { background: #64748b; color: white; }
.btn-action-secondary:hover { background: #475569; color: white; }

/* Historia */
.history-timeline { position: relative; padding-left: 1.5rem; }
.history-timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.history-item { position: relative; padding: 0.75rem 0; padding-left: 1.5rem; }
.history-item::before { content: ''; position: absolute; left: -1rem; top: 1rem; width: 10px; height: 10px; border-radius: 50%; background: var(--facilitame-primary); border: 2px solid white; box-shadow: 0 0 0 2px var(--facilitame-primary-light); }
.history-item-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; flex-wrap: wrap; }
.history-action { font-size: 0.8125rem; font-weight: 600; color: #1e293b; }
.history-user { font-size: 0.75rem; color: #64748b; }
.history-date { font-size: 0.6875rem; color: #94a3b8; margin-left: auto; }
.history-details { font-size: 0.75rem; color: #64748b; }

/* Chat */
.card-chat-modern { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.card-chat-header { padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%); border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.card-chat-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.card-chat-title i { color: var(--facilitame-primary); }
.card-chat-body { flex: 1; display: flex; flex-direction: column; min-height: 0; }

.chat-messages-container { flex: 1; overflow-y: auto; padding: 1.25rem; background: linear-gradient(to bottom, #f8fafc 0%, #fff 100%); min-height: 280px; max-height: 350px; scrollbar-width: thin; scrollbar-color: var(--facilitame-primary-light) transparent; }
.chat-messages-container::-webkit-scrollbar { width: 6px; }
.chat-messages-container::-webkit-scrollbar-thumb { background: var(--facilitame-primary-light); border-radius: 3px; }

.chat-loading, .chat-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; text-align: center; }
.chat-loading-spinner { width: 36px; height: 36px; border: 3px solid var(--facilitame-primary-light); border-top-color: var(--facilitame-primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 0.75rem; }
@keyframes spin { to { transform: rotate(360deg); } }
.chat-empty-icon { width: 60px; height: 60px; background: var(--facilitame-primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: var(--facilitame-primary); }
.chat-empty-title { font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; font-size: 0.9375rem; }
.chat-empty-text { color: #64748b; font-size: 0.8125rem; }

.chat-message { display: flex; margin-bottom: 1rem; }
.chat-message-left { justify-content: flex-start; }
.chat-message-right { justify-content: flex-end; }
.chat-message-wrapper { max-width: 75%; display: flex; flex-direction: column; }
.chat-message-sender { font-size: 0.6875rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px; }
.chat-message-right .chat-message-sender { text-align: right; }
.chat-message-bubble { padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem; line-height: 1.5; word-wrap: break-word; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.chat-message-bubble-advisory { background: linear-gradient(135deg, var(--facilitame-primary) 0%, #00a8b0 100%); color: white; border-bottom-right-radius: 4px; }
.chat-message-bubble-customer { background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
.chat-message-time { font-size: 0.6875rem; color: #94a3b8; margin-top: 0.25rem; }
.chat-message-right .chat-message-time { text-align: right; }

.chat-input-container { padding: 1rem 1.25rem; border-top: 2px solid #e2e8f0; background: white; }
.chat-input-group { display: flex; gap: 0.5rem; align-items: center; }
.chat-input { flex: 1; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.875rem; transition: all 0.3s ease; }
.chat-input:focus { outline: none; border-color: var(--facilitame-primary); box-shadow: 0 0 0 3px var(--facilitame-primary-light); }
.chat-send-btn { width: 44px; height: 44px; background: linear-gradient(135deg, var(--facilitame-primary) 0%, #00a8b0 100%); border: none; border-radius: 10px; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; flex-shrink: 0; }
.chat-send-btn:hover:not(:disabled) { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,194,203,0.4); }
.chat-send-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Tabs */
.nav-tabs-modern { border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem; }
.nav-tabs-modern .nav-link { border: none; color: #64748b; font-weight: 600; font-size: 0.875rem; padding: 0.75rem 1.25rem; margin-bottom: -2px; transition: all 0.2s ease; }
.nav-tabs-modern .nav-link:hover { color: var(--facilitame-primary); }
.nav-tabs-modern .nav-link.active { color: var(--facilitame-primary); border-bottom: 2px solid var(--facilitame-primary); }

@media (max-width: 991px) { .card-detail-header { flex-direction: column; align-items: flex-start; } .badge-modern { margin-left: 0; margin-top: 0.75rem; } }
@media (max-width: 767px) { .card-detail-body { padding: 1.25rem; } .contact-card { flex-direction: column; text-align: center; } .chat-message-wrapper { max-width: 90%; } }
</style>

<div class="advisory-appointment-detail-page mt-6">
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content">
            
            <div class="mb-4">
                <a href="/appointments" class="btn-light-modern">
                    <i class="ki-outline ki-arrow-left fs-4"></i> Volver a Citas
                </a>
            </div>
            
            <?php if ($unreadCount > 0): ?>
            <!-- Alert: Mensajes sin leer del cliente -->
            <div class="alert-unread-detail mb-4">
                <div class="alert-unread-detail-icon">
                    <i class="ki-outline ki-message-text-2 fs-2x"></i>
                </div>
                <div class="alert-unread-detail-content">
                    <div class="alert-unread-detail-title">
                        Tenias <?php echo $unreadCount; ?> mensaje(s) sin leer del cliente
                    </div>
                    <div class="alert-unread-detail-text">
                        Revisa el chat a la derecha. Los mensajes ya han sido marcados como leidos.
                    </div>
                </div>
                <a href="#chat-section" class="btn btn-sm btn-light-primary">
                    <i class="ki-outline ki-message-text-2 fs-6 me-1"></i> Ver chat
                </a>
            </div>
            <?php endif; ?>
            
            <div class="row gx-4">
                <!-- Detalles y edicion -->
                <div class="col-xl-7 mb-4">
                    <div class="card-detail-modern h-100">
                        <div class="card-detail-header">
                            <div class="card-detail-icon">
                                <i class="ki-outline ki-calendar fs-2x text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-detail-title">Cita #<?php echo $appointment['id']; ?></h3>
                                <div class="card-detail-subtitle">
                                    <i class="ki-outline ki-profile-user fs-6"></i>
                                    <?php echo htmlspecialchars($appointment['customer_name'] . ' ' . $appointment['customer_lastname']); ?>
                                </div>
                            </div>
                            <span class="badge-modern badge-light-<?php 
                                echo $appointment['status'] === 'solicitado' ? 'warning' : 
                                    ($appointment['status'] === 'agendado' ? 'primary' : 
                                    ($appointment['status'] === 'finalizado' ? 'success' : 'danger'));
                            ?>">
                                <i class="ki-outline ki-<?php 
                                    echo $appointment['status'] === 'solicitado' ? 'time' : 
                                        ($appointment['status'] === 'agendado' ? 'calendar-tick' : 
                                        ($appointment['status'] === 'finalizado' ? 'check-circle' : 'cross-circle'));
                                ?> fs-7"></i>
                                <?php echo $statusLabels[$appointment['status']] ?? $appointment['status']; ?>
                            </span>
                        </div>
                        
                        <div class="card-detail-body">
                            <!-- Tabs -->
                            <ul class="nav nav-tabs nav-tabs-modern" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Informacion</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-edit">Editar</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-history">Historial</a></li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Tab Info -->
                                <div class="tab-pane fade show active" id="tab-info">
                                    
                                    <?php if ($needsConfirmation && $hasProposedDate): ?>
                                    <!-- ALERTA: Cliente propuso fecha - necesita tu confirmacion -->
                                    <div class="alert-modern alert-modern-warning alert-action-required">
                                        <div class="alert-modern-icon"><i class="ki-outline ki-notification-bing fs-2x"></i></div>
                                        <div class="alert-modern-content">
                                            <div class="alert-modern-title">El cliente ha propuesto una fecha</div>
                                            <div class="alert-modern-text">
                                                <strong><?php echo !empty($appointment['proposed_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['proposed_date'])) : '-'; ?></strong><br>
                                                Acepta la propuesta o reprograma con otra fecha.
                                            </div>
                                            <button class="btn btn-success btn-accept-inline" onclick="acceptProposal()">
                                                <i class="ki-outline ki-check fs-4 me-1"></i> Aceptar esta fecha
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($hasScheduledDate): ?>
                                    <!-- Cita confirmada -->
                                    <div class="alert-modern alert-modern-success">
                                        <div class="alert-modern-icon"><i class="ki-outline ki-calendar-tick fs-2x"></i></div>
                                        <div class="alert-modern-content">
                                            <div class="alert-modern-title">Cita Confirmada</div>
                                            <div class="alert-modern-text"><?php echo !empty($appointment['scheduled_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['scheduled_date'])) : '-'; ?></div>
                                            <a href="#" onclick="addToGoogleCalendar(); return false;" class="btn btn-sm btn-light-success mt-2">
                                                <i class="ki-outline ki-calendar-add me-1"></i> Añadir a Google Calendar
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($hasProposedDate && $proposedBy === 'advisory'): ?>
                                    <!-- Esperando confirmacion del cliente -->
                                    <div class="alert-modern alert-modern-warning">
                                        <div class="alert-modern-icon"><i class="ki-outline ki-time fs-2x"></i></div>
                                        <div class="alert-modern-content">
                                            <div class="alert-modern-title">Esperando confirmacion del cliente</div>
                                            <div class="alert-modern-text">
                                                Propusiste: <strong><?php echo !empty($appointment['proposed_date']) ? date('d/m/Y \a \l\a\s H:i', strtotime($appointment['proposed_date'])) : '-'; ?></strong><br>
                                                El cliente debe confirmar o negociar por chat.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($appointment['status'] === 'solicitado'): ?>
                                    <!-- Pendiente sin fecha -->
                                    <div class="alert-modern alert-modern-warning">
                                        <div class="alert-modern-icon"><i class="ki-outline ki-information-2 fs-2x"></i></div>
                                        <div class="alert-modern-content">
                                            <div class="alert-modern-title">Pendiente de Agendar</div>
                                            <div class="alert-modern-text">Propone una fecha para esta cita.</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] === 'cancelado'): ?>
                                    <div class="alert-modern alert-modern-danger">
                                        <div class="alert-modern-icon"><i class="ki-outline ki-cross-circle fs-2x"></i></div>
                                        <div class="alert-modern-content">
                                            <div class="alert-modern-title">Cita Cancelada</div>
                                            <div class="alert-modern-text">
                                                Por: <?php echo $appointment['cancelled_by'] === 'advisory' ? 'Asesoria' : 'Cliente'; ?>
                                                <?php if (!empty($appointment['cancelled_at'])): ?>
                                                    el <?php echo fdatetime($appointment['cancelled_at']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($appointment['cancellation_reason'])): ?>
                                                    <br>Motivo: <?php echo htmlspecialchars($appointment['cancellation_reason']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="details-grid">
                                        <div class="detail-item">
                                            <div class="detail-label"><i class="ki-outline ki-phone fs-6"></i> Tipo</div>
                                            <div class="detail-value"><?php echo $typeLabels[$appointment['type']] ?? $appointment['type']; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label"><i class="ki-outline ki-abstract-26 fs-6"></i> Departamento</div>
                                            <div class="detail-value"><?php echo $deptLabels[$appointment['department']] ?? $appointment['department']; ?></div>
                                        </div>
                                        
                                        <?php if ($hasScheduledDate): ?>
                                        <div class="detail-item detail-item-date confirmed">
                                            <span class="date-badge">Confirmada</span>
                                            <div class="detail-label"><i class="ki-outline ki-calendar-tick fs-6"></i> Fecha de la Cita</div>
                                            <div class="detail-value"><?php echo fdatetime($appointment['scheduled_date']); ?></div>
                                        </div>
                                        <?php elseif ($hasProposedDate): ?>
                                        <div class="detail-item detail-item-date proposed">
                                            <span class="date-badge"><?php echo $proposedBy === 'advisory' ? 'Tu propuesta' : 'Propuesta cliente'; ?></span>
                                            <div class="detail-label"><i class="ki-outline ki-calendar fs-6"></i> Fecha Propuesta</div>
                                            <div class="detail-value"><?php echo fdatetime($appointment['proposed_date']); ?></div>
                                        </div>
                                        <?php else: ?>
                                        <div class="detail-item">
                                            <div class="detail-label"><i class="ki-outline ki-calendar fs-6"></i> Fecha</div>
                                            <div class="detail-value detail-value-muted">Pendiente de agendar</div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="detail-item">
                                            <div class="detail-label"><i class="ki-outline ki-calendar fs-6"></i> Solicitada</div>
                                            <div class="detail-value"><?php echo fdatetime($appointment['created_at']); ?></div>
                                        </div>
                                        
                                        <?php if (!empty($appointment['reason'])): ?>
                                        <div class="detail-item detail-item-full">
                                            <div class="detail-label"><i class="ki-outline ki-message-text fs-6"></i> Motivo</div>
                                            <div class="detail-value detail-value-text"><?php echo nl2br(htmlspecialchars($appointment['reason'])); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appointment['notes_advisory'])): ?>
                                        <div class="detail-item detail-item-full">
                                            <div class="detail-label"><i class="ki-outline ki-notepad fs-6"></i> Notas Internas</div>
                                            <div class="detail-value detail-value-text"><?php echo nl2br(htmlspecialchars($appointment['notes_advisory'])); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="separator-modern"></div>

                                    <div class="contact-card">
                                        <div class="contact-card-icon">
                                            <i class="ki-outline ki-profile-circle fs-3x text-primary"></i>
                                        </div>
                                        <div class="contact-card-info">
                                            <div class="contact-card-name"><?php echo htmlspecialchars($appointment['customer_name'] . ' ' . $appointment['customer_lastname']); ?></div>
                                            <div class="contact-card-details">
                                                <a href="mailto:<?php echo htmlspecialchars($appointment['customer_email']); ?>" class="contact-link">
                                                    <i class="ki-outline ki-sms fs-6"></i>
                                                    <?php echo htmlspecialchars($appointment['customer_email']); ?>
                                                </a>
                                                <?php if (!empty($appointment['customer_phone'])): ?>
                                                <a href="tel:<?php echo $appointment['customer_phone']; ?>" class="contact-link">
                                                    <i class="ki-outline ki-phone fs-6"></i>
                                                    <?php echo function_exists('phone') ? phone($appointment['customer_phone']) : $appointment['customer_phone']; ?>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Acciones rapidas -->
                                    <div class="action-buttons">
                                        <?php if ($needsConfirmation && $hasProposedDate): ?>
                                        <button class="btn-action btn-action-success" onclick="acceptProposal()">
                                            <i class="ki-outline ki-check text-white"></i> Aceptar Propuesta
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
                                        <button class="btn-action btn-action-warning" data-bs-toggle="modal" data-bs-target="#modal_reschedule">
                                            <i class="ki-outline ki-calendar-edit text-white"></i> <?php echo $hasScheduledDate ? 'Reprogramar' : 'Proponer Fecha'; ?>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] === 'agendado'): ?>
                                        <button class="btn-action btn-action-success" onclick="finalizeAppointment()">
                                            <i class="ki-outline ki-check-circle text-white"></i> Finalizar
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
                                        <button class="btn-action btn-action-danger" data-bs-toggle="modal" data-bs-target="#modal_cancel">
                                            <i class="ki-outline ki-cross text-white"></i> Cancelar
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] === 'cancelado'): ?>
                                        <button class="btn-action btn-action-secondary" onclick="reactivateAppointment()">
                                            <i class="ki-outline ki-arrow-circle-left text-white"></i> Reactivar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Tab Editar -->
                                <div class="tab-pane fade" id="tab-edit">
                                    <form id="form_edit_appointment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo de Cita</label>
                                                <select name="type" class="form-select">
                                                    <option value="llamada" <?php echo $appointment['type'] === 'llamada' ? 'selected' : ''; ?>>Llamada</option>
                                                    <option value="reunion_virtual" <?php echo $appointment['type'] === 'reunion_virtual' ? 'selected' : ''; ?>>Videollamada</option>
                                                    <option value="reunion_presencial" <?php echo $appointment['type'] === 'reunion_presencial' ? 'selected' : ''; ?>>Presencial</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Departamento</label>
                                                <select name="department" class="form-select">
                                                    <option value="contabilidad" <?php echo $appointment['department'] === 'contabilidad' ? 'selected' : ''; ?>>Contabilidad</option>
                                                    <option value="fiscalidad" <?php echo $appointment['department'] === 'fiscalidad' ? 'selected' : ''; ?>>Fiscalidad</option>
                                                    <option value="laboral" <?php echo $appointment['department'] === 'laboral' ? 'selected' : ''; ?>>Laboral</option>
                                                    <option value="gestion" <?php echo $appointment['department'] === 'gestion' ? 'selected' : ''; ?>>Gestion</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Motivo</label>
                                            <textarea name="reason" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['reason'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Notas Internas (solo asesoria)</label>
                                            <textarea name="notes_advisory" class="form-control" rows="3" placeholder="Notas privadas..."><?php echo htmlspecialchars($appointment['notes_advisory'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary-facilitame">
                                            <i class="ki-outline ki-check me-1"></i> Guardar cambios
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Tab Historial -->
                                <div class="tab-pane fade" id="tab-history">
                                    <?php if (empty($history)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="ki-outline ki-time fs-3x mb-3"></i>
                                        <p>No hay historial de cambios</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="history-timeline">
                                        <?php foreach ($history as $h): ?>
                                        <div class="history-item">
                                            <div class="history-item-header">
                                                <span class="history-action"><?php echo $actionLabels[$h['action']] ?? $h['action']; ?></span>
                                                <span class="history-user">por <?php echo (isset($h['user_type']) && $h['user_type'] === 'advisory') ? 'Asesoria' : 'Cliente'; ?></span>
                                                <span class="history-date"><?php echo fdatetime($h['created_at']); ?></span>
                                            </div>
                                            <?php if (!empty($h['field_changed']) || !empty($h['notes'])): ?>
                                            <div class="history-details">
                                                <?php if (!empty($h['field_changed'])): ?>
                                                    <?php echo htmlspecialchars($h['field_changed']); ?>: 
                                                    <?php if (!empty($h['old_value'])): ?>"<?php echo htmlspecialchars($h['old_value']); ?>" -> <?php endif; ?>
                                                    "<?php echo htmlspecialchars($h['new_value'] ?? ''); ?>"
                                                <?php endif; ?>
                                                <?php if (!empty($h['notes'])): ?>
                                                    <?php echo htmlspecialchars($h['notes']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat -->
                <div class="col-xl-5 mb-4" id="chat-section">
                    <div class="card-chat-modern h-100">
                        <div class="card-chat-header">
                            <h3 class="card-chat-title"><i class="ki-outline ki-message-text-2"></i> Chat con Cliente</h3>
                        </div>
                        
                        <div class="card-chat-body">
                            <div id="chatMessages" class="chat-messages-container">
                                <div class="chat-loading">
                                    <div class="chat-loading-spinner"></div>
                                    <p>Cargando mensajes...</p>
                                </div>
                            </div>
                            
                            <div class="chat-input-container">
                                <form id="chatForm" onsubmit="sendMessage(event)">
                                    <div class="chat-input-group">
                                        <input type="text" id="messageInput" class="chat-input" placeholder="Escribe tu mensaje..." required autocomplete="off">
                                        <button type="submit" class="chat-send-btn" id="sendButton">
                                            <i class="ki-outline ki-send fs-4 text-white"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reprogramar / Proponer Fecha -->
<?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
<div class="modal fade" id="modal_reschedule" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ki-outline ki-calendar-edit fs-2 text-warning me-2"></i> <?php echo $hasScheduledDate ? 'Reprogramar Cita' : 'Proponer Fecha'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_reschedule">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                <input type="hidden" name="action" value="reschedule">
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start mb-4">
                        <i class="ki-outline ki-information-2 fs-3 me-3 mt-1"></i>
                        <div><strong>Nueva fecha propuesta</strong><br><small>El cliente recibira la propuesta y debera confirmarla.</small></div>
                    </div>
                    <?php if ($hasScheduledDate || $hasProposedDate): ?>
                    <p class="text-muted mb-3">
                        Fecha actual: <strong><?php echo fdatetime($appointment['scheduled_date'] ?? $appointment['proposed_date']); ?></strong>
                    </p>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label required">Nueva Fecha y Hora</label>
                        <input type="datetime-local" name="proposed_date" id="reschedule_proposed_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-white"><i class="ki-outline ki-calendar-edit fs-4 me-1"></i> Proponer Fecha</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Cancelar -->
<?php if (in_array($appointment['status'], ['solicitado', 'agendado'])): ?>
<div class="modal fade" id="modal_cancel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ki-outline ki-cross-circle fs-2 text-danger me-2"></i> Cancelar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_cancel">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                <input type="hidden" name="action" value="cancel">
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="ki-outline ki-information-2 fs-2 me-3"></i>
                        <div>Esta accion cancelara la cita y notificara al cliente.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo de cancelacion (opcional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Indica el motivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger"><i class="ki-outline ki-cross fs-4 text-white"></i> Cancelar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var APPOINTMENT_ID = <?php echo $appointment_id; ?>;
var chatInterval;

// Fecha minima para proponer: ahora mismo (permite mismo día con hora futura)
var now = new Date();
// Redondear al próximo intervalo de 15 minutos
now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
var minDate = now.toISOString().slice(0, 16);
var rescheduleDateInput = document.getElementById('reschedule_proposed_date');
if (rescheduleDateInput) rescheduleDateInput.min = minDate;

// ============================================
// CHAT
// ============================================
function loadMessages() {
    fetch('/api/advisory-appointment-chat-messages?appointment_id=' + APPOINTMENT_ID)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok') {
                displayMessages(data.data.messages || []);
            }
        })
        .catch(function(err) { console.error('Error loading messages:', err); });
}

function displayMessages(msgs) {
    var c = document.getElementById('chatMessages');
    
    if (!msgs || !msgs.length) {
        c.innerHTML = '<div class="chat-empty-state"><div class="chat-empty-icon"><i class="ki-outline ki-message-text-2 fs-4x"></i></div><h4 class="chat-empty-title">No hay mensajes</h4><p class="chat-empty-text">Inicia la conversacion con el cliente!</p></div>';
        return;
    }
    
    var h = '';
    msgs.forEach(function(m) {
        var isAdvisory = m.sender_type === 'advisory';
        h += '<div class="chat-message ' + (isAdvisory ? 'chat-message-right' : 'chat-message-left') + '">' +
            '<div class="chat-message-wrapper">' +
            '<div class="chat-message-sender">' + (isAdvisory ? 'Tu' : 'Cliente') + '</div>' +
            '<div class="chat-message-bubble ' + (isAdvisory ? 'chat-message-bubble-advisory' : 'chat-message-bubble-customer') + '">' + escapeHtml(m.content || m.message) + '</div>' +
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
    
    fetch('/api/advisory-appointment-chat-send', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'ok') {
                inp.value = '';
                loadMessages();
            } else {
                Swal.fire({ icon: 'error', title: d.message || 'Error al enviar mensaje', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        })
        .catch(function() { Swal.fire({ icon: 'error', title: 'Error al enviar mensaje', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ki-outline ki-send fs-4 text-white"></i>';
            inp.focus();
        });
}

function formatTime(d) {
    if (!d) return '';
    var dt = new Date(d);
    return dt.toLocaleDateString('es-ES') + ' ' + dt.toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit'});
}

function escapeHtml(t) { if (!t) return ''; var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

// ============================================
// ACCIONES v2
// ============================================

// Aceptar propuesta del cliente
window.acceptProposal = function() {
    Swal.fire({
        title: 'Aceptar propuesta',
        html: 'Se confirmara la fecha propuesta por el cliente',
        icon: 'question',
        iconColor: '#00c2cb',
        showCancelButton: true,
        confirmButtonColor: '#00c2cb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Si, aceptar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('appointment_id', APPOINTMENT_ID);
            fd.append('action', 'accept_proposal');
            
            fetch('/api/advisory-update-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Confirmada', text: 'La cita ha sido agendada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo confirmar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); });
        }
    });
};

// Editar
document.getElementById('form_edit_appointment')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';
    
    fetch('/api/advisory-update-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Cambios guardados', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: result.message || 'Error al guardar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); btn.disabled = false; btn.innerHTML = originalText; });
});

// Reprogramar / Proponer fecha
document.getElementById('form_reschedule')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
    
    fetch('/api/advisory-update-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Propuesta enviada al cliente', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: result.message || 'Error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); btn.disabled = false; btn.innerHTML = originalText; });
});

// Cancelar
document.getElementById('form_cancel')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Cancelando...';
    
    fetch('/api/advisory-update-appointment', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.status === 'ok') {
            Swal.fire({ icon: 'success', title: 'Cita cancelada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            Swal.fire({ icon: 'error', title: result.message || 'Error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); btn.disabled = false; btn.innerHTML = originalText; });
});

// Finalizar
window.finalizeAppointment = function() {
    Swal.fire({
        title: 'Finalizar cita',
        html: 'Se marcara la cita como completada',
        icon: 'question',
        iconColor: '#00c2cb',
        showCancelButton: true,
        confirmButtonColor: '#00c2cb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Si, finalizar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('appointment_id', APPOINTMENT_ID);
            fd.append('action', 'finalize');
            
            fetch('/api/advisory-update-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Finalizada', text: 'La cita ha sido completada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo finalizar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); });
        }
    });
};

// Reactivar
window.reactivateAppointment = function() {
    Swal.fire({
        title: 'Reactivar cita',
        html: 'Se reactivara la cita cancelada',
        icon: 'question',
        iconColor: '#00c2cb',
        showCancelButton: true,
        confirmButtonColor: '#00c2cb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Si, reactivar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('appointment_id', APPOINTMENT_ID);
            fd.append('status', 'solicitado');
            
            fetch('/api/advisory-update-appointment', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Reactivada', text: 'La cita está nuevamente activa', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo reactivar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); });
        }
    });
};

// Init
document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    chatInterval = setInterval(loadMessages, 5000);
});

window.addEventListener('beforeunload', function() { if (chatInterval) clearInterval(chatInterval); });

// Google Calendar
window.addToGoogleCalendar = function() {
    <?php if ($hasScheduledDate): ?>
    var startDate = new Date('<?php echo date('c', strtotime($appointment['scheduled_date'])); ?>');
    var endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // 1 hora después

    var title = encodeURIComponent('Cita con <?php echo addslashes(htmlspecialchars($appointment['customer_name'] . ' ' . $appointment['customer_lastname'])); ?>');
    var details = encodeURIComponent('<?php echo addslashes($typeLabels[$appointment['type']] ?? $appointment['type']); ?> - <?php echo addslashes($deptLabels[$appointment['department']] ?? $appointment['department']); ?>\n\nMotivo: <?php echo addslashes(str_replace(["\r\n", "\r", "\n"], " ", $appointment['reason'] ?? '')); ?>');
    var location = encodeURIComponent('<?php echo $appointment['type'] === 'reunion_presencial' ? addslashes($appointment['direccion'] ?? '') : ($appointment['type'] === 'reunion_virtual' ? 'Videollamada' : 'Llamada telefónica'); ?>');

    var formatDate = function(d) {
        return d.toISOString().replace(/-|:|\.\d+/g, '').slice(0, 15) + 'Z';
    };

    var url = 'https://calendar.google.com/calendar/render?action=TEMPLATE' +
        '&text=' + title +
        '&dates=' + formatDate(startDate) + '/' + formatDate(endDate) +
        '&details=' + details +
        '&location=' + location;

    window.open(url, '_blank');
    <?php endif; ?>
};
</script>