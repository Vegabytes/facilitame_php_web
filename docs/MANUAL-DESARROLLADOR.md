# Manual de Desarrollador - Facilitame

## Guia Tecnica para el Desarrollo

**Version:** 1.0
**Fecha:** 12 de Diciembre 2025

---

## INDICE

1. [Introduccion](#1-introduccion)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Estructura de Directorios](#3-estructura-de-directorios)
4. [Flujo de Peticiones](#4-flujo-de-peticiones)
5. [Sistema de Autenticacion](#5-sistema-de-autenticacion)
6. [Base de Datos](#6-base-de-datos)
7. [APIs](#7-apis)
8. [Funciones Auxiliares](#8-funciones-auxiliares)
9. [Sistema de Roles](#9-sistema-de-roles)
10. [Entornos](#10-entornos)
11. [Desarrollo de Nuevas Funcionalidades](#11-desarrollo-de-nuevas-funcionalidades)
12. [Integraciones Externas](#12-integraciones-externas)
13. [Despliegue](#13-despliegue)
14. [Convenios de Codigo](#14-convenios-de-codigo)
15. [Depuracion](#15-depuracion)

---

## 1. INTRODUCCION

### 1.1 Stack Tecnologico

| Componente | Tecnologia |
|------------|------------|
| Backend | PHP 8.x |
| Base de Datos | MySQL 8.0+ |
| Frontend | Bootstrap 5, Metronic 8, jQuery |
| Autenticacion | JWT (firebase/php-jwt) |
| Emails | PHPMailer |
| UUIDs | ramsey/uuid |
| App Movil | iOS (Swift), Android (Kotlin) |

### 1.2 Requisitos de Desarrollo

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Composer
- Laragon (recomendado para Windows) o LAMP/MAMP
- Git

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Patron MVC Simplificado

El sistema sigue un patron MVC adaptado:

- **Modelo:** Funciones en `bold/functions.php` + consultas PDO directas
- **Vista:** Archivos PHP en `/pages/` organizados por rol
- **Controlador:**
  - `/controller/` para vistas web
  - `/api/` para endpoints AJAX

### 2.2 Punto de Entrada

Todas las peticiones pasan por `bold/bold.php`:

```
URL solicitada → bold.php → Detecta tipo (web/api) → Carga archivos necesarios → Ejecuta
```

---

## 3. ESTRUCTURA DE DIRECTORIOS

```
/Facilitame
├── api/                    # Endpoints API (AJAX)
│   ├── login.php          # Autenticacion
│   ├── request-*.php      # APIs de solicitudes
│   ├── advisory-*.php     # APIs de asesorias
│   └── app-*.php          # APIs para app movil
│
├── assets/
│   ├── css/               # Estilos CSS
│   ├── js/
│   │   ├── bold/          # JS personalizado por pagina
│   │   └── custom/        # JS generico
│   ├── media/             # Imagenes e iconos
│   └── plugins/           # Librerias de terceros
│
├── bold/                   # CORE del sistema
│   ├── bold.php           # Punto de entrada principal
│   ├── auth.php           # Logica de autenticacion JWT
│   ├── vars.php           # Variables de entorno
│   ├── db.php             # Conexion PDO
│   ├── functions.php      # Funciones globales
│   ├── classes/           # Clases PHP
│   │   ├── InmaticClient.php
│   │   ├── GoogleCalendarClient.php
│   │   ├── Request.php
│   │   └── User.php
│   └── utils/             # Utilidades
│       ├── firebase-console-message.php
│       └── apple-apn.php
│
├── components/            # Componentes reutilizables
│   ├── card-service.php
│   ├── card-offer.php
│   └── modal-*.php
│
├── controller/            # Controladores de paginas
│   ├── login.php          # Prepara datos para login
│   ├── cliente/           # Controladores de cliente
│   ├── proveedor/         # Controladores de proveedor
│   ├── asesoria/          # Controladores de asesoria
│   └── api-*.php          # APIs alternativas
│
├── database/              # Scripts SQL
├── docs/                  # Documentacion
├── documents/             # Archivos subidos (runtime)
├── email-templates/       # Plantillas HTML de email
├── layout/                # Layouts principales
│   ├── partials/
│   │   ├── _header.php
│   │   ├── _footer.php
│   │   └── _sidebar-*.php
│
├── migrations/            # Migraciones SQL
├── pages/                 # Vistas por rol
│   ├── administrador/
│   ├── asesoria/
│   ├── cliente/
│   ├── comercial/
│   └── proveedor/
│
├── partials/              # Parciales compartidos
├── vendor/                # Dependencias Composer
└── index.php              # Template principal
```

---

## 4. FLUJO DE PETICIONES

### 4.1 Peticion Web (Pagina)

```
1. Usuario accede a /dashboard
2. .htaccess redirige a bold/bold.php?page=dashboard
3. bold.php detecta RESOURCE="/pages", PAGE="/dashboard"
4. Carga vars.php, db.php, functions.php
5. auth.php valida JWT de cookie
6. controller() carga /controller/dashboard.php
7. index.php renderiza layout + /pages/{rol}/dashboard.php
```

### 4.2 Peticion API (AJAX)

```
1. JS hace POST a /api/request-create
2. bold.php detecta RESOURCE="/api", PAGE="/request-create"
3. auth.php valida JWT
4. Carga /api/request-create.php (o /controller/api-request-create.php)
5. API procesa y devuelve JSON
```

### 4.3 Busqueda de Archivos API

El sistema busca APIs en dos ubicaciones:

```php
// En bold.php linea 117-127
$api_file = ROOT_DIR . "/api" . PAGE . ".php";           // /api/nombre.php
$controller_file = ROOT_DIR . "/controller/api" . str_replace("/", "-", PAGE) . ".php";  // /controller/api-nombre.php

if (file_exists($api_file)) {
    require $api_file;
} elseif (file_exists($controller_file)) {
    require $controller_file;
}
```

**Importante:** Si una API existe en ambas ubicaciones, `/api/` tiene prioridad.

---

## 5. SISTEMA DE AUTENTICACION

### 5.1 JWT (JSON Web Token)

El sistema usa JWT almacenado en cookie `auth_token`:

```php
// Estructura del payload
$payload = [
    'role' => 'cliente',        // Rol del usuario
    'user_id' => 123,           // ID del usuario
    'iat' => time(),            // Issued at
    'exp' => time() + 3600      // Expira en 1 hora
];

$jwt = JWT::encode($payload, JWT_SECRET, "HS256");
setcookie("auth_token", $jwt, time() + 3600, "/", "", false, true);
```

### 5.2 Renovacion Automatica

Si quedan menos de 10 minutos para expirar, el token se renueva automaticamente:

```php
// En auth.php linea 25-37
if ($timeLeft < 600) {
    // Renovar token por 1 hora mas
}
```

### 5.3 Constante USER

Despues de autenticarse, se define la constante global `USER`:

```php
define("USER", [
    "role" => "cliente",           // Rol original
    "view" => "cliente",           // Vista a usar (cliente incluye autonomo, empresa, particular)
    "id" => 123,
    "name" => "Juan",
    "lastname" => "Perez",
    "email" => "juan@example.com",
    "profile_picture" => "foto.jpg",
    "phone" => "666555444"
]);
```

### 5.4 APIs Moviles

Las apps moviles envian el token via POST en lugar de cookie:

```php
// En auth.php linea 16
$jwt = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : $_POST["auth_token"];
```

Ademas, las apps envian header `X-Origin: app`:

```php
define("IS_MOBILE_APP", isset($_SERVER['HTTP_X_ORIGIN']) && $_SERVER['HTTP_X_ORIGIN'] === 'app');
```

---

## 6. BASE DE DATOS

### 6.1 Conexion PDO

La conexion se establece en `bold/db.php`:

```php
global $pdo;
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

### 6.2 Uso de PDO

```php
// Consulta preparada
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Con named parameters
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->bindValue(":email", $email);
$stmt->execute();
```

### 6.3 Tablas Principales

| Tabla | Descripcion |
|-------|-------------|
| `users` | Usuarios del sistema |
| `roles` | Roles disponibles |
| `model_has_roles` | Relacion usuario-rol |
| `requests` | Solicitudes de servicio |
| `offers` | Ofertas de proveedores |
| `advisories` | Asesorias |
| `customers_advisories` | Relacion cliente-asesoria |
| `advisory_invoices` | Facturas de asesorias |
| `advisory_appointments` | Citas de asesorias |
| `notifications` | Notificaciones del sistema |
| `messages_v2` | Mensajes de chat |

---

## 7. APIS

### 7.1 Estructura de Respuesta

Todas las APIs usan `json_response()`:

```php
// En functions.php linea 165-177
function json_response($status, $message, $code, $data = [], $icon = "") {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => $status,           // "ok" o "ko"
        "message_html" => $message,    // Mensaje con HTML
        "message_plain" => strip_tags($message),  // Mensaje sin HTML
        "code" => $code,               // Codigo unico para debug
        "data" => $data,               // Datos adicionales
        "icon" => $icon                // Icono opcional
    ]);
    exit;
}
```

### 7.2 Ejemplo de API

```php
<?php
// api/ejemplo.php

if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

$nombre = trim($_POST['nombre'] ?? '');

if (empty($nombre)) {
    json_response("ko", "El nombre es requerido", 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO tabla (nombre) VALUES (?)");
    $stmt->execute([$nombre]);

    json_response("ok", "Guardado correctamente", 200, [
        'id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    json_response("ko", "Error: " . $e->getMessage(), 500);
}
```

### 7.3 APIs sin Autenticacion

Definidas en bold.php:

```php
$no_auth = ["/login", "/sign-up", "/recovery", "/restore", "/activate", ...];
$no_auth_api = ["/activate-with-password", "/_debug-session"];
```

---

## 8. FUNCIONES AUXILIARES

### 8.1 Verificacion de Roles

```php
// Verificar rol del usuario actual
admin()      // true si es administrador
cliente()    // true si es cliente (autonomo, empresa, particular)
proveedor()  // true si es proveedor
comercial()  // true si es comercial
asesoria()   // true si es asesoria
```

### 8.2 Notificaciones

```php
// Crear notificacion
notification($from_user_id, $to_user_id, $request_id, $subject, $message);

// Version extendida
notification_v2($from_user_id, $to_user_id, $request_id, $subject, $message, $title, $type);
```

### 8.3 Emails

```php
send_mail($to_address, $to_name, $subject, $body, $mid, $attachments = [], $from = SMTP_FROM);

// Ejemplo
send_mail(
    "usuario@email.com",
    "Juan Perez",
    "Bienvenido a Facilitame",
    $html_body,
    "welcome_" . time()
);
```

### 8.4 Utilidades

```php
// Debug
pretty($variable);  // Imprime variable formateada

// Saludo segun hora
hello();  // "Buenos dias" / "Buenas tardes" / "Buenas noches"

// Validar NIF/CIF/NIE
validate_nif_cif_nie("12345678A");  // Retorna 1 (NIF), 2 (CIF), 3 (NIE), -1/-2/-3 (invalido), 0 (formato incorrecto)
```

---

## 9. SISTEMA DE ROLES

### 9.1 Roles Disponibles

| ID | Nombre | Vista |
|----|--------|-------|
| 1 | administrador | administrador |
| 2 | proveedor | proveedor |
| 3 | comercial | comercial |
| 4 | autonomo | cliente |
| 5 | empresa | cliente |
| 6 | particular | cliente |
| 7 | asesoria | asesoria |

### 9.2 Vista vs Rol

- **Rol:** Rol real del usuario (autonomo, empresa, particular)
- **Vista:** Carpeta de paginas a usar (cliente agrupa autonomo/empresa/particular)

```php
USER["role"]  // "autonomo"
USER["view"]  // "cliente"
```

### 9.3 Permisos en Paginas

```php
// En controller
if (!cliente()) {
    header('Location: /login');
    exit;
}
```

---

## 10. ENTORNOS

### 10.1 Deteccion de Entorno

En `bold/vars.php`:

```php
if (strpos($_SERVER["SERVER_NAME"], "facilitame.test") !== false) {
    define("ENVIRONMENT", "LOCAL");
} elseif (strpos($_SERVER["SERVER_NAME"], "demo.facilitame.es") !== false) {
    define("ENVIRONMENT", "DEMO");
} else {
    define("ENVIRONMENT", "PRODUCTION");
}
```

### 10.2 Variables por Entorno

| Variable | LOCAL | DEMO | PRODUCTION |
|----------|-------|------|------------|
| ROOT_URL | http://facilitame.test | https://demo.facilitame.es | https://facilitame.smbnlreg.com |
| DB_NAME | facilitame | wlaopzex_facilitame_demo | smbnlreg_facilitame_pro |
| SMTP_PORT | 587 | 465 | 465 |

### 10.3 Constantes Importantes

```php
ROOT_DIR        // Ruta absoluta del proyecto
ROOT_URL        // URL base del sitio
DOCUMENTS_DIR   // Carpeta de documentos subidos
ADMIN_ID        // ID del usuario administrador
JWT_SECRET      // Clave para firmar JWT
```

---

## 11. DESARROLLO DE NUEVAS FUNCIONALIDADES

### 11.1 Crear Nueva Pagina

1. **Crear controlador** en `/controller/{rol}/mi-pagina.php`:
```php
<?php
if (!cliente()) {
    header('Location: /login');
    exit;
}

$info = [
    "page" => "mi-pagina",
    "title" => "Mi Pagina",
    "scripts" => ["mi-pagina"]
];

// Cargar datos
$datos = obtener_datos();
```

2. **Crear vista** en `/pages/{rol}/mi-pagina.php`:
```php
<div class="card">
    <div class="card-header">
        <h3><?= $info['title'] ?></h3>
    </div>
    <div class="card-body">
        <!-- Contenido -->
    </div>
</div>
```

3. **Crear JS** en `/assets/js/bold/mi-pagina.js`:
```javascript
$(document).ready(function() {
    // Logica de la pagina
});
```

### 11.2 Crear Nueva API

1. **Crear archivo** en `/api/mi-api.php`:
```php
<?php
if (!cliente()) {
    json_response("ko", "No autorizado", 403);
}

global $pdo;

// Procesar peticion
$param = $_POST['param'] ?? '';

// Validar
if (empty($param)) {
    json_response("ko", "Parametro requerido", 400);
}

// Ejecutar logica
try {
    // ...
    json_response("ok", "Exito", 200, ['resultado' => $data]);
} catch (Exception $e) {
    json_response("ko", $e->getMessage(), 500);
}
```

2. **Llamar desde JS**:
```javascript
$.ajax({
    url: '/api/mi-api',
    type: 'POST',
    data: { param: valor },
    success: function(result) {
        if (result.status === 'ok') {
            toastr.success(result.message_plain);
        } else {
            toastr.error(result.message_plain);
        }
    }
});
```

### 11.3 Agregar Campo a BD

1. **Crear migracion** en `/migrations/`:
```sql
-- migrations/2025_12_12_add_campo_tabla.sql
ALTER TABLE tabla ADD COLUMN nuevo_campo VARCHAR(255) DEFAULT NULL;
```

2. **Ejecutar en todos los entornos**

---

## 12. INTEGRACIONES EXTERNAS

### 12.1 Inmatic (OCR de Facturas)

Clase: `bold/classes/InmaticClient.php`

```php
$client = new InmaticClient($advisory_id);
$result = $client->uploadDocument($filePath, $fileName, $documentType, $metadata);
```

Verificar acceso:
```php
$planesConInmatic = ['pro', 'premium', 'enterprise'];
$hasAccess = in_array($advisory['plan'], $planesConInmatic) || $advisory['inmatic_trial'];
```

### 12.2 Google Calendar

Clase: `bold/classes/GoogleCalendarClient.php`

```php
$client = new GoogleCalendarClient();
$client->setAccessToken($access_token);
$events = $client->getEvents($calendarId, $timeMin, $timeMax);
```

### 12.3 Firebase (Push Notifications)

```php
// En utils/firebase-console-message.php
send_firebase_notification($token, $title, $body, $data);
```

### 12.4 Apple Push Notifications

```php
// En utils/apple-apn.php
send_apn_notification($device_token, $title, $body, $badge);
```

---

## 13. DESPLIEGUE

### 13.1 Archivos a Sincronizar

```bash
# Excluir del despliegue
uploads/
.env
.git/
node_modules/
*.log
```

### 13.2 Checklist Pre-Despliegue

- [ ] Probar localmente
- [ ] Ejecutar migraciones SQL
- [ ] Verificar constantes en vars.php
- [ ] Backup de BD destino

### 13.3 Checklist Post-Despliegue

- [ ] Verificar login
- [ ] Probar APIs criticas
- [ ] Verificar envio de emails
- [ ] Probar app movil contra nuevo entorno

---

## 14. CONVENIOS DE CODIGO

### 14.1 Nomenclatura

| Tipo | Convencion | Ejemplo |
|------|------------|---------|
| Variables PHP | snake_case | `$user_id` |
| Funciones PHP | snake_case | `get_user()` |
| Clases PHP | PascalCase | `InmaticClient` |
| Variables JS | camelCase | `userId` |
| Archivos PHP | kebab-case | `mi-pagina.php` |
| Tablas BD | snake_case | `advisory_invoices` |

### 14.2 Estructura de APIs

```php
<?php
// 1. Verificar autenticacion
if (!rol()) {
    json_response("ko", "No autorizado", 403);
}

// 2. Variables globales
global $pdo;

// 3. Obtener y validar parametros
$param = $_POST['param'] ?? '';
if (empty($param)) {
    json_response("ko", "Parametro requerido", 400);
}

// 4. Logica de negocio en try-catch
try {
    // ...
    json_response("ok", "Mensaje", 200, $data);
} catch (Exception $e) {
    json_response("ko", $e->getMessage(), 500);
}
```

### 14.3 Consultas SQL

- Siempre usar prepared statements
- Nunca concatenar variables en SQL
- Usar named parameters para claridad

```php
// CORRECTO
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND active = :active");
$stmt->execute(['id' => $id, 'active' => 1]);

// INCORRECTO - vulnerable a SQL injection
$stmt = $pdo->query("SELECT * FROM users WHERE id = $id");
```

---

## 15. DEPURACION

### 15.1 Activar Errores

En desarrollo, `vars.php` muestra todos los errores:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

### 15.2 Debug de Variables

```php
// Imprimir formateado
pretty($variable);

// Log a archivo
error_log("Debug: " . json_encode($variable));

// Log especifico
file_put_contents(ROOT_DIR . "/debug.log", date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
```

### 15.3 Debug de APIs

Crear endpoint temporal:
```php
// api/_debug-test.php
<?php
header('Content-Type: application/json');
echo json_encode([
    'user' => USER ?? null,
    'post' => $_POST,
    'get' => $_GET,
    'server' => $_SERVER['REQUEST_URI']
]);
```

### 15.4 Logs de Errores

- PHP errors: `/var/log/apache2/error.log` o similar
- Aplicacion: `ROOT_DIR . "/*.log"` (archivos personalizados)

### 15.5 Verificar Sesion/JWT

```php
// api/_debug-session.php
<?php
header('Content-Type: application/json');
echo json_encode([
    'has_cookie' => isset($_COOKIE['auth_token']),
    'environment' => ENVIRONMENT,
    'user' => defined('USER') ? USER : null
]);
```

---

## Apendice A: APIs Disponibles

### Autenticacion
- `POST /api/login` - Iniciar sesion
- `POST /api/sign-up` - Registro
- `POST /api/recovery` - Recuperar contrasena
- `POST /api/restore` - Restaurar contrasena

### Solicitudes
- `POST /api/request-create` - Crear solicitud
- `POST /api/request-update-status` - Actualizar estado
- `POST /api/request-upload-new-document` - Subir documento

### Ofertas
- `POST /api/offer-create` - Crear oferta
- `POST /api/offer-accept` - Aceptar oferta
- `POST /api/offer-upload` - Subir documento de oferta

### Asesorias
- `POST /api/advisory-upload-invoice` - Subir factura
- `POST /api/advisory-invoice-send-to-inmatic` - Enviar a Inmatic
- `POST /api/advisory-appointment-create` - Crear cita

### App Movil
- `POST /api/app-login` - Login movil
- `POST /api/app-dashboard` - Dashboard movil
- `POST /api/app-services` - Servicios
- `POST /api/app-token-save-fcm` - Guardar token push

---

## Apendice B: Errores Comunes

| Error | Causa | Solucion |
|-------|-------|----------|
| `SyntaxError: Unexpected token '<'` | API devuelve HTML en vez de JSON | Verificar que el archivo API existe y no tiene errores PHP |
| `No autorizado (403)` | Usuario no autenticado o rol incorrecto | Verificar JWT y permisos |
| `API no encontrada (404)` | Archivo no existe en /api/ ni /controller/ | Verificar nombre y ubicacion del archivo |
| `SQLSTATE[42S22]` | Columna no existe en BD | Verificar esquema de BD y ejecutar migraciones |

---

*Manual de Desarrollador - Facilitame v1.0*
