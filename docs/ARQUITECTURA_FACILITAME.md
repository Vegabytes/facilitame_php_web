# ARQUITECTURA Y FUNCIONAMIENTO - FACILITAME

**Fecha**: 10 de Diciembre 2025
**Versión**: 1.0

---

## 1. VISIÓN GENERAL

Facilitame es una plataforma de gestión de servicios que conecta **clientes** con **proveedores** a través de **comerciales**, con un módulo adicional de **asesorías** para gestión contable y fiscal.

### Stack Tecnológico

| Capa | Tecnología |
|------|------------|
| Frontend Web | PHP + HTML + JavaScript + CSS |
| Frontend Mobile | React Native (Expo) |
| Backend | PHP 8.x |
| Base de Datos | MySQL 8.x (InnoDB) |
| Autenticación | JWT (Firebase PHP-JWT) |
| Email | PHPMailer |
| PDF | TCPDF |
| Notificaciones Push | Firebase Cloud Messaging |
| APIs Externas | Google APIs |

---

## 2. ESTRUCTURA DE DIRECTORIOS

```
Facilitame/
├── api/                    # Endpoints API (legacy y algunos nuevos)
├── app/                    # Aplicación móvil React Native
├── assets/                 # Recursos estáticos
│   ├── css/               # Hojas de estilo
│   ├── js/                # JavaScript
│   │   └── bold/          # Scripts personalizados
│   └── documents/         # Archivos subidos
│       └── bold/          # Documentos de la app
├── bold/                   # Core del sistema
│   ├── auth.php           # Autenticación y sesiones
│   ├── functions.php      # Funciones globales (2200+ líneas)
│   ├── config.php         # Configuración
│   └── init.php           # Inicialización
├── components/             # Componentes PHP reutilizables
├── controller/             # Controladores de API (nuevos)
├── layout/                 # Templates de layout
│   └── partials/          # Parciales (header, sidebar, footer)
├── pages/                  # Páginas por rol
│   ├── administrador/     # Páginas de admin
│   ├── asesoria/          # Páginas de asesoría
│   ├── cliente/           # Páginas de cliente
│   ├── comercial/         # Páginas de comercial
│   └── proveedor/         # Páginas de proveedor
├── partials/               # Parciales compartidos
│   └── menus/             # Menús (notificaciones, usuario)
├── src/                    # Recursos fuente (SASS)
├── tests/                  # Tests y reportes
├── vendor/                 # Dependencias Composer
└── docs/                   # Documentación
```

---

## 3. ROLES Y PERMISOS

### 3.1 Roles del Sistema

| Rol | Descripción | Acceso |
|-----|-------------|--------|
| `administrador` | Control total del sistema | Todo |
| `comercial` | Gestiona clientes y solicitudes | Clientes asignados |
| `proveedor` | Atiende solicitudes de su categoría | Categorías asignadas |
| `cliente` | Realiza solicitudes de servicios | Sus propias solicitudes |
| `asesoria` | Gestiona clientes de asesoría | Sus clientes vinculados |

### 3.2 Funciones de Verificación de Rol

```php
// bold/auth.php
admin()      // Retorna true si es administrador
comercial()  // Retorna true si es comercial
proveedor()  // Retorna true si es proveedor
cliente()    // Retorna true si es cliente
asesoria()   // Retorna true si es asesoría
```

### 3.3 Verificación de Acceso a Solicitudes

```php
// bold/functions.php
user_can_access_request($request_id)
// - Admin: acceso total
// - Cliente: solo sus solicitudes
// - Comercial: clientes de sus códigos de venta
// - Proveedor: solicitudes de sus categorías
```

---

## 4. FLUJO DE AUTENTICACIÓN

### 4.1 Login Web

```
1. Usuario envía credenciales → /api/login
2. Servidor valida contra BD (users.email, users.password)
3. Si válido, genera JWT con payload:
   - id, name, email, role, categories (proveedor)
4. JWT se guarda en cookie 'token'
5. Cada request, bold/auth.php verifica JWT
6. Si válido, define constante USER con datos
```

### 4.2 Login App Móvil

```
1. App envía credenciales → /api/app-login
2. Mismo proceso de validación
3. JWT retornado en response JSON
4. App guarda JWT en AsyncStorage
5. App envía JWT en header Authorization
```

### 4.3 Estructura JWT

```php
$payload = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $role_name,
    'categories' => $categories, // Solo para proveedor
    'exp' => time() + (60 * 60 * 24 * 30) // 30 días
];
```

---

## 5. ROUTING

### 5.1 Router Principal

```php
// index.php
$url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$url = ltrim($url, "/");

// Rutas de API
if (str_starts_with($url, "api/")) {
    $endpoint = substr($url, 4);
    require "api/{$endpoint}.php";
}

// Rutas de controlador
if (str_starts_with($url, "api-")) {
    $controller = str_replace("api-", "", $url);
    require "controller/api-{$controller}.php";
}

// Rutas de páginas
$page = $url ?: "home";
$role = USER['role'];
require "pages/{$role}/{$page}.php";
```

