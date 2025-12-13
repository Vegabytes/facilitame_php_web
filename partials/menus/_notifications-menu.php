<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column w-350px" 
     data-kt-menu="true" 
     id="kt_menu_notifications">
    
    <!--begin::Header-->
    <div class="notifications-header">
        <div class="d-flex align-items-center gap-3">
            <div class="notification-icon-badge">
                <i class="ki-outline ki-notification-status fs-2x text-white"></i>
                <?php if (NOTIFICATIONS["unread"] > 0): ?>
                    <span class="notification-count" id="notification-count-badge"><?= NOTIFICATIONS["unread"] ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h4 class="text-white fw-bold m-0 fs-5">Notificaciones</h4>
                <span class="text-white opacity-85 fs-7" id="notification-status-text">
                    <?php if (NOTIFICATIONS["unread"] > 0): ?>
                        <?= NOTIFICATIONS["unread"] ?> sin leer
                    <?php else: ?>
                        Todo al día
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    <!--end::Header-->
    
    <!--begin::Items-->
    <div class="scroll-y notifications-list" id="notifications-list">
        <?php if (empty(NOTIFICATIONS) || count(NOTIFICATIONS) <= 1): ?>
            <div class="empty-state-notifications">
                <div class="empty-state-circle">
                    <i class="ki-outline ki-notification-status"></i>
                </div>
                <h5 class="empty-state-title">Todo tranquilo</h5>
                <p class="empty-state-text">No tienes notificaciones nuevas</p>
            </div>
        <?php else: ?>
            <?php foreach (NOTIFICATIONS as $index => $notification) : ?>
                <?php if ($index === "unread") continue; ?>
                <?php $is_unread = $notification["status"] == 0; ?>
                <?php
                // Determinar URL según tipo de notificación
                $notif_type = $notification["type"] ?? 'notification';
                if ($notif_type === 'communication') {
                    $notif_href = "/communications";
                } elseif (asesoria() && !empty($notification["sender_id"])) {
                    $notif_href = "/customer?id=" . $notification["sender_id"];
                } else {
                    $notif_href = "/request?id=" . $notification["request_id"];
                }
                $notif_icon = ($notif_type === 'communication') ? 'ki-notification-bing' : 'ki-notification-2';
                ?>

                <a href="<?= $notif_href ?>"
                   data-notification-status="<?= $notification["status"] ?>"
                   data-notification-id="<?= $notification["id"] ?>"
                   data-notification-type="<?= $notif_type ?>"
                   class="notification-item <?= $is_unread ? 'notification-unread' : '' ?>">

                    <div class="notification-icon <?= $is_unread ? 'icon-unread' : 'icon-read' ?>">
                        <i class="ki-outline <?= $notif_icon ?>"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-text"><?= htmlspecialchars($notification["description"]) ?></div>
                        <div class="notification-time">
                            <i class="ki-outline ki-time"></i>
                            <?= $notification["time_from"] ?>
                        </div>
                    </div>
                    
                    <?php if ($is_unread): ?>
                        <div class="notification-dot"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!--end::Items-->
    
    <!--begin::Footer-->
    <?php if (!empty(NOTIFICATIONS) && count(NOTIFICATIONS) > 1): ?>
        <div class="notifications-footer">
<?php if (cliente() || comercial() || asesoria()) : ?>
    <a href="/notifications" class="btn-notif btn-notif-primary">
        <i class="ki-outline ki-eye"></i>
        Ver todas
    </a>
<?php endif; ?>
            
            <button type="button" class="btn-notif btn-notif-secondary" id="btn-mark-all-read">
                <i class="ki-outline ki-check-circle"></i>
                <span id="btn-mark-text">Marcar leídas</span>
            </button>
        </div>
    <?php endif; ?>
    <!--end::Footer-->
</div>
<!--end::Menu-->

<script>
(function() {
    var markAllBtn = document.getElementById('btn-mark-all-read');
    var notificationIndicator = document.getElementById('notification-indicator');
    var countBadge = document.getElementById('notification-count-badge');
    var statusText = document.getElementById('notification-status-text');
    var headerBadge = document.querySelector('.header-icon-btn .notification-badge');
    
    if (!markAllBtn) return;
    
    markAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var btn = this;
        var btnText = document.getElementById('btn-mark-text');
        var btnIcon = btn.querySelector('i');
        
        if (btn.disabled) return;
        btn.disabled = true;
        
        var originalText = btnText.textContent;
        var originalIcon = btnIcon.className;
        btnText.textContent = 'Marcando...';
        btnIcon.className = 'btn-icon-spinner';
        btn.classList.add('loading');
        
        fetch('/api/notifications-mark-read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success || data.status === 'ok') {
                var unreadItems = document.querySelectorAll('.notification-item.notification-unread');
                unreadItems.forEach(function(item) {
                    item.classList.remove('notification-unread');
                    item.setAttribute('data-notification-status', '1');
                    var icon = item.querySelector('.notification-icon');
                    if (icon) { icon.classList.remove('icon-unread'); icon.classList.add('icon-read'); }
                    var dot = item.querySelector('.notification-dot');
                    if (dot) dot.remove();
                });
                
                if (countBadge) countBadge.style.display = 'none';
                if (statusText) statusText.textContent = 'Todo al día';
                if (notificationIndicator) notificationIndicator.style.display = 'none';
                if (headerBadge) headerBadge.style.display = 'none';
                
                btnText.textContent = '¡Listo!';
                btnIcon.className = 'ki-outline ki-check';
                btn.classList.remove('loading', 'btn-notif-secondary');
                btn.classList.add('btn-notif-success');
                
                setTimeout(function() { btn.style.display = 'none'; }, 2000);
            } else {
                throw new Error(data.message || 'Error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            btnText.textContent = originalText;
            btnIcon.className = originalIcon;
            btn.classList.remove('loading');
            btn.disabled = false;
            alert('No se pudieron marcar las notificaciones. Inténtalo de nuevo.');
        });
    });
    
    var notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(function(item) {
        item.addEventListener('click', function() {
            var notifId = this.getAttribute('data-notification-id');
            var isUnread = this.getAttribute('data-notification-status') === '0';
            if (isUnread && notifId) {
                fetch('/api/notification-mark-read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ id: notifId }),
                    credentials: 'same-origin'
                }).catch(function(err) { console.error('Error:', err); });
            }
        });
    });
})();
</script>