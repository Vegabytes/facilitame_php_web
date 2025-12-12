<?php
/**
 * Componente: Conectar Google Calendar
 * Muestra el estado de conexión y botones para conectar/desconectar
 */

$gcal_connected = isGoogleCalendarConnected(USER['id']);
?>

<div class="card mb-5">
    <div class="card-header border-0">
        <h4 class="card-title mb-0 d-flex align-items-center gap-2">
            <img src="https://www.gstatic.com/calendar/images/dynamiclogo_2020q4/calendar_31_2x.png"
                 alt="Google Calendar" style="width: 24px; height: 24px;">
            Google Calendar
        </h4>
    </div>
    <div class="card-body pt-0">
        <?php if ($gcal_connected): ?>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge badge-light-success fs-7">
                        <i class="ki-outline ki-check-circle me-1"></i>
                        Conectado
                    </span>
                    <span class="text-muted fs-7">
                        Las citas se sincronizan automáticamente
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-light-danger" id="btn-gcal-disconnect">
                    <i class="ki-outline ki-disconnect me-1"></i>
                    Desconectar
                </button>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <p class="text-gray-600 mb-1">
                        Sincroniza tus citas de Facilitame con Google Calendar
                    </p>
                    <span class="text-muted fs-7">
                        Las citas aparecerán automáticamente en tu calendario
                    </span>
                </div>
                <a href="/api/google-calendar-connect?return_url=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="btn btn-sm btn-light-primary">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                         alt="" style="width: 18px; height: 18px; margin-right: 8px;">
                    Conectar con Google
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($gcal_connected): ?>
<script>
document.getElementById('btn-gcal-disconnect').addEventListener('click', function() {
    Swal.fire({
        title: '¿Desconectar Google Calendar?',
        text: 'Las citas ya sincronizadas permanecerán en tu calendario, pero las nuevas no se añadirán automáticamente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, desconectar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            fetch('/api/google-calendar-disconnect', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Desconectado',
                        text: 'Google Calendar ha sido desconectado',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
            });
        }
    });
});
</script>
<?php endif; ?>
