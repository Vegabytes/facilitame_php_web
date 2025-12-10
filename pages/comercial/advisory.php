<?php
/**
 * Detalle de Asesoría - Panel Comercial
 * /pages/comercial/advisory.php
 */
$scripts = [];

$advisory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$advisory_id) {
    header('Location: /');
    exit;
}

// Verificar que esta asesoría pertenece al comercial
global $pdo;
$salesUserId = USER['id'];

$stmt = $pdo->prepare("
    SELECT a.id 
    FROM advisories a
    INNER JOIN advisories_sales_codes adv_sc ON a.id = adv_sc.advisory_id
    INNER JOIN sales_codes sc ON sc.id = adv_sc.sales_code_id
    WHERE a.id = ? AND sc.user_id = ?
");
$stmt->execute([$advisory_id, $salesUserId]);
if (!$stmt->fetch()) {
    header('Location: /');
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
                            <i class="ki-duotone ki-briefcase fs-3x text-primary">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
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
                            Usuario Responsable
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
                                <dt>Total citas</dt>
                                <dd id="stat-total-appointments">0</dd>
                            </div>
                        </dl>
                    </div>
                    
                </div>
                
                <div class="card-footer" style="flex-shrink: 0; padding: 1rem; border-top: 1px solid var(--f-border);">
                    <a href="/" class="btn btn-sm btn-light-primary w-100">
                        <i class="ki-outline ki-arrow-left me-1"></i>
                        Volver al panel
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- CONTENIDO PRINCIPAL - CLIENTES -->
        <main class="advisory-main" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                
                <div class="card-header" style="flex-shrink: 0;">
                    <h5 class="card-title mb-0">
                        <i class="ki-outline ki-people me-2"></i>
                        Clientes de esta asesoría
                    </h5>
                </div>
                
                <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                    
                    <!-- Buscador -->
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--f-border);">
                        <div class="search-box">
                            <i class="ki-outline ki-magnifier"></i>
                            <input type="text" class="form-control" placeholder="Buscar clientes..." id="search-customers">
                        </div>
                    </div>
                    
                    <!-- Listado -->
                    <div class="tab-list-container" id="customers-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                        <div class="loading-state">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <span class="ms-2">Cargando clientes...</span>
                        </div>
                    </div>
                    
                    <!-- Info de resultados -->
                    <div style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--f-border); background: var(--f-bg-soft);">
                        <span class="text-muted" id="customers-count">0 clientes</span>
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
    
    // ==========================================
    // CARGA INICIAL
    // ==========================================
    async function loadAdvisoryData() {
        try {
            const response = await fetch('/api/salesrep-advisory-detail?id=' + ADVISORY_ID);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderAdvisoryInfo(result.data);
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
        document.getElementById('stat-total-appointments').textContent = stats.total_appointments || 0;
    }
    
    // ==========================================
    // CARGAR CLIENTES
    // ==========================================
    async function loadCustomers(search = '') {
        const container = document.getElementById('customers-list');
        container.innerHTML = '<div class="loading-state"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2">Cargando...</span></div>';
        
        try {
            const params = new URLSearchParams({ advisory_id: ADVISORY_ID, limit: 100 });
            if (search) params.append('search', search);
            
            const response = await fetch('/api/salesrep-advisory-clients?' + params);
            const result = await response.json();
            
            if (result.status === 'ok' && result.data) {
                renderCustomers(result.data.data || []);
                document.getElementById('customers-count').textContent = 
                    `${result.data.pagination.total_records} cliente${result.data.pagination.total_records !== 1 ? 's' : ''}`;
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
                    <div class="empty-state-title">Sin clientes</div>
                    <p class="empty-state-text">Esta asesoría no tiene clientes asignados</p>
                </div>`;
            return;
        }
        
        container.innerHTML = customers.map(c => `
            <div class="list-card list-card-info">
                <div class="list-card-content">
                    <div class="list-card-title">
                        <span class="list-card-link">${escapeHtml(c.name)}</span>
                        ${c.email_verified_at 
                            ? '<span class="badge-status badge-status-success">Verificado</span>' 
                            : '<span class="badge-status badge-status-warning">Pendiente</span>'}
                    </div>
                    <div class="list-card-meta">
                        <span><i class="ki-outline ki-sms"></i>${escapeHtml(c.email)}</span>
                        ${c.phone ? `<span><i class="ki-outline ki-phone"></i>${escapeHtml(c.phone)}</span>` : ''}
                        ${c.nif_cif ? `<span><i class="ki-outline ki-document"></i>${escapeHtml(c.nif_cif)}</span>` : ''}
                        <span><i class="ki-outline ki-calendar"></i>${formatDate(c.created_at)}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // ==========================================
    // EVENTOS
    // ==========================================
    let searchTimeout;
    document.getElementById('search-customers')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadCustomers(this.value.trim()), 300);
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
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    
    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAdvisoryData);
    } else {
        loadAdvisoryData();
    }
})();
</script>