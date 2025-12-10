<div class="card card-flush h-xl-100 pb-5">
    <div class="card-header pt-7">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-800 mb-5">
                <i class="ki-outline ki-profile-user fs-2 text-facilitame me-2"></i>
                Clientes
            </span>
            <!--begin::Search-->
            <div class="search-wrapper-facilitame">
                <i class="ki-outline ki-magnifier"></i>
                <input 
                    type="text" 
                    data-dt-clientes-filter-filter="search"
                    class="search-input-facilitame" 
                    placeholder="Buscar clientes..." 
                />
            </div>
            <!--end::Search-->
        </h3>
    </div>

    <div class="card-body pt-2 card-scroll" style="height:65vh">
        <?php if (empty($customers)): ?>
            <div class="empty-state-modern">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-profile-user"></i>
                </div>
                <h4 class="empty-state-title">No hay clientes</h4>
                <p class="empty-state-text">No se encontraron clientes registrados</p>
            </div>
        <?php else: ?>
            <table class="table table-modern align-middle table-row-dashed fs-6 gy-3" id="dt-clientes">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="ps-4">ID</th>
                        <th class="">Cliente</th>
                        <th class="min-w-150px">Email</th>
                        <th class="min-w-100px">Teléfono</th>
                        <th class="text-end pe-4 min-w-70px">Acciones</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    <?php foreach ($customers as $customer) : ?>
                        <tr class="table-row-modern" data-customer-name="<?php echo $customer["name"] . " " . $customer["lastname"] ?>">
                            <td class="ps-4">
                                <span class="badge badge-modern badge-modern-light">#<?php echo $customer["id"] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="customer-avatar">
                                        <span class="customer-avatar-text">
                                            <?php echo strtoupper(substr($customer["name"], 0, 1) . substr($customer["lastname"], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="ms-3">
                                        <a href="#" data-customer-id="<?php echo $customer["id"] ?>" class="customer-get-info customer-name-link">
                                            <?php echo ucwords($customer["name"] . " " . $customer["lastname"]) ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo $customer["email"]; ?>" class="link-modern">
                                    <i class="ki-outline ki-sms fs-6 me-1"></i>
                                    <?php secho($customer["email"]) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($customer["phone"])): ?>
                                    <a href="tel:<?php echo $customer["phone"]; ?>" class="link-modern">
                                        <i class="ki-outline ki-phone fs-6 me-1"></i>
                                        <?php echo phone($customer["phone"]) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm-facilitame btn-secondary-facilitame" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Acciones
                                        <i class="ki-outline ki-down fs-5 ms-1"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="/customer?id=<?php echo $customer["id"] ?>&r=<?php echo htmlspecialchars($_GET['r'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="ki-outline ki-eye fs-5 me-2"></i>
                                                Ver perfil
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-customer-id="<?php echo $customer["id"] ?>" class="customer-get-info">
                                                <i class="ki-outline ki-folder fs-5 me-2"></i>
                                                Ver solicitudes
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
/* ============================================
   TABLA MODERNA
   ============================================ */

.table-modern {
    margin-bottom: 0;
}

.table-modern thead tr {
    border-bottom: 2px solid #e2e8f0;
}

.table-modern thead th {
    padding: 1rem 0.75rem;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border-bottom: none;
}

.table-row-modern {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.table-row-modern:hover {
    background: #f8fafc;
    transform: translateX(2px);
}

.table-row-modern td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

/* Avatar del cliente */
.customer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--color-main-facilitame) 0%, var(--color-main-facilitame-active) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.customer-avatar-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    letter-spacing: 0.5px;
}

.customer-name-link {
    color: #1e293b;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: color 0.2s ease;
}

.customer-name-link:hover {
    color: var(--color-main-facilitame);
}

/* Links modernos */
.link-modern {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.link-modern:hover {
    color: var(--color-main-facilitame);
}

/* Badge moderno para ID */
.badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-modern-light {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
}

/* ============================================
   BUSCADOR FACILITAME
   ============================================ */

.search-wrapper-facilitame {
    position: relative;
    width: 100%;
    max-width: 350px;
}

.search-wrapper-facilitame i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    pointer-events: none;
}

.search-input-facilitame {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    background: white;
}

.search-input-facilitame:focus {
    outline: none;
    border-color: var(--color-main-facilitame);
    box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.1);
}

.search-input-facilitame::placeholder {
    color: #94a3b8;
}

/* ============================================
   EMPTY STATE MODERNO
   ============================================ */

.empty-state-modern {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    min-height: 400px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    position: relative;
    animation: floatSoft 3s ease-in-out infinite;
}

.empty-state-icon i {
    font-size: 2.5rem;
    color: var(--color-main-facilitame);
}

.empty-state-icon::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(0, 194, 203, 0.1);
    animation: pulseSoft 2s ease-in-out infinite;
}

@keyframes floatSoft {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

@keyframes pulseSoft {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.08); opacity: 0.5; }
}

.empty-state-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.empty-state-text {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* ============================================
   DROPDOWN MEJORADO
   ============================================ */

.dropdown-menu {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    padding: 0.5rem 0;
}

.dropdown-item {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.dropdown-item:hover {
    background: #f8fafc;
    color: var(--color-main-facilitame);
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: #e2e8f0;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .table-modern {
        font-size: 0.8125rem;
    }
    
    .customer-avatar {
        width: 32px;
        height: 32px;
    }
    
    .customer-avatar-text {
        font-size: 0.75rem;
    }
    
    .table-row-modern td {
        padding: 0.75rem 0.5rem;
    }
    
    /* Ocultar columna de teléfono en móvil */
    .table-modern thead th:nth-child(4),
    .table-modern tbody td:nth-child(4) {
        display: none;
    }
}
</style>