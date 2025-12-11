<?php
/**
 * Detalle de Asesoría - Panel Admin
 * /pages/administrador/advisory.php
 */
$scripts = [];

$advisory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$advisory_id) {
    header('Location: advisories');
    exit;
}
?>

<div class="advisory-detail-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <div class="advisory-detail-layout" style="flex: 1; display: flex; gap: 1.5rem; min-height: 0;">
        
        <!-- SIDEBAR ASESORÍA -->
        <aside class="advisory-sidebar" style="width: 320px; flex-shrink: 0; display: flex; flex-direction: column;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column;">
                <div class="card-body" style="flex: 1; overflow-y: auto;">
                    
                    <div class="customer-profile">
                        <div class="customer-avatar" id="advisory-avatar">
                            <img src="assets/media/bold/profile-default.jpg" alt="Asesoría" loading="lazy">
                        </div>
                        
                        <h3 class="customer-name" id="advisory-name">Cargando...</h3>
                        <p class="text-muted mb-2" id="advisory-cif" style="font-size: 0.875rem;">-</p>
                        
                        <div class="mb-3">
                            <span class="advisory-code" id="advisory-code">-</span>
                        </div>
                        
                        <div id="advisory-status-badge">
                            <span class="badge-status badge-status-neutral">Cargando...</span>
                        </div>
                        
                        <div class="customer-stat" id="advisory-stat-customers">
                            <span class="customer-stat-value">0</span>
                            <span class="customer-stat-label">Clientes vinculados</span>
                        </div>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-geolocation"></i>
                            Datos de Contacto
                        </h6>
                        <dl class="customer-details">
                            <div class="customer-detail-row">
                                <dt>Dirección</dt>
                                <dd id="advisory-direccion"><span class="text-muted">-</span></dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Email Empresa</dt>
                                <dd id="advisory-email">-</dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Plan</dt>
                                <dd id="advisory-plan">-</dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Fecha Alta</dt>
                                <dd id="advisory-created">-</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-user"></i>
                            Usuario Asignado
                        </h6>
                        <div id="advisory-user-info">
                            <span class="text-muted">Sin usuario asignado</span>
                        </div>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-chart-simple"></i>
                            Estadísticas
                        </h6>
                        <dl class="customer-details">
                            <div class="customer-detail-row">
                                <dt>Clientes</dt>
                                <dd id="stat-customers">0</dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Citas pendientes</dt>
                                <dd id="stat-appointments">0</dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Mensajes sin leer</dt>
                                <dd id="stat-messages">0</dd>
                            </div>
                            <div class="customer-detail-row">
                                <dt>Total citas</dt>
                                <dd id="stat-total-appointments">0</dd>
                            </div>
                        </dl>
                    </div>
                    
                </div>
                
                <div class="card-footer" style="flex-shrink: 0; padding: 1rem; border-top: 1px solid var(--f-border);">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light-primary flex-fill" onclick="showNotAvailable('Editar asesoría')">
                            <i class="ki-outline ki-pencil me-1"></i>
                            Editar
                        </button>
                        <button type="button" class="btn btn-sm btn-light-danger" onclick="showNotAvailable('Eliminar asesoría')">
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="advisory-main" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                
                <!-- Header con tabs -->
                <div class="card-header" style="flex-shrink: 0; padding: 0;">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-customers" 
                                    type="button"
                                    role="tab"
                                    aria-selected="true">
                                <i class="ki-outline ki-people"></i>
                                Clientes
                                <span class="badge badge-sm bg-light text-muted ms-1" id="badge-customers">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-appointments" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-calendar"></i>
                                Citas
                                <span class="badge badge-sm bg-light text-muted ms-1" id="badge-appointments">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-communications" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-message-text-2"></i>
                                Comunicaciones
                                <span class="badge badge-sm bg-light text-muted ms-1" id="badge-communications">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#tab-invoices" 
                                    type="button"
                                    role="tab"
                                    aria-selected="false">
                                <i class="ki-outline ki-bill"></i>
                                Facturas
                                <span class="badge badge-sm bg-light text-muted ms-1" id="badge-invoices">0</span>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Body con contenido de tabs -->
                <div class="tab-content" style="flex: 1; overflow-y: auto; min-height: 0;">
                    
                    <!-- Tab: Clientes -->
                    <div class="tab-pane fade show active" id="tab-customers" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="details-section-title mb-0">
                                        <i class="ki-outline ki-people"></i>
                                        Clientes vinculados
                                    </h6>
                                    <span class="text-muted" id="customers-count">0 clientes</span>
                                </div>
                                
                                <!-- Buscador -->
                                <div class="search-box mb-4">
                                    <i class="ki-outline ki-magnifier"></i>
                                    <input type="text" class="form-control" placeholder="Buscar clientes..." id="search-customers">
                                </div>
                                
                                <div class="tab-list-container" id="customers-list">
                                    <div class="loading-state">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="ms-2">Cargando clientes...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Citas -->
                    <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="details-section-title mb-0">
                                        <i class="ki-outline ki-calendar"></i>
                                        Citas programadas
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted" id="appointments-count">0 citas</span>
                                        <select class="form-select form-select-sm" id="filter-appointment-status" style="width: auto;">
                                            <option value="">Todos los estados</option>
                                            <option value="solicitado">Solicitado</option>
                                            <option value="agendado">Agendado</option>
                                            <option value="finalizado">Finalizado</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="tab-list-container" id="appointments-list">
                                    <div class="loading-state">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="ms-2">Cargando citas...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Comunicaciones -->
                    <div class="tab-pane fade" id="tab-communications" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="details-section-title mb-0">
                                        <i class="ki-outline ki-message-text-2"></i>
                                        Comunicaciones enviadas
                                    </h6>
                                    <span class="text-muted" id="communications-count">0 comunicaciones</span>
                                </div>
                                
                                <div class="tab-list-container" id="communications-list">
                                    <div class="loading-state">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="ms-2">Cargando comunicaciones...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Facturas -->
                    <div class="tab-pane fade" id="tab-invoices" role="tabpanel">
                        <div class="tab-pane-content" style="padding: 1.5rem;">
                            <div class="details-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="details-section-title mb-0">
                                        <i class="ki-outline ki-bill"></i>
                                        Facturas subidas
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted" id="invoices-count">0 facturas</span>
                                        <select class="form-select form-select-sm" id="filter-invoice-type" style="width: auto;">
                                            <option value="">Todos los tipos</option>
                                            <option value="ingreso">Ingresos</option>
                                            <option value="gasto">Gastos</option>
                                        </select>
                                        <select class="form-select form-select-sm" id="filter-invoice-status" style="width: auto;">
                                            <option value="">Todos</option>
                                            <option value="0">Pendientes</option>
                                            <option value="1">Procesadas</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="tab-list-container" id="invoices-list">
                                    <div class="loading-state">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                        <span class="ms-2">Cargando facturas...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </main>
        
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    const ADVISORY_ID = <?php echo $advisory_id; ?>;
    
    const PLAN_CONFIG = {
        'gratuito': { label: 'Gratuito', class: 'badge-status-neutral' },
        'basic': { label: 'Basic', class: 'badge-status-info' },
        'estandar': { label: 'Estándar', class: 'badge-status-primary' },
        'pro': { label: 'Pro', class: 'badge-status-success' },
        'premium': { label: 'Premium', class: 'badge-status-warning' }
    };
    
    const STATUS_CONFIG = {
        'pendiente': { label: 'Pendiente', class: 'badge-status-warning', icon: 'ki-time' },
        'activo': { label: 'Activo', class: 'badge-status-success', icon: 'ki-check-circle' },
        'suspendido': { label: 'Suspendido', class: 'badge-status-danger', icon: 'ki-lock' },
        '': { label: 'Sin estado', class: 'badge-status-neutral', icon: 'ki-information' }
    };
    
    const APPOINTMENT_STATUS = {
        'solicitado': { label: 'Solicitado', class: 'badge-status-warning' },
        'agendado': { label: 'Agendado', class: 'badge-status-info' },
        'finalizado': { label: 'Finalizado', class: 'badge-status-success' },
        'cancelado': { label: 'Cancelado', class: 'badge-status-danger' }
    };
    
    // Estado de tabs cargados
    const tabsLoaded = {
        customers: false,
        appointments: false,
        communications: false,
        invoices: false
    };
    
    // ==========================================
    // CARGA INICIAL - INFO ASESORÍA
    // ==========================================
    async function loadAdvisoryData() {
        try {
            const response = await fetch('/api/advisory-detail?id=' + ADVISORY_ID);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderAdvisoryInfo(result.data);
                // Cargar pestaña de clientes al inicio
                loadCustomers();
            } else {
                showLoadError();
            }
        } catch (error) {
            console.error('Error:', error);
            showLoadError();
        }
    }
    
    function renderAdvisoryInfo(adv) {
        const stats = adv.stats || {};
        
        document.getElementById('advisory-name').textContent = adv.razon_social || 'Sin nombre';
        document.getElementById('advisory-cif').textContent = adv.cif || '-';
        document.getElementById('advisory-code').textContent = adv.codigo_identificacion || '-';
        
        const status = STATUS_CONFIG[adv.estado] || STATUS_CONFIG[''];
        document.getElementById('advisory-status-badge').innerHTML = `
            <span class="badge-status ${status.class}">
                <i class="ki-outline ${status.icon}"></i>
                ${status.label}
            </span>
        `;
        
        const statEl = document.getElementById('advisory-stat-customers');
        if (statEl) statEl.querySelector('.customer-stat-value').textContent = stats.total_customers || 0;
        
        document.getElementById('advisory-direccion').innerHTML = adv.direccion 
            ? escapeHtml(adv.direccion) 
            : '<span class="text-muted">-</span>';
        
        document.getElementById('advisory-email').innerHTML = adv.email_empresa
            ? `<a href="mailto:${escapeHtml(adv.email_empresa)}" class="customer-email">${escapeHtml(adv.email_empresa)}</a>`
            : '-';
        
        const plan = PLAN_CONFIG[adv.plan] || PLAN_CONFIG['gratuito'];
        document.getElementById('advisory-plan').innerHTML = `<span class="badge-status ${plan.class}">${plan.label}</span>`;
        
        document.getElementById('advisory-created').innerHTML = adv.created_at
            ? `<i class="ki-outline ki-calendar text-muted"></i> ${escapeHtml(adv.created_at)}`
            : '-';
        
        const userInfoEl = document.getElementById('advisory-user-info');
        if (adv.user_id && adv.user_name && adv.user_name.trim()) {
            const userInitial = adv.user_name.trim().charAt(0).toUpperCase();
            userInfoEl.innerHTML = `
                <div class="user-info-inline">
                    <div class="user-avatar">${userInitial}</div>
                    <div class="user-details">
                        <p class="user-name">${escapeHtml(adv.user_name)}</p>
                        ${adv.user_email ? `<p class="user-email">${escapeHtml(adv.user_email)}</p>` : ''}
                        ${adv.user_phone ? `<p class="user-phone"><i class="ki-outline ki-phone me-1"></i>${escapeHtml(adv.user_phone)}</p>` : ''}
                    </div>
                </div>
            `;
        }
        
        document.getElementById('stat-customers').textContent = stats.total_customers || 0;
        document.getElementById('stat-appointments').textContent = stats.pending_appointments || 0;
        document.getElementById('stat-messages').textContent = stats.unread_messages || 0;
        document.getElementById('stat-total-appointments').textContent = stats.total_appointments || 0;
        
        document.getElementById('badge-customers').textContent = stats.total_customers || 0;
      document.getElementById('badge-appointments').textContent = stats.total_appointments || 0;

        document.getElementById('badge-communications').textContent = stats.total_communications || 0;
        document.getElementById('badge-invoices').textContent = stats.pending_invoices || 0;
    }
    
    // ==========================================
    // CARGAR CLIENTES
    // ==========================================
    async function loadCustomers(search = '') {
        const container = document.getElementById('customers-list');
        container.innerHTML = '<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2">Cargando...</span></div>';
        
        try {
            const params = new URLSearchParams({ advisory_id: ADVISORY_ID, limit: 50 });
            if (search) params.append('search', search);
            
            const response = await fetch('/api/advisory-clients-paginated-admin?' + params);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderCustomers(result.data.data || []);
                document.getElementById('customers-count').textContent = 
                    `${result.data.pagination.total_records} cliente${result.data.pagination.total_records !== 1 ? 's' : ''}`;
                tabsLoaded.customers = true;
            }
        } catch (error) {
            console.error('Error cargando clientes:', error);
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div><div class="empty-state-title">Error al cargar</div></div>';
        }
    }
    
    function renderCustomers(customers) {
        const container = document.getElementById('customers-list');
        
        if (!customers.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-people"></i></div>
                    <div class="empty-state-title">Sin clientes vinculados</div>
                    <p class="empty-state-text">Esta asesoría no tiene clientes asignados</p>
                </div>`;
            return;
        }
        
        container.innerHTML = customers.map(c => `
            <div class="list-card" data-customer-id="${c.id}">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <span class="badge-status badge-status-neutral">#${c.id}</span>
                        <a href="customer?id=${c.id}" class="list-card-customer">
                            ${escapeHtml(c.name)}
                        </a>
                        ${c.email_verified_at
                            ? '<span class="badge-status badge-status-success">Activo</span>'
                            : '<span class="badge-status badge-status-warning">Pendiente</span>'}
                    </div>
                    <div class="list-card-meta">
                        <span><i class="ki-outline ki-sms"></i>${escapeHtml(c.email)}</span>
                        ${c.phone ? `<span><i class="ki-outline ki-phone"></i>${escapeHtml(c.phone)}</span>` : ''}
                        <span class="badge-status badge-status-success">
                            <i class="ki-outline ki-check-circle"></i>
                            ${c.services_number} solicitudes
                        </span>
                    </div>
                </div>
                <div class="list-card-actions">
                    <a href="customer?id=${c.id}" class="btn-icon" title="Ver cliente">
                        <i class="ki-outline ki-eye"></i>
                    </a>
                </div>
            </div>
        `).join('');
    }
    
    // ==========================================
    // CARGAR CITAS
    // ==========================================
    async function loadAppointments(status = '') {
        const container = document.getElementById('appointments-list');
        container.innerHTML = '<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2">Cargando...</span></div>';
        
        try {
            const params = new URLSearchParams({ advisory_id: ADVISORY_ID, limit: 50 });
            if (status) params.append('status', status);
            
            const response = await fetch('/api/advisory-appointments-paginated-admin?' + params);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderAppointments(result.data.data || []);
                document.getElementById('appointments-count').textContent = 
                    `${result.data.pagination.total_records} cita${result.data.pagination.total_records !== 1 ? 's' : ''}`;
                tabsLoaded.appointments = true;
            }
        } catch (error) {
            console.error('Error cargando citas:', error);
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div><div class="empty-state-title">Error al cargar</div></div>';
        }
    }
    
    function renderAppointments(appointments) {
        const container = document.getElementById('appointments-list');
        
        if (!appointments.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-calendar"></i></div>
                    <div class="empty-state-title">Sin citas</div>
                    <p class="empty-state-text">No hay citas programadas</p>
                </div>`;
            return;
        }
        
        container.innerHTML = appointments.map(a => {
            const statusCfg = APPOINTMENT_STATUS[a.status] || { label: a.status, class: 'badge-status-neutral' };
            const date = a.scheduled_date || a.proposed_date || '-';
            
            return `
                <div class="list-card">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            <span class="badge-status badge-status-neutral">#${a.id}</span>
                            <a href="/customer?id=${a.customer_id}" class="list-card-customer">
                                ${escapeHtml(a.customer_name)}
                            </a>
                            <span class="${statusCfg.class}">${statusCfg.label}</span>
                        </div>
                        <div class="list-card-meta">
                            ${a.reason ? `<span><i class="ki-outline ki-message-text"></i>${escapeHtml(a.reason.substring(0, 50))}${a.reason.length > 50 ? '...' : ''}</span>` : ''}
                            ${a.department ? `<span><i class="ki-outline ki-briefcase"></i>${escapeHtml(a.department)}</span>` : ''}
                            <span><i class="ki-outline ki-time"></i>${date}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // ==========================================
    // CARGAR COMUNICACIONES
    // ==========================================
    async function loadCommunications() {
        const container = document.getElementById('communications-list');
        container.innerHTML = '<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2">Cargando...</span></div>';
        
        try {
            const response = await fetch('/api/advisory-communications-list-admin?advisory_id=' + ADVISORY_ID);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderCommunications(result.data.data || []);
                document.getElementById('communications-count').textContent = 
                    `${result.data.pagination.total_records} comunicación${result.data.pagination.total_records !== 1 ? 'es' : ''}`;
                tabsLoaded.communications = true;
            }
        } catch (error) {
            console.error('Error cargando comunicaciones:', error);
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div><div class="empty-state-title">Error al cargar</div></div>';
        }
    }
    
    function renderCommunications(communications) {
        const container = document.getElementById('communications-list');
        
        if (!communications.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-message-text-2"></i></div>
                    <div class="empty-state-title">Sin comunicaciones</div>
                    <p class="empty-state-text">No hay comunicaciones registradas</p>
                </div>`;
            return;
        }
        
        const importanceClass = {
            'alta': 'danger',
            'media': 'warning',
            'baja': 'info',
            'normal': 'neutral'
        };
        
        container.innerHTML = communications.map(c => `
            <div class="list-card">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <span class="list-card-customer">
                            <i class="ki-outline ki-message-text-2 me-1"></i>
                            ${escapeHtml(c.title)}
                        </span>
                        <span class="badge-status badge-status-${importanceClass[c.importance] || 'neutral'}">${c.importance || 'Normal'}</span>
                    </div>
                    <div class="list-card-meta">
                        <span><i class="ki-outline ki-eye"></i>${c.read_count} lecturas</span>
                        <span><i class="ki-outline ki-calendar"></i>${escapeHtml(c.created_at)}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // ==========================================
    // CARGAR FACTURAS
    // ==========================================
    async function loadInvoices(type = '', status = '') {
        const container = document.getElementById('invoices-list');
        container.innerHTML = '<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2">Cargando...</span></div>';
        
        try {
            const params = new URLSearchParams({ advisory_id: ADVISORY_ID, limit: 50 });
            if (type) params.append('type', type);
            if (status) params.append('status', status);
            
            const response = await fetch('/api/advisory-invoices-paginated-admin?' + params);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderInvoices(result.data.data || []);
                document.getElementById('invoices-count').textContent = 
                    `${result.data.pagination.total_records} factura${result.data.pagination.total_records !== 1 ? 's' : ''}`;
                tabsLoaded.invoices = true;
            }
        } catch (error) {
            console.error('Error cargando facturas:', error);
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div><div class="empty-state-title">Error al cargar</div></div>';
        }
    }
    
    function renderInvoices(invoices) {
        const container = document.getElementById('invoices-list');
        
        if (!invoices.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-bill"></i></div>
                    <div class="empty-state-title">Sin facturas</div>
                    <p class="empty-state-text">Los clientes aún no han subido facturas</p>
                </div>`;
            return;
        }
        
        container.innerHTML = invoices.map(inv => `
            <div class="list-card">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <span class="list-card-customer">
                            <i class="ki-outline ki-bill me-1"></i>
                            ${escapeHtml(inv.filename)}
                        </span>
                        <span class="badge-status badge-status-${inv.type === 'ingreso' ? 'success' : 'danger'}">${inv.type === 'ingreso' ? 'Ingreso' : 'Gasto'}</span>
                        ${inv.is_processed 
                            ? '<span class="badge-status badge-status-success">Procesada</span>' 
                            : '<span class="badge-status badge-status-warning">Pendiente</span>'}
                    </div>
                    <div class="list-card-meta">
                        <span><i class="ki-outline ki-user"></i>${escapeHtml(inv.customer_name)}</span>
                        <span><i class="ki-outline ki-document"></i>${inv.filesize_formatted}</span>
                        <span><i class="ki-outline ki-calendar"></i>${inv.month}/${inv.year}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // ==========================================
    // EVENTOS
    // ==========================================
    
    // Cambio de pestaña - cargar datos bajo demanda
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            
            if (target === '#tab-appointments' && !tabsLoaded.appointments) {
                loadAppointments();
            } else if (target === '#tab-communications' && !tabsLoaded.communications) {
                loadCommunications();
            } else if (target === '#tab-invoices' && !tabsLoaded.invoices) {
                loadInvoices();
            }
        });
    });
    
    // Búsqueda de clientes
    let searchTimeout;
    document.getElementById('search-customers')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadCustomers(this.value.trim()), 300);
    });
    
    // Filtro de citas
    document.getElementById('filter-appointment-status')?.addEventListener('change', function() {
        loadAppointments(this.value);
    });
    
    // Filtros de facturas
    document.getElementById('filter-invoice-type')?.addEventListener('change', function() {
        const status = document.getElementById('filter-invoice-status')?.value || '';
        loadInvoices(this.value, status);
    });
    
    document.getElementById('filter-invoice-status')?.addEventListener('change', function() {
        const type = document.getElementById('filter-invoice-type')?.value || '';
        loadInvoices(type, this.value);
    });
    
    // ==========================================
    // UTILIDADES
    // ==========================================
    function showLoadError() {
        document.getElementById('advisory-name').textContent = 'Error al cargar';
        document.getElementById('advisory-name').classList.add('text-danger');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.showNotAvailable = function(action) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'No disponible',
                text: `La función "${action}" no está disponible todavía.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(`La función "${action}" no está disponible todavía.`);
        }
    };
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAdvisoryData);
    } else {
        loadAdvisoryData();
    }
})();
</script>