### 5.2 Convención de Nombres

| Tipo | Patrón | Ejemplo |
|------|--------|---------|
| API Legacy | `/api/{nombre}` | `/api/login` |
| API Nueva | `/api-{nombre}` | `/api-advisory-appointments-paginated` |
| Página | `/{nombre}` | `/appointments` |

---

## 6. BASE DE DATOS

### 6.1 Tablas Principales

#### Usuarios y Roles
```sql
users                    -- Usuarios del sistema
roles                    -- Definición de roles
model_has_roles          -- Relación usuario-rol
```

#### Solicitudes y Ofertas
```sql
requests                 -- Solicitudes de servicio
offers                   -- Ofertas de proveedores
request_files            -- Documentos adjuntos
request_comments         -- Comentarios internos
provider_comments        -- Comentarios de proveedor
```

#### Módulo Asesoría
```sql
advisories               -- Asesorías registradas
customers_advisories     -- Relación cliente-asesoría
advisory_appointments    -- Citas
advisory_messages        -- Chat asesoría-cliente
advisory_invoices        -- Facturas enviadas
advisory_communications  -- Comunicados masivos
advisory_communication_recipients -- Destinatarios
advisory_communication_files      -- Adjuntos de comunicados
```

#### Comerciales
```sql
sales_codes              -- Códigos de venta
customers_sales_codes    -- Relación cliente-código
commissions_admin        -- Comisiones
```

#### Sistema
```sql
notifications            -- Notificaciones
categories               -- Categorías de servicios
provider_categories      -- Categorías por proveedor
```

### 6.2 Diagrama de Relaciones Principales

```
users (1) ──────── (N) requests
  │
  └── (1) ──────── (1) advisories
                        │
                        └── (1) ── (N) customers_advisories ── (N) ── (1) users (clientes)
                        │
                        └── (1) ── (N) advisory_appointments
                        │
                        └── (1) ── (N) advisory_communications
                                        │
                                        └── (1) ── (N) advisory_communication_recipients
```

---

## 7. MÓDULOS PRINCIPALES

### 7.1 Módulo de Solicitudes

**Flujo**:
1. Cliente crea solicitud → estado "pendiente"
2. Proveedor recibe y crea oferta
3. Cliente acepta oferta → estado "en proceso"
4. Proveedor completa → estado "completado"
5. Facturación y cierre

**Estados**:
- pendiente, en_proceso, completado, cancelado, aplazado

### 7.2 Módulo de Asesoría

**Funcionalidades**:
- **Citas**: Cliente solicita → Asesoría agenda → Finaliza
- **Facturas**: Cliente sube → Asesoría recibe y procesa
- **Comunicaciones**: Asesoría envía → Clientes reciben
- **Chat**: Bidireccional asesoría ↔ cliente

**Estados de Citas**:
- solicitado, agendado, finalizado, cancelado

### 7.3 Módulo de Notificaciones

```php
// Crear notificación
notification(
    $sender_id,      // Quien envía
    $receiver_id,    // Quien recibe
    $request_id,     // Solicitud relacionada (puede ser null)
    $title,          // Título
    $description     // Descripción
);
```

**Canales**:
- In-app (tabla notifications)
- Email (PHPMailer)
- Push (Firebase - solo móvil)

---

## 8. APIs PRINCIPALES

### 8.1 Autenticación

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/login` | POST | Login web |
| `/api/app-login` | POST | Login móvil |
| `/api/sign-up` | POST | Registro |
| `/api/recovery-password` | POST | Recuperar contraseña |

### 8.2 Solicitudes

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api-requests-paginated-{rol}` | GET | Listar solicitudes |
| `/api/request-create` | POST | Crear solicitud |
| `/api/request-update` | POST | Actualizar solicitud |

### 8.3 Asesoría

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api-advisory-appointments-paginated` | GET | Listar citas (asesoría) |
| `/api-customer-appointments-paginated` | GET | Listar citas (cliente) |
| `/api-customer-request-appointment` | POST | Solicitar cita |
| `/api-advisory-update-appointment` | POST | Actualizar cita |
| `/api/advisory-upload-invoice` | POST | Subir factura |
| `/api/advisory-send-communication` | POST | Enviar comunicado |
| `/api-advisory-invoices-paginated` | GET | Listar facturas |

### 8.4 Notificaciones

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/notifications-paginated-{rol}` | GET | Listar notificaciones |
| `/api/notifications-mark-read` | POST | Marcar todas leídas |
| `/api/notification-mark-read` | POST | Marcar una leída |

