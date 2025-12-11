<?php $scripts = []; ?>

<div class="customer-detail-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">
    
    <div class="customer-detail-layout" style="flex: 1; display: flex; gap: 1.5rem; min-height: 0;">
        
        <!-- SIDEBAR CLIENTE -->
        <aside class="customer-sidebar" style="width: 320px; flex-shrink: 0; display: flex; flex-direction: column;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column;">
                <div class="card-body" style="flex: 1; overflow-y: auto;">
                    
                    <!-- Avatar y datos principales -->
                    <div class="customer-profile">
                        <div class="customer-avatar">
                            <?php if ($customer["profile_picture"] != "") : ?>
                                <img src="<?php echo MEDIA_DIR . "/" . $customer["profile_picture"] ?>"
                                     alt="Foto de perfil" loading="lazy">
                            <?php else : ?>
                                <img src="assets/media/bold/profile-default.jpg"
                                     alt="Foto de perfil por defecto" loading="lazy">
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="customer-name">
                            <?php echo $customer["name"] . " " . $customer["lastname"]; ?>
                        </h3>
                        
                        <span class="badge-status badge-status-info">
                            <i class="ki-outline ki-profile-circle"></i>
                            <?php echo display_role($customer["role_name"]); ?>
                        </span>
                        
                        <div class="customer-stat">
                            <span class="customer-stat-value"><?php echo $customer["services_number"]; ?></span>
                            <span class="customer-stat-label">Servicios activos</span>
                        </div>
                    </div>
                    
                    <hr class="customer-divider">
                    
                    <!-- Información del cliente -->
                    <div class="customer-info-section">
                        <h6 class="customer-info-title">
                            <i class="ki-outline ki-information-5"></i>
                            Información del cliente
                        </h6>
                        
                        <dl class="customer-details">
                            
                            <!-- Estado de cuenta -->
                            <div class="customer-detail-row">
                                <dt>Estado de cuenta</dt>
                                <dd>
                                    <?php if (($customer["is_premium"] ?? "0") == "1") : ?>
                                        <span class="badge-status badge-status-warning">
                                            <i class="ki-outline ki-crown"></i>
                                            Premium
                                        </span>
                                    <?php else : ?>
                                        <span class="badge-status badge-status-success">
                                            <i class="ki-outline ki-check-circle"></i>
                                            Estándar
                                        </span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            
                            <!-- ID Usuario -->
                            <div class="customer-detail-row">
                                <dt>ID de usuario</dt>
                                <dd>
                                    <span class="badge-status badge-status-primary">#<?php echo $customer["id"]; ?></span>
                                </dd>
                            </div>
                            
                            <!-- Email -->
                            <div class="customer-detail-row">
                                <dt>Email</dt>
                                <dd>
                                    <a href="mailto:<?php secho($customer['email']); ?>" class="customer-email">
                                        <?php secho($customer["email"]); ?>
                                    </a>
                                </dd>
                            </div>
                            
                            <!-- Comercial asignado -->
                            <div class="customer-detail-row">
                                <dt>Comercial asignado</dt>
                                <dd>
                                    <?php if (!empty($customer["sales_rep_name"])) : ?>
                                        <i class="ki-outline ki-user-tick text-success"></i>
                                        <?php echo $customer["sales_rep_name"]; ?>
                                    <?php else : ?>
                                        <span class="text-muted">Sin asignar</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            
                            <!-- Acceso a facturas -->
                            <div class="customer-detail-row">
                                <dt>Acceso a facturas</dt>
                                <dd>
                                    <?php if ($customer["allow_invoice_access"] == "1") : ?>
                                        <span class="badge-status badge-status-success">
                                            <i class="ki-outline ki-check-circle"></i>
                                            Autorizado
                                        </span>
                                        <small class="customer-detail-note">
                                            Desde <?php echo fdate($customer["allow_invoice_access_granted_at"]); ?>
                                        </small>
                                    <?php else : ?>
                                        <span class="badge-status badge-status-danger">
                                            <i class="ki-outline ki-cross-circle"></i>
                                            No autorizado
                                        </span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            
                        </dl>
                    </div>
                    
                </div>
            </div>
        </aside>
        <!-- /SIDEBAR CLIENTE -->
        
        <!-- CONTENIDO PRINCIPAL: SOLICITUDES -->
        <main class="customer-main" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                
                <!-- Controles -->
                <div class="list-controls" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border); border-radius: var(--f-radius) var(--f-radius) 0 0;">
                    <div class="results-info">
                        <span id="customer-requests-count"><?php echo count($requests); ?> solicitudes</span>
                    </div>
                </div>
                
                <!-- Body con lista de solicitudes -->
                <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
                    <div class="tab-list-container" id="customer-requests-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                        <?php if (empty($requests)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="ki-outline ki-folder"></i>
                                </div>
                                <div class="empty-state-title">No hay solicitudes</div>
                                <p class="empty-state-text">Este cliente aún no tiene solicitudes registradas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php if ($request["status_id"] == 8) continue; ?>
                                <?php 
                                    $request_date = is_null($request["request_date"]) ? "-" : fdate($request["request_date"]);
                                    $updated_at   = is_null($request["updated_at"])   ? "-" : fdate($request["updated_at"]);
                                    
                                    // Mapeo status_id → clase
                                    $statusMap = [
                                        1 => 'primary',   // Iniciado
                                        2 => 'info',      // Oferta disponible
                                        3 => 'success',   // Aceptada
                                        4 => 'info',      // En curso
                                        5 => 'danger',    // Rechazada
                                        6 => 'warning',   // Sin respuesta
                                        7 => 'success',   // Activada
                                        8 => 'warning',   // Revisión
                                        9 => 'muted',     // Eliminada
                                        10 => 'warning',  // Aplazada
                                        11 => 'muted'     // Desactivada
                                    ];
                                    $statusClass = $statusMap[$request["status_id"]] ?? 'muted';
                                ?>
                                <div class="list-card list-card-<?php echo $statusClass; ?>">
                                    <div class="list-card-content">
                                        <div class="list-card-title">
                                            <a href="request?id=<?php echo $request['id']; ?>" class="list-card-customer">
                                                Solicitud #<?php echo $request['id']; ?>
                                            </a>
                                            <span class="text-muted">›</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($request['category_name']); ?></span>
                                        </div>
                                        <div class="list-card-meta">
                                            <span>
                                                <i class="ki-outline ki-calendar"></i>
                                                <?php echo $request_date; ?>
                                            </span>
                                            <span>
                                                <i class="ki-outline ki-time"></i>
                                                <?php echo $updated_at; ?>
                                            </span>
                                            <span class="badge-status badge-status-<?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($request["status"]); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="list-card-actions">
                                        <a href="request?id=<?php echo $request['id']; ?>" 
                                           class="btn-icon" 
                                           title="Ver solicitud">
                                            <i class="ki-outline ki-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </main>
        <!-- /CONTENIDO PRINCIPAL -->
        
    </div>
    
