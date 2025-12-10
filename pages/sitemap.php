<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa del Sitio - Facilitame</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link rel="shortcut icon" href="assets/media/bold/favicon.png" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f4f5 100%);
            margin: 0;
            padding: 2rem 1rem;
            min-height: 100vh;
            color: #475569;
            line-height: 1.7;
        }

        .legal-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 194, 203, 0.08);
            padding: 3rem;
        }

        .legal-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 3px solid #00c2cb;
        }

        .legal-header h1 {
            color: #00c2cb;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .legal-header p {
            color: #64748b;
            font-size: 1rem;
            margin: 0;
            text-align: center;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #00c2cb;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            color: #00a8b0;
            transform: translateX(-3px);
        }

        .sitemap-section {
            margin-bottom: 2rem;
        }

        .sitemap-section h2 {
            color: #00c2cb;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .sitemap-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sitemap-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .sitemap-list li:last-child {
            border-bottom: none;
        }

        .sitemap-list a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #1e293b;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sitemap-list a:hover {
            color: #00c2cb;
            padding-left: 0.5rem;
        }

        .sitemap-list .icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #00c2cb 0%, #00a8b0 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
        }

        .sitemap-list .description {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 400;
            margin-left: auto;
        }

        .legal-footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
        }

        .legal-footer a {
            color: #00c2cb;
            text-decoration: none;
        }

        .legal-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .legal-container {
                padding: 1.5rem;
                margin: 1rem;
                border-radius: 12px;
            }

            .legal-header h1 {
                font-size: 1.75rem;
            }

            .sitemap-list .description {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="legal-container">
        <a href="/" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver al inicio
        </a>

        <div class="legal-header">
            <h1>Mapa del Sitio</h1>
            <p>Navegacion completa de Facilitame</p>
        </div>

        <div class="sitemap-section">
            <h2>Acceso</h2>
            <ul class="sitemap-list">
                <li>
                    <a href="/login">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <polyline points="10 17 15 12 10 7"/>
                                <line x1="15" y1="12" x2="3" y2="12"/>
                            </svg>
                        </span>
                        Iniciar Sesion
                        <span class="description">Accede a tu cuenta</span>
                    </a>
                </li>
                <li>
                    <a href="/sign-up">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                        </span>
                        Registrarse
                        <span class="description">Crea una nueva cuenta</span>
                    </a>
                </li>
                <li>
                    <a href="/recovery">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        Recuperar Contrasena
                        <span class="description">Restablece tu acceso</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sitemap-section">
            <h2>Informacion Legal</h2>
            <ul class="sitemap-list">
                <li>
                    <a href="/terms">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        Politica de Privacidad
                        <span class="description">Proteccion de datos</span>
                    </a>
                </li>
                <li>
                    <a href="/legal">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </span>
                        Aviso Legal
                        <span class="description">Terminos de uso</span>
                    </a>
                </li>
                <li>
                    <a href="/cookies">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                        Politica de Cookies
                        <span class="description">Uso de cookies</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sitemap-section">
            <h2>Contacto</h2>
            <ul class="sitemap-list">
                <li>
                    <a href="mailto:soporte@facilitame.es">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        soporte@facilitame.es
                        <span class="description">Ayuda y soporte</span>
                    </a>
                </li>
                <li>
                    <a href="https://facilitame.es" target="_blank">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                        </span>
                        facilitame.es
                        <span class="description">Sitio web principal</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="legal-footer">
            <p>&copy; 2024 Facilitame 2024 SL. Todos los derechos reservados.</p>
            <p><a href="/">Volver al inicio</a></p>
        </div>
    </div>
</body>

</html>
