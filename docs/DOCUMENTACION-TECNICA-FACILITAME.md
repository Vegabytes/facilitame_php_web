# FACILITAME - Documentacion Tecnica y Funcional

**Version:** 1.0
**Fecha:** 12 de Diciembre 2025
**Autor:** Documentacion generada automaticamente

---

## INDICE

1. [Vision General](#1-vision-general)
2. [Stack Tecnologico](#2-stack-tecnologico)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Base de Datos](#4-base-de-datos)
5. [Sistema de Roles y Permisos](#5-sistema-de-roles-y-permisos)
6. [Modulos Funcionales](#6-modulos-funcionales)
7. [APIs](#7-apis)
8. [Integraciones Externas](#8-integraciones-externas)
9. [Aplicacion Movil](#9-aplicacion-movil)
10. [Sistema de Notificaciones](#10-sistema-de-notificaciones)
11. [Problemas Conocidos](#11-problemas-conocidos)
12. [Roadmap de Mejoras](#12-roadmap-de-mejoras)

---

## 1. VISION GENERAL

### 1.1 Que es Facilitame?

Facilitame es una plataforma web y movil que conecta **clientes** (particulares, autonomos y empresas) con **proveedores de servicios** especializados, facilitando la gestion de solicitudes, ofertas y seguimiento de servicios. Adicionalmente, incluye un modulo de **asesorias** para gestion fiscal y contable.

### 1.2 Proposito del Sistema

- **Para Clientes:** Solicitar servicios, recibir ofertas, gestionar documentacion y comunicarse con proveedores
- **Para Proveedores:** Recibir solicitudes, enviar ofertas, gestionar el ciclo de vida del servicio
- **Para Asesorias:** Gestionar clientes, citas, comunicaciones y facturas
- **Para Comerciales:** Captar clientes mediante codigos de venta y recibir comisiones
- **Para Administradores:** Gestion completa de la plataforma

### 1.3 Alcance Funcional

| Modulo | Estado | Descripcion |
|--------|--------|-------------|
| Solicitudes | Produccion | Ciclo completo de solicitudes de servicios |
| Ofertas | Produccion | Sistema de ofertas y aceptacion |
| Chat | Produccion | Mensajeria entre usuarios |
| Documentos | Produccion | Gestion de archivos adjuntos |
| Asesorias | Produccion | Sistema completo de gestion de asesorias |
| App Movil | Produccion | iOS y Android |
| Notificaciones | Produccion | Push, email e in-app |
| Facturacion | Parcial | Upload de facturas (sin integracion Inmatic) |

---

## 2. STACK TECNOLOGICO

### 2.1 Backend

| Tecnologia | Version | Uso |
|------------|---------|-----|
| PHP | 8.x | Lenguaje principal del backend |
| MySQL | 8.0+ | Base de datos relacional |
| PDO | - | Capa de acceso a datos |
| Composer | - | Gestion de dependencias |
| Ramsey/UUID | - | Generacion de identificadores unicos |

### 2.2 Frontend Web

| Tecnologia | Uso |
|------------|-----|
| HTML5 | Estructura |
| CSS3 / SCSS | Estilos |
| Bootstrap 5 | Framework CSS |
| JavaScript ES6+ | Logica del cliente |
| jQuery | Manipulacion DOM y AJAX |
| Metronic 8 | Template administrativo |
| DataTables | Tablas interactivas |
| ApexCharts | Graficos |
| Flatpickr | Selector de fechas |
| SweetAlert2 | Dialogos y alertas |
| Toastr | Notificaciones toast |

### 2.3 Aplicacion Movil

| Plataforma | Tecnologia |
|------------|------------|
| iOS | Swift / SwiftUI |
| Android | Kotlin / Jetpack Compose |

### 2.4 Servicios Externos

| Servicio | Proposito |
|----------|-----------|
| Firebase | Push notifications |
| SMTP | Envio de emails |
| Google Calendar | Integracion de citas |
| Inmatic (Pendiente) | Gestion de facturas OCR |

### 2.5 Servidor

| Componente | Especificacion |
|------------|----------------|
| Servidor Web | Apache/Nginx |
| OS | Linux |
| Entorno Local | Laragon (Windows/WSL) |

---

## 3. ARQUITECTURA DEL SISTEMA

### 3.1 Estructura de Directorios

```
/Facilitame
├── api/                    # Endpoints API (PHP)
├── assets/                 # Recursos estaticos
│   ├── css/               # Estilos
│   ├── js/                # JavaScript
│   │   └── custom/        # JS personalizado
│   ├── media/             # Imagenes e iconos
│   └── plugins/           # Librerias terceros
├── bold/                   # Core del sistema
│   ├── classes/           # Clases PHP
│   ├── functions.php      # Funciones globales
│   └── globals.php        # Variables globales
├── components/            # Componentes reutilizables
├── controller/            # Controladores adicionales
├── database/              # Migraciones SQL
├── docs/                  # Documentacion
├── documents/             # Archivos subidos
├── email-templates/       # Plantillas de email
├── layout/                # Layouts principales
│   └── partials/         # Parciales del layout
├── migrations/            # Migraciones SQL
├── pages/                 # Vistas por rol
│   ├── administrador/    # Paginas de admin
│   ├── asesoria/         # Paginas de asesorias
│   ├── cliente/          # Paginas de clientes
│   ├── comercial/        # Paginas de comerciales
│   └── proveedor/        # Paginas de proveedores
├── partials/              # Componentes parciales
├── vendor/                # Dependencias Composer
├── config.php             # Configuracion principal
├── index.php              # Punto de entrada
└── router.php             # Enrutador principal
```

### 3.2 Patron de Arquitectura

El sistema sigue un patron **MVC simplificado**:

- **Modelo:** Funciones en `bold/functions.php` + consultas directas PDO
- **Vista:** Archivos PHP en `/pages/` y `/components/`
- **Controlador:** Archivos en `/api/` y `/controller/`

### 3.3 Flujo de una Peticion

```
1. index.php → Inicializa sesion y configuracion
2. router.php → Determina la ruta
3. Layout principal → Carga estructura HTML
4. Pagina especifica → Renderiza contenido
5. APIs (AJAX) → Operaciones asincronas
```

### 3.4 Autenticacion

- **Sesiones PHP** para web
- **Token Bearer** para API movil
- **Firebase Token** para push notifications

---

## 4. BASE DE DATOS

### 4.1 Resumen de Tablas

**Total de tablas:** 51 tablas activas

| Categoria | Tablas |
|-----------|--------|
| Usuarios y Roles | users, roles, model_has_roles, permissions |
| Solicitudes | requests, requests_statuses, request_files, request_status_log, request_incidents |
| Ofertas | offers, provider_comments |
| Notificaciones | notifications |
| Mensajeria | messages_v2, advisory_messages |
| Asesorias | advisories, customers_advisories, advisory_appointments, advisory_communications, advisory_invoices |
| Comerciales | sales_codes, customers_sales_codes, commissions |
| Catalogos | categories, regions, file_types, incident_categories |
| Sistema | log, parameters, failed_jobs |

### 4.2 Tablas Principales

#### USERS (Usuarios)
```sql
- id (PK, bigint unsigned)
- name, lastname, phone, email (datos basicos)
- password (hash)
- nif_cif (identificacion fiscal)
- firebase_token (push notifications)
- verification_token, email_verified_at (verificacion email)
- region_code (ubicacion)
- platform (web/app)
- deleted_at (soft delete)
```

#### REQUESTS (Solicitudes)
```sql
- id (PK)
- category_id (FK → categories)
- user_id (FK → users) - cliente
- status_id (FK → requests_statuses)
- form_values (JSON) - datos del formulario
- code (codigo comercial)
- origin (web/app)
- deleted_at, deleted_by, delete_reason
- rescheduled_at, reactivation_reason
```

#### OFFERS (Ofertas)
```sql
- id (PK)
- request_id (FK → requests)
- provider_id (FK → users)
- offer_title, offer_content
- status_id (FK → requests_statuses)
- total_amount, commision_type_id, commision
- expires_at, activated_at
- rejected_at, reject_reason
- deleted_at
```

#### ADVISORIES (Asesorias)
```sql
- id (PK)
- user_id (FK → users)
- cif, razon_social, direccion, email_empresa, telefono
- codigo_identificacion (UNIQUE) - para clientes
- plan (gratuito/basic/estandar/pro/premium/enterprise)
- estado (pendiente/activo/suspendido)
- deleted_at
```

#### ADVISORY_APPOINTMENTS (Citas)
```sql
- id (PK)
- advisory_id, customer_id
- type (llamada/reunion_presencial/reunion_virtual)
- department (contabilidad/fiscalidad/laboral/gestion)
- status (solicitado/agendado/finalizado/cancelado)
- scheduled_date, proposed_date
- needs_confirmation_from, proposed_by (sistema v2)
- notes_advisory, notes_customer
- cancellation_reason, cancelled_by, cancelled_at
```

### 4.3 Estados de Solicitudes

| ID | Estado | Uso |
|----|--------|-----|
| 1 | Iniciado | Solicitud creada |
| 2 | Oferta Disponible | Proveedor subio oferta |
| 3 | Aceptada | Cliente acepto oferta |
| 4 | En curso | Proveedor confirmo |
| 5 | Rechazada | Cliente rechazo |
| 6 | Llamada sin respuesta | Lead sin contacto |
| 7 | Activada | Servicio activo |
| 8 | Revision solicitada | Cliente pide revision |
| 9 | Eliminada | Soft delete |
| 10 | Aplazada | Reagendada |
| 11 | Desactivada | Solo para ofertas |

### 4.4 Diagrama de Relaciones Principales

```
users ─────┬──→ requests ──→ offers
           │         │
           │         └──→ request_files
           │         └──→ notifications
           │
           ├──→ advisories ──→ customers_advisories
           │         │
           │         └──→ advisory_appointments
           │         └──→ advisory_communications
           │         └──→ advisory_invoices
           │
           └──→ model_has_roles ──→ roles
```

---

## 5. SISTEMA DE ROLES Y PERMISOS

### 5.1 Roles Disponibles

| ID | Rol | Descripcion |
|----|-----|-------------|
| 2 | proveedor | Ofrece servicios |
| 3 | administrador | Control total |
| 4 | autonomo | Cliente autonomo |
| 5 | empresa | Cliente empresa |
| 6 | particular | Cliente particular |
| 7 | comercial | Capta clientes |
| 8 | asesoria | Gestiona asesorias |
| 9 | comunidad | Cliente comunidad |
| 10 | asociacion | Cliente asociacion |

### 5.2 Funciones de Verificacion

```php
admin()      // Verifica si es administrador
cliente()    // Verifica si es cliente (4,5,6,9,10)
proveedor()  // Verifica si es proveedor
comercial()  // Verifica si es comercial
asesoria()   // Verifica si es asesoria
```

### 5.3 Permisos por Rol

| Funcionalidad | Admin | Proveedor | Cliente | Comercial | Asesoria |
|---------------|-------|-----------|---------|-----------|----------|
| Ver solicitudes | Todas | Sus categorias | Propias | Sus clientes | - |
| Crear ofertas | Si | Si | No | No | No |
| Aceptar ofertas | No | No | Si | No | No |
| Gestionar asesorias | Si | No | No | No | Si |
| Ver comisiones | Si | No | No | Si | No |
| Eliminar usuarios | Si | No | No | No | No |

---

## 6. MODULOS FUNCIONALES

### 6.1 Modulo de Solicitudes

#### Flujo Completo

```
1. Cliente crea solicitud → Estado: Iniciado (1)
2. Sistema notifica a proveedores de la categoria
3. Proveedor sube oferta → Estado: Oferta Disponible (2)
4. Cliente acepta oferta → Estado: Aceptada (3)
5. Proveedor confirma (+ importe/comision) → Estado: En curso (4)
6. Proveedor activa (+ fecha vencimiento) → Estado: Activada (7)
7. [Opcional] Cliente solicita revision → Estado: Revision solicitada (8)
```

#### APIs Principales

| Endpoint | Metodo | Funcion |
|----------|--------|---------|
| `/api/services-form-main-submit` | POST | Crear solicitud |
| `/api/request-delete` | POST | Eliminar solicitud |
| `/api/request-reschedule` | POST | Aplazar solicitud |
| `/api/request-reactivate` | POST | Reactivar solicitud |
| `/api/request-upload-new-document` | POST | Subir documento |

### 6.2 Modulo de Ofertas

#### Estados de Ofertas

| Estado | Descripcion |
|--------|-------------|
| 2 | Disponible (recien subida) |
| 3 | Aceptada por cliente |
| 4 | Confirmada por proveedor |
| 5 | Rechazada |
| 7 | Activada |
| 11 | Desactivada (historica) |

#### APIs Principales

| Endpoint | Metodo | Funcion |
|----------|--------|---------|
| `/api/offer-upload` | POST | Subir oferta |
| `/api/offer-accept` | POST | Cliente acepta |
| `/api/offer-reject` | POST | Cliente rechaza |
| `/api/offer-confirm` | POST | Proveedor confirma |
| `/api/offer-activate` | POST | Proveedor activa |
| `/api/offer-withdraw` | POST | Proveedor retira |
| `/api/offer-review-request` | POST | Cliente solicita revision |

### 6.3 Modulo de Asesorias

#### Funcionalidades

1. **Gestion de Clientes**
   - Crear nuevos clientes
   - Vincular clientes existentes
   - Tipos: autonomo, empresa, particular, comunidad, asociacion

2. **Citas**
   - Tipos: llamada, reunion presencial, reunion virtual
   - Departamentos: contabilidad, fiscalidad, laboral, gestion
   - Sistema de propuestas v2 (confirmacion bidireccional)

3. **Comunicaciones**
   - Envio masivo o selectivo
   - Niveles: leve, media, importante
   - Soporte para archivos adjuntos

4. **Facturas**
   - Clientes suben facturas a la asesoria
   - Asesoria sube facturas para clientes
   - Clasificacion: gasto/ingreso

#### Planes Disponibles

| Plan | Precio Anual | Caracteristicas |
|------|--------------|-----------------|
| Gratuito | 0€ | Funciones basicas |
| Basic | 300€ | + Envio facturas |
| Estandar | 650€ | + Comunicaciones |
| Pro | 1,799€ | + Integracion Inmatic |
| Premium | 2,799€ | Funciones avanzadas |
| Enterprise | 5,799€ | Personalizacion total |

#### APIs de Asesorias

| Endpoint | Funcion |
|----------|---------|
| `/api/advisories-add` | Crear asesoria |
| `/api/advisories-update` | Actualizar asesoria |
| `/api/advisories-delete` | Eliminar asesoria |
| `/api/advisory-create-customer` | Crear cliente |
| `/api/advisory-link-customer` | Vincular cliente existente |
| `/api/advisory-send-communication` | Enviar comunicacion |
| `/api/advisory-create-appointment` | Crear cita |
| `/api/advisory-update-appointment` | Actualizar cita |
| `/api/advisory-upload-invoice` | Cliente sube factura |
| `/api/advisory-upload-customer-invoices` | Asesoria sube factura |

### 6.4 Modulo de Comerciales

#### Sistema de Codigos de Venta

- Cada comercial tiene un codigo unico
- Los clientes se registran con el codigo
- El comercial recibe comisiones por servicios activados

#### Tablas Involucradas

- `sales_codes` - Codigos de comerciales
- `customers_sales_codes` - Relacion cliente-comercial
- `commissions` - Tipos de comision
- `commissions_admin` - Registro de comisiones

### 6.5 Modulo de Chat

#### Tipos de Chat

1. **Chat de Ofertas** (messages_v2)
   - Entre cliente y proveedor
   - Asociado a una oferta/solicitud

2. **Chat de Asesorias** (advisory_messages)
   - Entre cliente y asesoria
   - Puede estar asociado a una cita

---

## 7. APIs

### 7.1 Estructura General

```php
// Patron comun de respuesta
json_response("ok", "Mensaje exito", CODIGO, $data);
json_response("ko", "Mensaje error", CODIGO);
```

### 7.2 APIs de Solicitudes

| Endpoint | Parametros | Respuesta |
|----------|------------|-----------|
| `services-form-main-submit` | category_id, form, code?, documents[] | request_id |
| `request-delete` | request_id, delete_reason? | success |
| `request-reschedule` | request_id, date | success |
| `requests-paginated` | page, limit, filters | requests[], total |

### 7.3 APIs de Ofertas

| Endpoint | Parametros | Respuesta |
|----------|------------|-----------|
| `offer-upload` | request_id, offer_title, offer_content, file? | offer_id |
| `offer-accept` | offer_id, request_id | success |
| `offer-confirm` | offer_id, commission, total_amount? | success |
| `offer-activate` | offer_id, expires_at | success |

### 7.4 APIs de Asesorias

| Endpoint | Parametros | Respuesta |
|----------|------------|-----------|
| `advisory-create-customer` | name, lastname, email, client_type, subtype | customer_id |
| `advisory-create-appointment` | customer_id, type, department, proposed_date, reason | appointment_id |
| `advisory-send-communication` | subject, message, importance, target_type, files[]? | communication_id |

### 7.5 APIs Moviles

| Endpoint | Plataforma | Funcion |
|----------|------------|---------|
| `app-login` | iOS/Android | Autenticacion |
| `app-service-form-main-submit` | iOS/Android | Crear solicitud |
| `app-chat-message-store` | iOS/Android | Enviar mensaje |
| `app-user-profile-details-update` | iOS/Android | Actualizar perfil |

---

## 8. INTEGRACIONES EXTERNAS

### 8.1 Firebase (Push Notifications)

**Configuracion:**
- Token almacenado en `users.firebase_token`
- Envio mediante API REST de Firebase

**Flujo:**
1. App movil obtiene token de Firebase
2. App envia token al backend
3. Backend almacena token
4. Backend envia push cuando hay notificaciones

### 8.2 Email (SMTP)

**Funcion principal:** `send_mail($to, $name, $subject, $body, $code)`

**Plantillas en:** `/email-templates/`
- `request-create.php` - Nueva solicitud
- `customer-activation.php` - Activacion de cuenta
- `appointment-*.php` - Notificaciones de citas

### 8.3 Google Calendar

**Uso:** Integracion en paginas de citas para agregar eventos al calendario

**Implementacion:** URL con parametros para crear evento

### 8.4 Inmatic (Pendiente)

**Proposito:** Gestion automatizada de facturas con OCR

**Endpoints disponibles (segun documentacion API v1.0.15):**
- Autenticacion: Token Bearer
- Documentos: Upload, listado, estados
- Empresas: CRUD
- Proveedores/Clientes: CRUD
- Webhooks: Suscripciones

**Estado:** No implementado actualmente

---

## 9. APLICACION MOVIL

### 9.1 Plataformas

| Plataforma | Tecnologia | Estado |
|------------|------------|--------|
| iOS | Swift/SwiftUI | Produccion |
| Android | Kotlin/Compose | Produccion |

### 9.2 Funcionalidades Moviles

- Login/Registro
- Crear solicitudes
- Ver ofertas
- Chat con proveedores
- Notificaciones push
- Perfil de usuario
- Gestion de documentos

### 9.3 APIs Especificas para App

Las APIs moviles usan el prefijo `app-`:
- `app-login.php`
- `app-service-form-main-submit.php`
- `app-chat-message-store.php`
- `app-user-profile-details-update.php`

### 9.4 Deteccion de Plataforma

```php
define('IS_MOBILE_APP', isset($_SERVER['HTTP_X_APP_PLATFORM']));
```

---

## 10. SISTEMA DE NOTIFICACIONES

### 10.1 Tipos de Notificaciones

| Tipo | Descripcion | Canal |
|------|-------------|-------|
| In-app | Notificaciones dentro de la plataforma | Web/App |
| Email | Correo electronico | SMTP |
| Push | Notificaciones moviles | Firebase |

### 10.2 Funciones de Notificacion

```php
// Notificacion simple (in-app)
notification($sender_id, $receiver_id, $request_id, $title, $description);

// Notificacion completa (in-app + email)
notification_v2($sender_id, $receiver_id, $request_id, $title, $description,
                $email_subject, $template, $data);
```

### 10.3 Tabla notifications

```sql
- id, sender_id, receiver_id, user_id
- title, description
- request_id (opcional)
- status (0=no leida, 1=leida)
- created_at, updated_at
```

### 10.4 Eventos que Generan Notificaciones

| Evento | Destinatarios |
|--------|---------------|
| Nueva solicitud | Proveedor, Comercial |
| Nueva oferta | Cliente |
| Oferta aceptada | Proveedor |
| Oferta rechazada | Proveedor |
| Documento subido | Proveedor, Admin |
| Nueva cita | Asesoria/Cliente |
| Comunicacion enviada | Clientes |

---

## 11. PROBLEMAS CONOCIDOS

### 11.1 Bugs Criticos

| # | Descripcion | Archivo | Impacto |
|---|-------------|---------|---------|
| 1 | Estado 8 sin salida - citas bloqueadas | offer-review-request.php | Alto |
| 2 | Campo `direccion` no existe en citas | appointment.php | Medio |
| 3 | Rol asesoria incorrecto (5 vs 8) | advisories-*.php | Alto |
| 4 | Tabla advisory_communication_files no existe | advisory-send-communication.php | Alto |

### 11.2 Inconsistencias de Datos

| # | Descripcion | Solucion |
|---|-------------|----------|
| 1 | offer-reject no filtra deleted_at | Agregar AND deleted_at IS NULL |
| 2 | offer-activate hace soft delete sin cambiar status_id | Sincronizar ambos campos |
| 3 | Estado 8 excluido de listados paginados | Revisar si es intencional |
| 4 | Campos preferred_time, specific_time no usados | Eliminar o implementar |

### 11.3 Vulnerabilidades Corregidas

- XSS en chat, perfiles, ofertas (Corregido con htmlspecialchars)
- Nombres de archivo sin sanitizar (Corregido con preg_replace)

### 11.4 Migraciones Pendientes

1. `migrations/2025-12-12-advisories-updates.sql` - Campos telefono y deleted_at
2. `database/add_communication_files.sql` - Tabla de archivos de comunicaciones

---

## 12. ROADMAP DE MEJORAS

### 12.1 Corto Plazo (Inmediato)

1. **Ejecutar migraciones pendientes**
2. **Corregir rol de asesoria (5 → 8)**
3. **Crear endpoint offer-review-complete.php**
4. **Agregar validacion de permisos en offer-activate.php**

### 12.2 Mediano Plazo

1. **Implementar integracion Inmatic**
   - Autenticacion con token
   - Upload automatico de facturas
   - Webhooks para estados

2. **Mejorar sistema de citas**
   - Validacion de horarios de negocio
   - Prevencion de citas duplicadas
   - Zona horaria explicita

3. **Optimizar consultas**
   - Agregar indices faltantes
   - Optimizar queries con JOINs

### 12.3 Largo Plazo

1. **Separar estados de ofertas** (tabla offer_statuses)
2. **Implementar maquina de estados** para transiciones
3. **Sistema de auditorias completo**
4. **Dashboard de analytics**
5. **API REST documentada con OpenAPI/Swagger**

---

## ANEXOS

### A. Variables de Configuracion

```php
// config.php
define('ROOT_DIR', '/path/to/facilitame');
define('ROOT_URL', 'https://facilitame.com');
define('DOCUMENTS_DIR', 'documents');
define('DEBUG', false);
define('ADMIN_ID', 1);
```

### B. Constantes de Usuario

```php
// Disponibles globalmente tras login
USER['id']
USER['name']
USER['email']
USER['role']
```

### C. Funciones Utiles

```php
// Autenticacion
admin(), cliente(), proveedor(), comercial(), asesoria()

// Acceso a datos
get_user($id), get_request($id), get_offer($id)
get_category($id), get_request_provider($request_id)

// Permisos
user_can_access_request($request_id)

// Notificaciones
notification(), notification_v2()

// Utilidades
json_response(), send_mail(), app_log()
```

### D. Estructura de Respuesta API

```json
{
  "status": "ok|ko|error|exists",
  "message": "Mensaje descriptivo",
  "code": 1234567890,
  "data": { ... }
}
```

---

**Fin del documento**

*Generado automaticamente - Facilitame v1.0*