</div>

<script>
// Función de filtrado conectada al header
window.filterCustomerRequests = function(query) {
    query = normalizeText(query.toLowerCase().trim());
    const cards = document.querySelectorAll('#customer-requests-list .list-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const text = normalizeText(card.textContent.toLowerCase());
        const isVisible = text.includes(query);
        card.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    // Actualizar contador
    const countEl = document.getElementById('customer-requests-count');
    if (countEl) {
        countEl.textContent = query 
            ? `${visibleCount} de <?php echo count($requests); ?> solicitudes`
            : `<?php echo count($requests); ?> solicitudes`;
    }

    showNoResultsCustomer(visibleCount === 0 && query.length > 0);
};

function normalizeText(text) {
    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function showNoResultsCustomer(show) {
    let noResults = document.getElementById('no-results-customer');

    if (show && !noResults) {
        noResults = document.createElement('div');
        noResults.id = 'no-results-customer';
        noResults.className = 'empty-state';
        noResults.innerHTML = `
            <div class="empty-state-icon"><i class="ki-outline ki-magnifier"></i></div>
            <div class="empty-state-title">Sin resultados</div>
            <p class="empty-state-text">Intenta con otros términos de búsqueda</p>
        `;
        document.getElementById('customer-requests-list').appendChild(noResults);
    } else if (!show && noResults) {
        noResults.remove();
    }
}
</script>