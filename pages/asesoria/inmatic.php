<?php
$currentPage = 'inmatic';

// Obtener advisory_id y plan
$stmt = $pdo->prepare("SELECT id, plan, razon_social FROM advisories WHERE user_id = ? AND deleted_at IS NULL");
$stmt->execute([USER['id']]);
$advisory = $stmt->fetch();

if (!$advisory) {
    echo '<div class="alert alert-danger m-5">Asesoría no encontrada.</div>';
    return;
}

$advisory_id = $advisory['id'];
$plan = $advisory['plan'];

// Verificar si el plan incluye Inmatic
$planesConInmatic = ['pro', 'premium', 'enterprise'];
$hasInmatic = in_array($plan, $planesConInmatic);

// Obtener configuración actual
$stmt = $pdo->prepare("SELECT * FROM advisory_inmatic_config WHERE advisory_id = ?");
$stmt->execute([$advisory_id]);
$config = $stmt->fetch();

$isConfigured = $config && !empty($config['inmatic_token']);
$isActive = $config && $config['is_active'];

// Obtener estadísticas si está configurado
$stats = null;
$recentInvoices = [];
$suppliers = [];
$customers = [];

if ($isConfigured) {
    // Stats de documentos
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT ai.id) as total_invoices,
            SUM(CASE WHEN aid.id IS NULL THEN 1 ELSE 0 END) as not_sent,
            SUM(CASE WHEN aid.inmatic_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN aid.inmatic_status IN ('processed', 'approved', 'exported') THEN 1 ELSE 0 END) as processed,
            SUM(CASE WHEN aid.inmatic_status = 'error' THEN 1 ELSE 0 END) as error
        FROM advisory_invoices ai
        LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
        WHERE ai.advisory_id = ?
    ");
    $stmt->execute([$advisory_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Clientes sincronizados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_inmatic_customers WHERE advisory_id = ?");
    $stmt->execute([$advisory_id]);
    $customersCount = $stmt->fetchColumn();

    // Proveedores sincronizados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_inmatic_suppliers WHERE advisory_id = ?");
    $stmt->execute([$advisory_id]);
    $suppliersCount = $stmt->fetchColumn();

    // Facturas recientes con estado Inmatic
    $stmt = $pdo->prepare("
        SELECT ai.id, ai.original_name, ai.type, ai.tag, ai.created_at,
               aid.inmatic_status, aid.inmatic_document_id, aid.ocr_data, aid.error_message,
               u.name as customer_name
        FROM advisory_invoices ai
        LEFT JOIN advisory_inmatic_documents aid ON ai.id = aid.advisory_invoice_id
        LEFT JOIN users u ON ai.customer_id = u.id
        WHERE ai.advisory_id = ?
        ORDER BY ai.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$advisory_id]);
    $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Proveedores detectados
    $stmt = $pdo->prepare("
        SELECT * FROM advisory_inmatic_suppliers
        WHERE advisory_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$advisory_id]);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$tagLabels = [
    'restaurante' => 'Restaurante',
    'gasolina' => 'Gasolina',
    'proveedores' => 'Proveedores',
    'material_oficina' => 'Mat. oficina',
    'viajes' => 'Viajes',
    'servicios' => 'Servicios',
    'otros' => 'Otros'
];
?>

