# Facilitame - Documentación del Proyecto

## Descripción General

Facilitame es una plataforma de gestión de servicios que conecta clientes con proveedores, asesorías y comerciales. El sistema permite la gestión de solicitudes de servicios, facturación, citas, comunicaciones y más.

## Estructura del Proyecto

```
facilitame/
├── api/                    # 118 endpoints de API
├── controller/             # 106 controladores
├── pages/                  # Vistas por rol de usuario
│   ├── administrador/      # Panel de administración
│   ├── asesoria/           # Panel de asesorías
│   ├── cliente/            # Panel de clientes
│   ├── comercial/          # Panel de comerciales
│   └── proveedor/          # Panel de proveedores
├── bold/                   # Core del framework
│   ├── classes/            # Clases PHP (User, Request, etc.)
│   └── functions.php       # Funciones globales
├── components/             # Componentes reutilizables
├── assets/                 # CSS, JS, imágenes
├── crons/                  # Tareas programadas
├── database/               # Migraciones y seeds
├── email-templates/        # Plantillas de email
├── layout/                 # Layouts y partials
├── tests/                  # Tests del proyecto
├── uploads/                # Archivos subidos
└── schema.sql              # Esquema de base de datos (42 tablas)
```

## Roles de Usuario

| ID | Rol | Descripción |
|----|-----|-------------|
| 1 | Admin | Administrador del sistema |
| 2 | Provider | Proveedor de servicios |
| 4 | Autónomo | Cliente autónomo |
| 5 | Empresa | Cliente empresa/Asesoría |
| 6 | Particular | Cliente particular |
| 7 | Sales Rep | Comercial |

## Módulos Principales

### 1. Gestión de Servicios
- Solicitudes de servicios por categorías
- Ofertas de proveedores
- Estados: pendiente, en proceso, activo, finalizado
- Sistema de incidencias

### 2. Módulo de Asesorías
- Gestión de clientes de asesoría
- Sistema de citas (solicitado, agendado, finalizado, cancelado)
- Comunicaciones masivas con niveles de importancia
- Envío de facturas (restringido por plan)
- Chat asesoría-cliente
- Planes: gratuito, basic, estandar, pro, premium, enterprise

### 3. Sistema de Comerciales
- Códigos de venta únicos
- Vinculación con clientes y asesorías
- Sistema de comisiones
- Dashboard de KPIs

### 4. Facturación
- Upload de facturas (gastos/ingresos)
- Clasificación por mes/trimestre
- Acceso controlado por permisos

### 5. Notificaciones
- Notificaciones en tiempo real
- Emails transaccionales
- Push notifications (Firebase)
- Recordatorios automáticos (24h para comunicaciones importantes)

## Base de Datos - Tablas Principales

### Usuarios y Roles
- `users` - Usuarios del sistema
- `roles` - Definición de roles
- `model_has_roles` - Asignación de roles

### Asesorías
- `advisories` - Asesorías registradas
- `customers_advisories` - Relación cliente-asesoría
- `advisories_sales_codes` - Asesorías vinculadas a comerciales
- `advisory_appointments` - Citas de asesoría
- `advisory_appointment_history` - Historial de cambios en citas
- `advisory_communications` - Comunicaciones masivas
- `advisory_communication_recipients` - Destinatarios de comunicaciones
- `advisory_invoices` - Facturas de clientes
- `advisory_messages` - Chat asesoría-cliente

### Servicios
- `categories` - Categorías de servicios
- `requests` - Solicitudes de servicios
- `offers` - Ofertas de proveedores
- `incidents` - Incidencias reportadas

### Comerciales
- `sales_codes` - Códigos de venta
- `customers_sales_codes` - Clientes vinculados a comerciales
- `commissions_admin` - Comisiones administrativas

### Sistema
- `notifications` - Notificaciones
- `logs` - Logs de actividad
- `messages` - Mensajes del chat

## APIs por Categoría

### Autenticación
- `POST /api/login` - Iniciar sesión
- `POST /api/logout` - Cerrar sesión
- `POST /api/sign-up` - Registro
- `POST /api/recovery` - Recuperar contraseña
- `POST /api/restore` - Restablecer contraseña
- `POST /api/activate-with-password` - Activar cuenta con contraseña

### Asesorías (Admin)
- `POST /api/advisories-add` - Crear asesoría
- `POST /api/advisories-update` - Actualizar asesoría
- `POST /api/advisories-delete` - Eliminar asesoría (soft delete)

### Asesorías (Asesor)
- `GET /api/advisory-detail` - Detalle de asesoría
- `GET /api/advisory-clients-paginated` - Listar clientes
- `POST /api/advisory-create-customer` - Crear cliente
- `POST /api/advisory-link-customer` - Vincular cliente existente
- `POST /api/advisory-create-appointment` - Crear cita
- `POST /api/advisory-update-appointment` - Actualizar cita
- `POST /api/advisory-send-communication` - Enviar comunicación
- `POST /api/advisory-upload-invoice` - Subir factura
- `POST /api/advisory-chat-send` - Enviar mensaje chat

