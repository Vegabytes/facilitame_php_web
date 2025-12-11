<?php
$currentPage = 'advisory-customers';

// Obtener código de la asesoría
$advisory_code = '';
$query = "SELECT codigo_identificacion FROM advisories WHERE user_id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(":user_id", USER["id"]);
$stmt->execute();
$result = $stmt->fetch();
if ($result) {
    $advisory_code = $result["codigo_identificacion"];
}

$ROLES = [
    'autonomo' => 'Autónomo',
    'particular' => 'Particular',
    'empresa' => 'Empresa',
    'comunidad' => 'Comunidad',
    'asociacion' => 'Asociación'
];
?>

<div id="facilita-app">
    <div class="dashboard-asesoria-home">
        
        <!-- Code Card -->
        <div class="card code-card-compact">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-3">
                        <div class="code-icon">
                            <i class="ki-outline ki-share fs-2"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-gray-800">Tu Código de Asesoría</div>
                            <div class="text-muted fs-7">Comparte este código con tus clientes</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control code-input" value="<?php secho($advisory_code); ?>" id="advisory-code-input" readonly>
                        <button class="btn btn-primary btn-sm" type="button" onclick="copyCode()" title="Copiar código">
                            <i class="ki-outline ki-copy"></i>
                        </button>
                        <button class="btn btn-light btn-sm" type="button" onclick="copyLink()" title="Copiar link de registro">
                            <i class="ki-outline ki-exit-right-corner me-1"></i>Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card principal clientes -->
        <div class="card dashboard-tabs-card">
            
            <!-- Controles -->
            <div class="list-controls">
                <div class="results-info">
                    <span id="clients-results-count">Cargando...</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="pagination-size">
                        <label for="clients-page-size">Mostrar:</label>
                        <select id="clients-page-size" class="form-select form-select-sm">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modal_create_customer">
                        <i class="ki-outline ki-plus fs-4 me-1"></i>Nuevo Cliente
                    </button>
                </div>
            </div>
            
            <!-- Listado -->
            <div class="card-body">
                <div class="tab-list-container" id="clients-list">
                    <div class="loading-state">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Cargando clientes...</span>
                    </div>
                </div>
            </div>
            
            <!-- Paginador -->
            <div class="pagination-container" id="clients-pagination" style="display: none;">
                <div class="pagination-info" id="clients-page-info">Página 1 de 1</div>
                <div class="pagination-nav">
                    <button class="btn-pagination" id="clients-prev" disabled>
                        <i class="ki-outline ki-left"></i>
                    </button>
                    <span class="pagination-current" id="clients-page-current">1 / 1</span>
                    <button class="btn-pagination" id="clients-next" disabled>
                        <i class="ki-outline ki-right"></i>
                    </button>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<input type="hidden" id="advisory-link-input" value="<?php echo ROOT_URL; ?>/sign-up?advisory=<?php secho($advisory_code); ?>">

<script>
function copyCode() {
    var input = document.getElementById('advisory-code-input');
    navigator.clipboard.writeText(input.value).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Código copiado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    });
}

function copyLink() {
    navigator.clipboard.writeText(document.getElementById('advisory-link-input').value).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Link copiado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    });
}

