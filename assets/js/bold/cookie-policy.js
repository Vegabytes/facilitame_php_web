$(document).ready(function() {
    
    function getCookie(name) {
        let value = `; ${document.cookie}`;
        let parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    // Si ya aceptó, no mostrar banner
    if (getCookie('cookies-accepted')) {
        return;
    }
    
    const banner = `
        <div id="cookie-banner">
            <p>
                Utilizamos cookies esenciales para el funcionamiento de la aplicación web. 
                También utilizamos cookies de analítica para mejorar nuestros servicios. 
                Para más información, consulta nuestra 
                <a href="/cookies" target="_blank">Política de Cookies</a>.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary-facilitame flex-fill" id="accept-all-cookies">
                    Aceptar todas
                </button>
                <button class="btn btn-secondary flex-fill" id="accept-essential-cookies">
                    Solo esenciales
                </button>
            </div>
        </div>
    `;
    
    $('body').append(banner);
    
    // Aceptar todas (esenciales + analíticas)
    $('#accept-all-cookies').on('click', function() {
        document.cookie = "cookies-accepted=true; path=/; max-age=" + (60 * 60 * 24 * 730);
        document.cookie = "analytics-accepted=true; path=/; max-age=" + (60 * 60 * 24 * 730);
        localStorage.setItem('facilitame-analytics-consent', 'accepted');
        $('#cookie-banner').remove();
        location.reload();
    });
    
    // Solo esenciales
    $('#accept-essential-cookies').on('click', function() {
        document.cookie = "cookies-accepted=true; path=/; max-age=" + (60 * 60 * 24 * 730);
        document.cookie = "analytics-accepted=false; path=/; max-age=" + (60 * 60 * 24 * 730);
        localStorage.setItem('facilitame-analytics-consent', 'rejected');
        $('#cookie-banner').remove();
    });
});