### Clientes
- `POST /api/customer-link-advisory` - Vincularse a asesoría con código
- `GET /api/customer-communications-list` - Ver comunicaciones
- `GET /api/client-appointments-count` - Contar citas
- `GET /api/client-requests-count` - Contar solicitudes

### Comerciales
- `POST /api/sales-rep-create` - Crear comercial
- `POST /api/sales-rep-update` - Actualizar comercial
- `POST /api/sales-rep-delete` - Eliminar comercial (soft delete)

### Servicios
- `GET /api/get-services` - Listar servicios
- `GET /api/get-service` - Detalle de servicio
- `GET /api/get-request` - Detalle de solicitud
- `POST /api/request-delete` - Eliminar solicitud
- `POST /api/request-reactivate` - Reactivar solicitud

### Ofertas
- `POST /api/offer-upload` - Subir oferta
- `POST /api/offer-accept` - Aceptar oferta
- `POST /api/offer-reject` - Rechazar oferta
- `POST /api/offer-activate` - Activar oferta
- `POST /api/offer-withdraw` - Retirar oferta

### Incidencias
- `POST /api/incident-report` - Reportar incidencia
- `POST /api/incident-mark-active` - Marcar como activa
- `POST /api/incident-mark-closed` - Cerrar incidencia
- `POST /api/incident-mark-validated` - Validar incidencia

### App Móvil
- `GET /api/app-dashboard` - Dashboard móvil
- `GET /api/app-services` - Servicios del usuario
- `POST /api/app-token-save-fcm` - Guardar token Firebase
- `POST /api/app-token-remove-fcm` - Eliminar token Firebase
- `GET /api/app-notifications-get-pending` - Notificaciones pendientes

### Notificaciones
- `POST /api/notification-mark-read` - Marcar como leída
- `POST /api/notifications-mark-all-read-customer` - Marcar todas como leídas (cliente)
- `POST /api/notifications-mark-all-read-sales` - Marcar todas como leídas (comercial)

## Flujos de Email

### 1. Registro de Usuario
- Usuario se registra → Email con enlace de activación (48h)
- Usuario activa cuenta → Establece contraseña

### 2. Recuperación de Contraseña
- Usuario solicita recuperación → Email con enlace (1h)
- Usuario restablece contraseña

### 3. Creación de Usuario por Admin/Asesoría
- Admin/Asesoría crea usuario → Email de activación enviado
- Usuario activa cuenta → Establece contraseña

### 4. Notificaciones
- Nuevo mensaje → Email de notificación
- Nueva cita → Email de confirmación
- Comunicación importante → Email + recordatorio 24h

## Seguridad

### Autenticación
- Sesiones PHP con tokens
- Tokens de verificación para email
- Expiración de tokens configurable

### Autorización
- Funciones de verificación por rol: `admin()`, `cliente()`, `proveedor()`, `comercial()`, `asesoria()`
- Verificación en cada endpoint de API

### Validaciones
- NIF/CIF/NIE validación española
- Email único por usuario
- CIF único por asesoría
- Soft delete para mantener integridad histórica

## Crons

Los crons están en la carpeta `/crons/` y generan logs en `/crons/logs/`.

| Cron | Frecuencia | Descripción |
|------|------------|-------------|
| `cron_advisory_reminder.php` | Cada hora | Reenvía recordatorio de comunicaciones con importancia "Importante" que no han sido leídas después de 24h |
| `cron_notify_rescheduled_requests.php` | Diario (8:00) | Notifica a cliente, comercial y proveedor cuando una solicitud reagendada alcanza su fecha de reactivación |

### Configuración en crontab
```bash
# Recordatorio comunicaciones importantes (cada hora)
0 * * * * php /var/www/facilitame/crons/cron_advisory_reminder.php

# Solicitudes reagendadas (diario a las 8:00)
0 8 * * * php /var/www/facilitame/crons/cron_notify_rescheduled_requests.php
```

## Configuración

### Variables de Entorno
```php
// Base de datos
DB_HOST, DB_NAME, DB_USER, DB_PASSWORD

// Email (SMTP)
SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_FROM

// Rutas
ROOT_DIR, ROOT_URL, CONTROLLER

// Entorno
ENVIRONMENT (DEMO/PRODUCTION)
```

## Pendiente para Fases Futuras

### Inmatic
- Integración con API Inmatic v1.0.15
- Gestión de llamadas y leads

### Google Calendar
- Sincronización de citas con Google Calendar
- OAuth2 para autenticación

### Pasarela de Pago
- Integración de pagos online
- Gestión de suscripciones por plan

### App Móvil
- Actualmente solo para clientes
- Expandir a otros perfiles (proveedores, comerciales)

---

*Documentación generada el 11/12/2025*
