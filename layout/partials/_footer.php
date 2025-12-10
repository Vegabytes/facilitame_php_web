<!--begin::Footer-->
<div id="kt_app_footer" class="app-footer">
    <div class="footer-container">
        <div class="footer-copyright">
            <span>2024 ©</span>
            <a href="https://facilitame.es/" target="_blank"><?php secho(COMPANY_NAME_LEGAL) ?></a>
        </div>
        
        <div class="footer-links">
            <a href="/terms" target="_blank"><i class="ki-outline ki-shield-tick"></i>Privacidad</a>
            <a href="/legal" target="_blank"><i class="ki-outline ki-document"></i>Aviso Legal</a>
            <a href="/cookies" target="_blank"><i class="ki-outline ki-setting-2"></i>Cookies</a>
            <a href="mailto:soporte@facilitame.es"><i class="ki-outline ki-sms"></i>Soporte</a>
        </div>
        
        <span class="footer-version">v1.0.0</span>
    </div>
</div>
<!--end::Footer-->

<style>
.app-footer {
    position: fixed;
    bottom: 0;
    left: 240px;
    right: 0;
    height: 48px;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    z-index: 99;
}

.footer-container {
    max-width: 1400px;
    height: 100%;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.footer-copyright { display: flex; align-items: center; gap: 0.375rem; }
.footer-copyright a { color: #1e293b; font-weight: 600; text-decoration: none; }
.footer-copyright a:hover { color: var(--color-main-facilitame); }

.footer-links { display: flex; align-items: center; gap: 1.25rem; }
.footer-links a {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: #64748b;
    text-decoration: none;
    transition: color 0.15s;
}
.footer-links a:hover { color: var(--color-main-facilitame); }
.footer-links i { font-size: 0.875rem; }

.footer-version {
    background: rgba(0, 194, 203, 0.1);
    color: var(--color-main-facilitame);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 600;
}

/* Sidebar colapsado */
.app-sidebar.collapsed ~ .app-wrapper .app-footer { left: 72px; }

/* Responsive */
@media (max-width: 991px) {
    .app-footer { left: 0; height: auto; padding: 0.5rem 0; }
    .footer-container { flex-direction: column; gap: 0.5rem; text-align: center; }
    .footer-links { flex-wrap: wrap; justify-content: center; gap: 0.75rem; }
}

@media (max-width: 576px) {
    .footer-links i { display: none; }
}
</style>