<style>
.inmatic-header {
    background: linear-gradient(135deg, #14145B 0%, #050d4c 100%);
    border-radius: 12px;
    padding: 24px;
    color: white;
    margin-bottom: 24px;
}
.inmatic-header .logo-container {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 12px 20px;
    display: inline-flex;
    align-items: center;
}
.inmatic-header .logo-container img {
    height: 28px;
}
.inmatic-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}
.inmatic-status-badge.connected {
    background: rgba(80, 205, 137, 0.2);
    color: #50cd89;
}
.inmatic-status-badge.disconnected {
    background: rgba(255, 199, 0, 0.2);
    color: #ffc700;
}
.stat-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}
.stat-card:hover {
    border-color: #9949FF;
    box-shadow: 0 4px 12px rgba(153, 73, 255, 0.1);
}
.stat-card .stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 8px;
}
.stat-card .stat-label {
    color: #6c757d;
    font-size: 13px;
}
.stat-card.primary .stat-value { color: #9949FF; }
.stat-card.success .stat-value { color: #50cd89; }
.stat-card.warning .stat-value { color: #ffc700; }
.stat-card.danger .stat-value { color: #f1416c; }
.stat-card.info .stat-value { color: #7239ea; }

.invoice-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}
.invoice-status.pending { background: #fff8dd; color: #ffc700; }
.invoice-status.processing { background: #e8f4ff; color: #009ef7; }
.invoice-status.processed { background: #e8fff3; color: #50cd89; }
.invoice-status.error { background: #fff5f8; color: #f1416c; }
.invoice-status.not-sent { background: #f5f5f5; color: #a1a5b7; }

.ocr-data-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
    font-size: 12px;
}
.ocr-data-preview .ocr-field {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    border-bottom: 1px dashed #e9ecef;
}
.ocr-data-preview .ocr-field:last-child {
    border-bottom: none;
}
.ocr-data-preview .ocr-label {
    color: #6c757d;
}
.ocr-data-preview .ocr-value {
    font-weight: 600;
    color: #181c32;
}

.supplier-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 8px;
}
.supplier-item .supplier-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #9949FF 0%, #7239ea 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}
.supplier-item .supplier-info {
    flex: 1;
}
.supplier-item .supplier-name {
    font-weight: 600;
    color: #181c32;
}
.supplier-item .supplier-nif {
    font-size: 12px;
    color: #a1a5b7;
}
</style>

<div id="facilita-app">
    <div class="customers-page">

        <!-- Header con logo Inmatic -->
        <div class="inmatic-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-4">
                    <div class="logo-container">
                        <img src="/assets/media/logos/inmatic-logo.png" alt="Inmatic">
                    </div>
                    <div>
                        <h3 class="mb-1 text-white">Integración con Inmatic</h3>
                        <p class="mb-0 text-white opacity-75">Procesamiento automático de facturas con IA</p>
                    </div>
                </div>
                <?php if ($hasInmatic): ?>
                <div>
                    <?php if ($isConfigured && $isActive): ?>
                    <span class="inmatic-status-badge connected">
                        <i class="ki-outline ki-check-circle"></i>
                        Conectado
                    </span>
                    <?php elseif ($isConfigured): ?>
                    <span class="inmatic-status-badge disconnected">
                        <i class="ki-outline ki-information"></i>
                        Desactivado
                    </span>
                    <?php else: ?>
                    <span class="inmatic-status-badge disconnected">
                        <i class="ki-outline ki-setting-2"></i>
                        Sin configurar
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$hasInmatic): ?>
        <!-- Plan no compatible -->
        <div class="card">
            <div class="card-body text-center py-10">
                <div class="mb-5">
                    <img src="/assets/media/logos/inmatic-logo-color.png" alt="Inmatic" style="height: 50px; opacity: 0.5;">
                </div>
                <h4 class="mb-3">Inmatic no disponible en tu plan</h4>
                <p class="text-muted mb-5">
                    La integración con Inmatic está disponible en los planes <strong>Pro</strong>, <strong>Premium</strong> y <strong>Enterprise</strong>.<br>
                    Tu plan actual es: <strong><?php echo ucfirst($plan); ?></strong>
                </p>
                <a href="/pricing" class="btn btn-primary">
                    <i class="ki-outline ki-arrow-up me-1"></i>
                    Mejorar plan
                </a>
            </div>
        </div>

        <?php else: ?>

        <?php if ($isConfigured && $isActive): ?>
        <!-- Estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md">
                <div class="stat-card primary">
                    <div class="stat-value" id="stat-total"><?php echo $stats['total_invoices'] ?? 0; ?></div>
                    <div class="stat-label">Total facturas</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card success">
                    <div class="stat-value" id="stat-processed"><?php echo $stats['processed'] ?? 0; ?></div>
                    <div class="stat-label">Procesadas</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card warning">
                    <div class="stat-value" id="stat-pending"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card danger">
                    <div class="stat-value" id="stat-errors"><?php echo $stats['error'] ?? 0; ?></div>
                    <div class="stat-label">Con errores</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card info">
                    <div class="stat-value" id="stat-customers"><?php echo $customersCount ?? 0; ?></div>
                    <div class="stat-label">Clientes sync</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card info">
                    <div class="stat-value" id="stat-suppliers"><?php echo $suppliersCount ?? 0; ?></div>
                    <div class="stat-label">Proveedores</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Columna izquierda: Configuración -->
            <div class="col-lg-5">
                <!-- Configuración -->
                <div class="card mb-4">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-0">
                            <i class="ki-outline ki-setting-2 me-2 text-primary"></i>
                            Configuración
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="form-inmatic-config">
                            <!-- Token -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Token de API <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ki-outline ki-key"></i></span>
                                    <input type="password"
                                           class="form-control"
                                           name="inmatic_token"
                                           id="inmatic_token"
                                           placeholder="Introduce tu token de API"
                                           value="<?php echo $isConfigured ? '••••••••••••••••••••••••••••••••' : ''; ?>">
                                    <button type="button" class="btn btn-light" id="btn-toggle-token" title="Mostrar/Ocultar">
                                        <i class="ki-outline ki-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <a href="https://app.inmatic.ai" target="_blank">app.inmatic.ai</a> → Perfil → API
                                </div>
                            </div>

                            <!-- Company ID -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    ID de Empresa <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ki-outline ki-office-bag"></i></span>
                                    <input type="text"
                                           class="form-control"
                                           name="inmatic_company_id"
                                           id="inmatic_company_id"
                                           placeholder="Ej: 94198"
                                           value="<?php echo htmlspecialchars($config['inmatic_company_id'] ?? ''); ?>">
                                </div>
                                <div class="form-text">
                                    El ID aparece en la URL de Inmatic
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary" id="btn-save-config">
                                    <i class="ki-outline ki-check me-1"></i>
                                    Guardar
                                </button>
                                <button type="button" class="btn btn-light-info" id="btn-test-connection">
                                    <i class="ki-outline ki-wifi me-1"></i>
                                    Probar
                                </button>
                                <?php if ($isConfigured && $isActive): ?>
                                <button type="button" class="btn btn-light-danger" id="btn-disable">
                                    <i class="ki-outline ki-cross-circle me-1"></i>
                                    Desactivar
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($isConfigured && count($suppliers) > 0): ?>
                <!-- Proveedores detectados -->
                <div class="card">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-0">
                            <i class="ki-outline ki-shop me-2 text-primary"></i>
                            Proveedores detectados
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($suppliers as $supplier): ?>
                        <div class="supplier-item">
                            <div class="supplier-avatar">
                                <?php echo strtoupper(substr($supplier['name'], 0, 2)); ?>
                            </div>
                            <div class="supplier-info">
                                <div class="supplier-name"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                <div class="supplier-nif"><?php echo htmlspecialchars($supplier['nif_cif'] ?? 'Sin NIF'); ?></div>
                            </div>
                            <?php if ($supplier['inmatic_supplier_id']): ?>
                            <span class="badge badge-light-success">Sync</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Columna derecha: Facturas recientes -->
            <div class="col-lg-7">
                <?php if ($isConfigured): ?>
                <div class="card">
                    <div class="card-header border-0 d-flex align-items-center justify-content-between">
                        <h4 class="card-title mb-0">
                            <i class="ki-outline ki-document me-2 text-primary"></i>
                            Facturas recientes en Inmatic
                        </h4>
                        <a href="/invoices" class="btn btn-sm btn-light">Ver todas</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentInvoices)): ?>
                        <div class="text-center py-10 text-muted">
                            <i class="ki-outline ki-document fs-3x mb-3 d-block opacity-50"></i>
                            No hay facturas aún
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-row-bordered align-middle mb-0">
                                <thead>
                                    <tr class="text-muted fw-semibold">
                                        <th class="ps-4">Factura</th>
                                        <th>Cliente</th>
                                        <th>Estado Inmatic</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentInvoices as $inv): ?>
                                    <?php
                                    $statusClass = 'not-sent';
                                    $statusText = 'No enviada';
                                    if ($inv['inmatic_status']) {
                                        switch ($inv['inmatic_status']) {
                                            case 'pending': $statusClass = 'pending'; $statusText = 'Pendiente'; break;
                                            case 'processing': $statusClass = 'processing'; $statusText = 'Procesando'; break;
                                            case 'processed':
                                            case 'approved':
                                            case 'exported': $statusClass = 'processed'; $statusText = 'Procesada'; break;
                                            case 'error': $statusClass = 'error'; $statusText = 'Error'; break;
                                            default: $statusText = ucfirst($inv['inmatic_status']);
                                        }
                                    }
                                    $ocrData = $inv['ocr_data'] ? json_decode($inv['ocr_data'], true) : null;
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ki-outline ki-<?php echo $inv['type'] === 'ingreso' ? 'arrow-up text-success' : 'arrow-down text-danger'; ?>"></i>
                                                <div>
                                                    <div class="fw-semibold text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($inv['original_name']); ?>">
                                                        <?php echo htmlspecialchars($inv['original_name']); ?>
                                                    </div>
                                                    <div class="text-muted fs-7">
                                                        <?php echo $tagLabels[$inv['tag']] ?? $inv['tag']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-gray-700"><?php echo htmlspecialchars($inv['customer_name'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <span class="invoice-status <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                            <?php if ($ocrData && isset($ocrData['total'])): ?>
                                            <div class="text-success fw-semibold fs-7 mt-1">
                                                <?php echo number_format($ocrData['total'], 2, ',', '.'); ?> &euro;
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-1">
                                                <?php if ($ocrData): ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-light-info"
                                                        onclick="showOcrData(<?php echo $inv['id']; ?>)" title="Ver datos OCR">
                                                    <i class="ki-outline ki-eye"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (!$inv['inmatic_document_id']): ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-light-primary btn-send-inmatic"
                                                        data-id="<?php echo $inv['id']; ?>" title="Enviar a Inmatic">
                                                    <i class="ki-outline ki-send"></i>
                                                </button>
                                                <?php elseif ($inv['inmatic_status'] === 'error'): ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-light-warning btn-resend-inmatic"
                                                        data-id="<?php echo $inv['id']; ?>" title="Reintentar">
                                                    <i class="ki-outline ki-arrows-circle"></i>
                                                </button>
                                                <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-light-success btn-sync-inmatic"
                                                        data-id="<?php echo $inv['id']; ?>" title="Sincronizar estado">
                                                    <i class="ki-outline ki-arrows-circle"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($inv['inmatic_status'] === 'error' && $inv['error_message']): ?>
                                    <tr>
                                        <td colspan="4" class="ps-4 pe-4 py-2 bg-light-danger">
                                            <small class="text-danger">
                                                <i class="ki-outline ki-information-2 me-1"></i>
                                                <?php echo htmlspecialchars($inv['error_message']); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Info para no configurados -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-4">
                            <div class="flex-shrink-0">
                                <img src="/assets/media/logos/inmatic-logo-color.png" alt="Inmatic" style="height: 40px;">
                            </div>
                            <div>
                                <h5 class="mb-2">¿Qué es Inmatic?</h5>
                                <p class="text-muted mb-3">
                                    Inmatic es un servicio de procesamiento de facturas con IA que extrae automáticamente
                                    los datos de tus facturas: importes, fechas, CIFs, conceptos y más.
                                </p>
                                <h6 class="mb-2">Al conectar Inmatic:</h6>
                                <ul class="text-muted mb-0">
                                    <li>Las facturas de tus clientes se envían automáticamente</li>
                                    <li>Se extraen datos con OCR e Inteligencia Artificial</li>
                                    <li>Los proveedores se detectan y sincronizan</li>
                                    <li>Recibes notificaciones cuando se procesan</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<!-- Modal OCR Data -->
<div class="modal fade" id="modal-ocr-data" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ki-outline ki-scan-barcode me-2 text-primary"></i>
                    Datos extraídos por OCR
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ocr-data-content">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var hasInmatic = <?php echo $hasInmatic ? 'true' : 'false'; ?>;
    var isConfigured = <?php echo $isConfigured ? 'true' : 'false'; ?>;

    if (!hasInmatic) return;

    var form = document.getElementById('form-inmatic-config');
    var tokenInput = document.getElementById('inmatic_token');
    var companyIdInput = document.getElementById('inmatic_company_id');
    var btnSave = document.getElementById('btn-save-config');
    var btnTest = document.getElementById('btn-test-connection');
    var btnDisable = document.getElementById('btn-disable');
    var btnToggleToken = document.getElementById('btn-toggle-token');

    var tokenChanged = false;

    // Toggle token visibility
    if (btnToggleToken) {
        btnToggleToken.addEventListener('click', function() {
            var icon = this.querySelector('i');
            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                icon.classList.remove('ki-eye');
                icon.classList.add('ki-eye-slash');
            } else {
                tokenInput.type = 'password';
                icon.classList.remove('ki-eye-slash');
                icon.classList.add('ki-eye');
            }
        });
    }

    tokenInput.addEventListener('input', function() {
        tokenChanged = true;
    });

    // Save config
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var token = tokenInput.value.trim();
        var companyId = companyIdInput.value.trim();

        if (!tokenChanged && token === '••••••••••••••••••••••••••••••••') {
            token = '';
        }

        if (!token && !isConfigured) {
            Swal.fire({ icon: 'warning', title: 'Token requerido', text: 'Introduce tu token de API de Inmatic' });
            return;
        }

        if (!companyId) {
            Swal.fire({ icon: 'warning', title: 'ID de empresa requerido', text: 'Introduce el ID de tu empresa en Inmatic' });
            return;
        }

        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        var formData = new FormData();
        formData.append('action', 'save');
        if (token && tokenChanged) {
            formData.append('inmatic_token', token);
        }
        formData.append('inmatic_company_id', companyId);

        fetch('/api/advisory-inmatic-config', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Configuración guardada',
                    text: result.data && result.data.webhook_configured ? 'Webhook configurado automáticamente' : '',
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: result.message });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        })
        .finally(function() {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="ki-outline ki-check me-1"></i>Guardar';
        });
    });

    // Test connection
    btnTest.addEventListener('click', function() {
        btnTest.disabled = true;
        btnTest.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Probando...';

        var formData = new FormData();
        formData.append('action', 'test');

        fetch('/api/advisory-inmatic-config', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.status === 'ok') {
                Swal.fire({ icon: 'success', title: 'Conexión exitosa', text: 'La conexión con Inmatic funciona correctamente' });
            } else {
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: result.message });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        })
        .finally(function() {
            btnTest.disabled = false;
            btnTest.innerHTML = '<i class="ki-outline ki-wifi me-1"></i>Probar';
        });
    });

    // Disable
    if (btnDisable) {
        btnDisable.addEventListener('click', function() {
            Swal.fire({
                title: '¿Desactivar integración?',
                text: 'Las facturas dejarán de enviarse a Inmatic automáticamente',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append('action', 'disable');

                    fetch('/api/advisory-inmatic-config', { method: 'POST', body: formData })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        if (result.status === 'ok') {
                            Swal.fire({ icon: 'success', title: 'Desactivado', timer: 1500, showConfirmButton: false })
                            .then(function() { window.location.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                        }
                    });
                }
            });
        });
    }

    // Send to Inmatic
    document.querySelectorAll('.btn-send-inmatic, .btn-resend-inmatic').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var invoiceId = this.dataset.id;
            var btnEl = this;
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            var formData = new FormData();
            formData.append('invoice_id', invoiceId);

            fetch('/api/advisory-invoice-send-to-inmatic', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Enviada', text: 'Factura enviada a Inmatic', timer: 1500, showConfirmButton: false })
                    .then(function() { window.location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<i class="ki-outline ki-send"></i>';
                }
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
                btnEl.disabled = false;
                btnEl.innerHTML = '<i class="ki-outline ki-send"></i>';
            });
        });
    });

    // Sync from Inmatic
    document.querySelectorAll('.btn-sync-inmatic').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var invoiceId = this.dataset.id;
            var btnEl = this;
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('/api/advisory-invoice-sync-inmatic?invoice_id=' + invoiceId)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok') {
                    Swal.fire({ icon: 'success', title: 'Sincronizado', timer: 1500, showConfirmButton: false })
                    .then(function() { window.location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                }
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
            })
            .finally(function() {
                btnEl.disabled = false;
                btnEl.innerHTML = '<i class="ki-outline ki-arrows-circle"></i>';
            });
        });
    });

    // Show OCR Data
    window.showOcrData = function(invoiceId) {
        var modal = new bootstrap.Modal(document.getElementById('modal-ocr-data'));
        var content = document.getElementById('ocr-data-content');
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modal.show();

        fetch('/api/advisory-invoice-sync-inmatic?invoice_id=' + invoiceId)
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.status === 'ok' && result.data) {
                var ocr = result.data.ocr_formatted || result.data.ocr_data || {};
                var html = '<div class="ocr-data-preview">';

                var labels = {
                    'emisor': 'Emisor',
                    'emisor_cif': 'CIF Emisor',
                    'receptor': 'Receptor',
                    'receptor_cif': 'CIF Receptor',
                    'numero_factura': 'Nº Factura',
                    'fecha': 'Fecha',
                    'base_imponible': 'Base imponible',
                    'iva': 'IVA',
                    'total': 'Total',
                    'concepto': 'Concepto'
                };

                for (var key in ocr) {
                    if (ocr[key]) {
                        var label = labels[key] || key;
                        var value = ocr[key];
                        if (key === 'total' || key === 'base_imponible' || key === 'iva') {
                            value = parseFloat(value).toLocaleString('es-ES', {minimumFractionDigits: 2}) + ' €';
                        }
                        html += '<div class="ocr-field"><span class="ocr-label">' + label + '</span><span class="ocr-value">' + value + '</span></div>';
                    }
                }

                if (Object.keys(ocr).length === 0) {
                    html += '<div class="text-center text-muted py-3">No hay datos OCR disponibles</div>';
                }

                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="text-center text-muted py-5">No se pudieron cargar los datos</div>';
            }
        })
        .catch(function() {
            content.innerHTML = '<div class="text-center text-danger py-5">Error de conexión</div>';
        });
    };

})();
</script>