### 8.5 Descarga de Archivos

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/file-download` | GET | Descarga segura de archivos |

**Parámetros**:
- `type`: advisory_invoice, request_file, offer, communication_file
- `id`: ID del archivo

---

## 9. FRONTEND

### 9.1 Layout Principal

```php
// layout/master.php
<!DOCTYPE html>
<html>
<head>
    <?php require 'partials/head.php'; ?>
</head>
<body>
    <?php require 'partials/sidebar/_menu_sidebar.php'; ?>
    <?php require 'partials/header/_header.php'; ?>

    <div class="content">
        <?php require $page_content; ?>
    </div>

    <?php require 'partials/scripts.php'; ?>
</body>
</html>
```

### 9.2 Patrón de Páginas con Listados

```javascript
// Patrón común en páginas con listados paginados
(function() {
    'use strict';

    const API_URL = '/api-{endpoint}';

    const state = {
        currentPage: 1,
        pageSize: 10,
        searchQuery: '',
        filters: {},
        totalPages: 1,
        isLoading: false
    };

    async function loadData() {
        if (state.isLoading) return;
        state.isLoading = true;
        showLoading();

        const params = new URLSearchParams({
            page: state.currentPage,
            limit: state.pageSize,
            search: state.searchQuery,
            ...state.filters
        });

        const response = await fetch(`${API_URL}?${params}`);
        const result = await response.json();

        if (result.status === 'ok') {
            renderList(result.data.data);
            updatePagination(result.data.pagination);
        }

        state.isLoading = false;
    }

    // ... renderList, updatePagination, etc.
})();
```

### 9.3 Componentes UI

- **Cards**: `.list-card`, `.list-card-{status}`
- **Badges**: `.badge-status`, `.badge-status-{tipo}`
- **Botones**: `.btn-icon`, `.btn-primary`, `.btn-light`
- **Modales**: Bootstrap 5 modals
- **Alertas**: SweetAlert2

---

## 10. APLICACIÓN MÓVIL

### 10.1 Estructura

```
app/
├── src/
│   ├── components/        # Componentes reutilizables
│   ├── screens/           # Pantallas
│   ├── navigation/        # React Navigation
│   ├── services/          # APIs y servicios
│   ├── context/           # React Context (auth, etc.)
│   └── utils/             # Utilidades
├── app.json               # Configuración Expo
└── package.json           # Dependencias
```

### 10.2 Autenticación Móvil

```javascript
// Almacenamiento de token
await AsyncStorage.setItem('token', jwt);

// Envío en requests
fetch(url, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});
```

### 10.3 Notificaciones Push

```javascript
// Registro de token FCM
const fcmToken = await messaging().getToken();
await api.post('/api/app-register-push-token', { token: fcmToken });
```

---

## 11. SEGURIDAD

### 11.1 Autenticación
- JWT con expiración de 30 días
- Passwords hasheados con `password_hash()`
- Verificación en cada request protegido

### 11.2 Autorización
- Verificación de rol en cada endpoint
- `user_can_access_request()` para acceso a recursos
- Sanitización de inputs

### 11.3 Protección de Datos
- Prepared statements (PDO) para SQL
- `htmlspecialchars()` para output HTML
- CORS configurado para dominios permitidos

### 11.4 Archivos
- Archivos subidos con UUID único
- Verificación de permisos antes de descarga
- Tipos MIME validados

---

## 12. DESPLIEGUE

### 12.1 Requisitos del Servidor
- PHP 8.0+
- MySQL 8.0+
- Apache/Nginx con mod_rewrite
- Composer
- SSL/HTTPS

### 12.2 Configuración
```php
// bold/config.php
define('ROOT_URL', 'https://facilitame.com');
define('ROOT_DIR', '/var/www/facilitame');
define('DB_HOST', 'localhost');
define('DB_NAME', 'facilitame');
define('DB_USER', 'user');
define('DB_PASS', 'password');
define('JWT_SECRET', 'secret_key');
```

### 12.3 Directorios con Permisos de Escritura
- `assets/documents/bold/`
- `logs/`

---

## 13. MANTENIMIENTO

### 13.1 Logs
```php
error_log("Mensaje de error");
// Se escribe en logs/error.log
```

### 13.2 Tests
```
tests/
├── run_tests.php          # Tests HTTP
├── code_analysis.php      # Análisis estático
├── BUGS_REPORTADOS.md     # Reporte de bugs
└── REPORTE_ISSUES.md      # Issues técnicos
```

### 13.3 Documentación
```
docs/
├── ARQUITECTURA_FACILITAME.md      # Este documento
├── ANALISIS_REQUISITOS_ASESORIA.md # Requisitos del módulo
├── OPTIMIZACION_SQL.md             # Optimizaciones de BD
└── PLAN_REFACTORIZACION.md         # Plan de mejoras
```

---

*Documento generado el 10 de Diciembre 2025*