(function() {
    'use strict';
    
    var API_URL = '/api-advisory-clients-paginated';
    
    var ROLES = <?php echo json_encode($ROLES); ?>;
    
    var state = {
        currentPage: 1,
        pageSize: 25,
        searchQuery: '',
        totalPages: 1,
        totalRecords: 0,
        isLoading: false
    };
    
    var listContainer = document.getElementById('clients-list');
    var resultsCount = document.getElementById('clients-results-count');
    var pageInfo = document.getElementById('clients-page-info');
    var pageCurrent = document.getElementById('clients-page-current');
    var prevBtn = document.getElementById('clients-prev');
    var nextBtn = document.getElementById('clients-next');
    var pageSizeSelect = document.getElementById('clients-page-size');
    var paginationContainer = document.getElementById('clients-pagination');
    
    var searchTimeout = null;
    
    function init() {
        prevBtn.addEventListener('click', function() { goToPage(state.currentPage - 1); });
        nextBtn.addEventListener('click', function() { goToPage(state.currentPage + 1); });
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        loadData();
    }
    
    function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();
        
        var params = new URLSearchParams({
            page: state.currentPage,
            limit: state.pageSize,
            search: state.searchQuery
        });
        
        fetch(API_URL + '?' + params)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.status === 'ok' && result.data) {
                    var pagination = result.data.pagination;
                    state.totalPages = pagination.total_pages;
                    state.totalRecords = pagination.total_records;
                    renderList(result.data.data || []);
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
            var title = state.searchQuery ? 'Sin resultados' : 'No hay clientes';
            var msg = state.searchQuery 
                ? 'No se encontraron resultados para "' + escapeHtml(state.searchQuery) + '"'
                : 'Comparte tu código para que tus clientes se registren';
            
            listContainer.innerHTML = 
                '<div class="empty-state">' +
                    '<div class="empty-state-icon"><i class="ki-outline ki-people"></i></div>' +
                    '<div class="empty-state-title">' + title + '</div>' +
                    '<p class="empty-state-text">' + msg + '</p>' +
                '</div>';
            paginationContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        data.forEach(function(client) {
            var fullName = ((client.name || '') + ' ' + (client.lastname || '')).trim() || 'Sin nombre';
            var roleName = ROLES[client.role_name] || client.role_name || 'Cliente';
            var hasPhone = client.phone && client.phone.trim() !== '';
            var isVerified = !!client.email_verified_at;
            var servicesCount = client.services_number || 0;
            
            html += '<div class="list-card list-card-primary">' +
                '<div class="list-card-content">' +
                    '<div class="list-card-title">' +
                        '<span class="badge-status badge-status-neutral">#' + client.id + '</span>' +
                        '<a href="/customer?id=' + client.id + '" class="list-card-customer">' + escapeHtml(fullName) + '</a>' +
                        '<span class="badge-status badge-status-info">' + escapeHtml(roleName) + '</span>' +
                        (isVerified
                            ? '<span class="badge-status badge-status-success">Verificado</span>'
                            : '<span class="badge-status badge-status-warning">Pendiente</span>') +
                    '</div>' +
                    '<div class="list-card-meta">' +
                        '<span><i class="ki-outline ki-sms"></i> ' + escapeHtml(client.email) + '</span>' +
                        (hasPhone ? '<span><i class="ki-outline ki-phone"></i> ' + formatPhone(client.phone) + '</span>' : '') +
                        '<span><i class="ki-outline ki-calendar"></i> ' + formatDate(client.created_at) + '</span>' +
                        '<span><i class="ki-outline ki-folder"></i> ' + servicesCount + ' servicio' + (servicesCount !== 1 ? 's' : '') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="list-card-actions">' +
                    '<a href="/customer?id=' + client.id + '" class="btn-icon btn-icon-info" title="Ver perfil"><i class="ki-outline ki-eye"></i></a>' +
                '</div>' +
            '</div>';
        });
        
        listContainer.innerHTML = html;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
        listContainer.scrollTop = 0;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    
    function formatPhone(phone) {
        if (!phone) return '';
        var cleaned = phone.replace(/\s/g, '');
        if (cleaned.length === 9) {
            return cleaned.slice(0, 3) + ' ' + cleaned.slice(3, 6) + ' ' + cleaned.slice(6);
        }
        return phone;
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= state.totalPages && page !== state.currentPage) {
            state.currentPage = page;
            loadData();
        }
    }
    
    function handlePageSizeChange(e) {
        state.pageSize = parseInt(e.target.value, 10);
        state.currentPage = 1;
        loadData();
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
    
    function showLoading() {
        listContainer.innerHTML = 
            '<div class="loading-state">' +
                '<div class="spinner-border spinner-border-sm text-primary"></div>' +
                '<span class="ms-2">Cargando clientes...</span>' +
            '</div>';
    }
    
    function showError(msg) {
        listContainer.innerHTML = 
            '<div class="empty-state">' +
                '<div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>' +
                '<div class="empty-state-title">Error al cargar</div>' +
                '<p class="empty-state-text">' + escapeHtml(msg) + '</p>' +
                '<button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadClients()">' +
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
    window.filterCustomers = function(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            state.searchQuery = query.trim();
            state.currentPage = 1;
            loadData();
        }, 300);
    };
    
    // Alias
    window.filterAdvisoryCustomers = window.filterCustomers;
    window.reloadClients = function() { loadData(); };
    window.reloadAdvisoryCustomers = window.reloadClients;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php require ROOT_DIR . "/partials/modals/modal-advisory-new-customer.php"; ?>