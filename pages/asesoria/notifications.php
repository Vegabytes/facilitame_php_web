<?php
$scripts = [];
?>

<div class="logs-page" style="height: calc(100vh - 160px); display: flex; flex-direction: column;">

    <div class="card" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">

        <!-- Controles -->
        <div class="list-controls" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--f-border); border-radius: var(--f-radius) var(--f-radius) 0 0;">
            <div class="results-info">
                <span id="notifications-page-results-count">Cargando...</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label fs-7 mb-0 text-nowrap">Estado:</label>
                    <select id="notifications-page-filter-status" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                        <option value="">Todas</option>
                        <option value="0">No leídas</option>
                        <option value="1">Leídas</option>
                    </select>
                </div>
                <div class="pagination-size">
                    <label for="notifications-page-size">Mostrar:</label>
                    <select id="notifications-page-size" class="form-select form-select-sm">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <button type="button"
                   class="btn btn-sm btn-success-facilitame"
                   id="notifications-page-btn-mark-all"
                   title="Marcar todas como leídas">
                    <i class="ki-outline ki-double-check"></i>
                    <span class="d-none d-md-inline ms-1">Marcar leídas</span>
                </button>
            </div>
        </div>

        <!-- Listado -->
        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; min-height: 0; padding: 0;">
            <div class="tab-list-container" id="notifications-page-list" style="flex: 1; overflow-y: auto; min-height: 0; padding: 1rem 1.25rem;">
                <div class="loading-state">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Cargando notificaciones...</span>
                </div>
            </div>
        </div>

        <!-- Paginador -->
        <div class="pagination-container" id="notifications-page-pagination" style="flex-shrink: 0; display: none;">
            <div class="pagination-info" id="notifications-page-info">Página 1 de 1</div>
            <div class="pagination-nav">
                <button class="btn-pagination" id="notifications-page-prev" disabled>
                    <i class="ki-outline ki-left"></i>
                </button>
                <span class="pagination-current" id="notifications-page-current">1 / 1</span>
                <button class="btn-pagination" id="notifications-page-next" disabled>
                    <i class="ki-outline ki-right"></i>
                </button>
            </div>
        </div>

    </div>

</div>

<script>
(function() {
    'use strict';

    // API para ASESORÍA
    const API_URL = '/api/notifications-paginated-advisory';
    const API_MARK_ALL = '/api/notifications-mark-read'; // Endpoint genérico que funciona para cualquier usuario
    const API_MARK_ONE = '/api/notification-mark-read';

    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        statusFilter: '',
        totalPages: 1,
        totalRecords: 0,
        unreadCount: 0,
        isLoading: false
    };

    const listContainer = document.getElementById('notifications-page-list');
    const resultsCount = document.getElementById('notifications-page-results-count');
    const pageInfo = document.getElementById('notifications-page-info');
    const pageCurrent = document.getElementById('notifications-page-current');
    const prevBtn = document.getElementById('notifications-page-prev');
    const nextBtn = document.getElementById('notifications-page-next');
    const pageSizeSelect = document.getElementById('notifications-page-size');
    const statusSelect = document.getElementById('notifications-page-filter-status');
    const markAllBtn = document.getElementById('notifications-page-btn-mark-all');
    const paginationContainer = document.getElementById('notifications-page-pagination');

    function init() {
        prevBtn.addEventListener('click', () => goToPage(state.currentPage - 1));
        nextBtn.addEventListener('click', () => goToPage(state.currentPage + 1));
        pageSizeSelect.addEventListener('change', handlePageSizeChange);
        statusSelect.addEventListener('change', handleStatusChange);
        markAllBtn.addEventListener('click', markAllAsRead);
        loadData();
    }

    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();

        try {
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.pageSize,
                search: state.searchQuery,
                status: state.statusFilter
            });

            const response = await fetch(`${API_URL}?${params}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            if (result.status === 'ok' && result.data) {
                const { data: items, pagination } = result.data;
                state.totalPages = pagination.total_pages;
                state.totalRecords = pagination.total_records;
                state.unreadCount = pagination.unread_count || 0;

                renderList(items || []);
                updateResultsCount(pagination);
                updatePaginationControls();
            } else {
                showError(result.message || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        } finally {
            state.isLoading = false;
        }
    }

    function renderList(data) {
        if (!data.length) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ki-outline ki-notification"></i></div>
                    <div class="empty-state-title">${state.searchQuery ? 'Sin resultados' : 'No hay notificaciones'}</div>
                    <p class="empty-state-text">${state.searchQuery ? `No se encontraron notificaciones para "${escapeHtml(state.searchQuery)}"` : 'No tienes notificaciones pendientes'}</p>
                </div>`;
            paginationContainer.style.display = 'none';
            return;
        }

        listContainer.innerHTML = data.map(n => {
            const isUnread = n.notification_status == 0 || n.is_unread === true;
            const cardClass = isUnread ? 'warning' : 'muted';
            const titleWeight = isUnread ? 'fw-semibold' : '';

            const notifTitle = n.notification_title || 'Notificación';
            const notifDesc = n.notification_description || '';

            return `
                <div class="list-card list-card-${cardClass} notification-item"
                     data-id="${n.notification_id}"
                     data-status="${n.notification_status}"
                     data-request="${n.request_id || n.id}"
                     data-sender="${n.sender_id || ''}"
                     style="margin-bottom: 0.5rem; cursor: pointer;">
                    <div class="list-card-content">
                        <div class="list-card-title">
                            ${isUnread ? '<span class="unread-indicator"></span>' : ''}
                            <span class="${titleWeight}">${escapeHtml(notifTitle)}</span>
                            ${notifDesc ? `<span class="text-muted">›</span><span class="text-muted">${escapeHtml(notifDesc)}</span>` : ''}
                        </div>
                        <div class="list-card-meta">
                            <span>
                                <i class="ki-outline ki-time"></i>
                                ${escapeHtml(n.time_from)}
                            </span>
                        </div>
                    </div>
                    <div class="list-card-actions">
                        <a href="/customer?id=${n.sender_id || ''}" class="btn-icon btn-icon-info" title="Ver cliente">
                            <i class="ki-outline ki-eye"></i>
                        </a>
                    </div>
                </div>`;
        }).join('');

        paginationContainer.style.display = 'flex';
        listContainer.scrollTop = 0;

        document.querySelectorAll('.notification-item').forEach(card => {
            card.addEventListener('click', handleCardClick);
        });
    }

    function handleCardClick(e) {
        // Si se hizo click en el botón de acción, dejar que el link funcione normalmente
        if (e.target.closest('a')) {
            const notifId = this.dataset.id;
            const notifStatus = this.dataset.status;
            if (notifStatus == '0') {
                markAsRead(notifId, this);
            }
            return; // Permite la navegación del link
        }
        e.preventDefault();
        markAsReadAndGo(this);
    }

    function markAsReadAndGo(card) {
        const notifId = card.dataset.id;
        const notifStatus = card.dataset.status;
        const senderId = card.dataset.sender;

        if (notifStatus == '0') {
            markAsRead(notifId, card);
        }

        // Navegar al cliente
        if (senderId) {
            window.location.href = '/customer?id=' + senderId;
        }
    }

    async function markAsRead(notificationId, element) {
        try {
            const response = await fetch(API_MARK_ONE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id: notificationId })
            });

            const result = await response.json();

            if (result.status === 'ok' || result.success) {
                if (element) {
                    element.classList.remove('list-card-warning');
                    element.classList.add('list-card-muted');
                    element.dataset.status = '1';

                    const dot = element.querySelector('.unread-indicator');
                    if (dot) dot.remove();

                    element.querySelectorAll('.fw-semibold').forEach(el => {
                        el.classList.remove('fw-semibold');
                    });
                }

                state.unreadCount = Math.max(0, state.unreadCount - 1);
                updateUnreadBadge();
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    async function markAllAsRead() {
        const result = await Swal.fire({
            icon: 'question',
            title: 'Marcar todas como leídas',
            text: '¿Estás seguro de marcar todas las notificaciones como leídas?',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar todas',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light ms-2'
            }
        });

        if (!result.isConfirmed) return;

        try {
            markAllBtn.classList.add('disabled');
            markAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const response = await fetch(API_MARK_ALL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: `Se marcaron ${data.data?.updated || 'todas las'} notificaciones como leídas`,
                    timer: 2000,
                    showConfirmButton: false
                });

                state.unreadCount = 0;
                updateUnreadBadge();
                loadData();
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron marcar las notificaciones',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary' }
            });
        } finally {
            markAllBtn.classList.remove('disabled');
            markAllBtn.innerHTML = '<i class="ki-outline ki-double-check"></i><span class="d-none d-md-inline ms-1">Marcar leídas</span>';
        }
    }

    function updateUnreadBadge() {
        const badges = document.querySelectorAll('.notification-badge, #notification-indicator');
        badges.forEach(badge => {
            if (state.unreadCount > 0) {
                badge.textContent = state.unreadCount;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        });
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

    function handleStatusChange(e) {
        state.statusFilter = e.target.value;
        state.currentPage = 1;
        loadData();
    }

    function updatePaginationControls() {
        pageCurrent.textContent = `${state.currentPage} / ${state.totalPages}`;
        pageInfo.innerHTML = `Mostrando página ${state.currentPage} de ${state.totalPages}`;
        prevBtn.disabled = state.currentPage <= 1;
        nextBtn.disabled = state.currentPage >= state.totalPages;
        paginationContainer.style.display = state.totalRecords > state.pageSize ? 'flex' : 'none';
    }

    function updateResultsCount(pagination) {
        const unreadText = state.unreadCount > 0 ? ` <span class="text-warning">(${state.unreadCount} sin leer)</span>` : '';
        resultsCount.innerHTML = pagination.total_records === 0
            ? 'No hay notificaciones'
            : `Mostrando <strong>${pagination.from}-${pagination.to}</strong> de <strong>${pagination.total_records}</strong>${unreadText}`;
    }

    function showLoading() {
        listContainer.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span class="ms-2">Cargando...</span>
            </div>`;
    }

    function showError(message) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="ki-outline ki-disconnect text-danger"></i></div>
                <div class="empty-state-title">Error al cargar</div>
                <p class="empty-state-text">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="window.reloadNotifications()">
                    <i class="ki-outline ki-arrows-circle me-1"></i>Reintentar
                </button>
            </div>`;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.reloadNotifications = () => loadData();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
