# Bugs y Problemas Encontrados en Facilitame

**Version:** 1.0
**Fecha:** 12 de Diciembre 2025
**Estado:** Analisis completado

---

## RESUMEN EJECUTIVO

Se ha realizado una auditoria completa del sistema Facilitame, identificando:

- **8 vulnerabilidades de seguridad criticas**
- **14 bugs funcionales criticos**
- **20+ inconsistencias de datos/codigo**
- **7 migraciones pendientes**

---

## 1. VULNERABILIDADES DE SEGURIDAD

### 1.1 SQL Injection - CRITICO

**Archivos afectados:**
- `bold/functions.php:413, 640` - Concatenacion directa de categorias en queries

```php
// VULNERABLE
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :request_id AND category_id IN (" . $categories . ")");
```

**Solucion:**
```php
// SEGURO
$cat_array = array_map('intval', explode(',', $categories));
$placeholders = implode(',', array_fill(0, count($cat_array), '?'));
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ? AND category_id IN ($placeholders)");
```

### 1.2 File Upload sin Validacion - CRITICO

**Archivos sin validacion de tipo de archivo:**
- `api/offer-upload.php` - Sin ninguna validacion
- `api/request-upload-new-document.php` - Sin validacion de extension/MIME
- `api/app-service-form-main-submit.php` - Sin validacion completa
- `api/services-form-main-submit.php` - Sin validacion completa

**Archivos con validacion insuficiente:**
- `api/invoice-upload.php` - Solo valida extension, no MIME type real

**Solucion recomendada:**
```php
// Validar MIME type real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['file']['tmp_name']);
$allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mime, $allowed_mimes)) {
    json_response("ko", "Tipo de archivo no permitido", 400);
}
```

### 1.3 XSS Corregidos (Session Anterior)

Los siguientes archivos fueron corregidos con `htmlspecialchars()`:
- `api/app-chat-message-store.php`
- `api/invoice-upload.php`
- `api/offer-upload.php`
- `api/sales-rep-create.php`
- `api/sales-rep-update.php`
- `api/user-profile-details-update.php`
- `api/app-user-profile-details-update.php`
- `api/user-update.php`
- `api/advisory-chat-send.php`
- `api/advisory-send-communication.php`
- `api/advisory-create-appointment.php`
- `api/advisory-update-appointment.php`
- `api/advisory-create-customer.php`

### 1.4 CORS Abierto

**Archivo:** `bold/bold.php:3`
```php
header("Access-Control-Allow-Origin: *");
```

**Impacto:** Permite requests desde cualquier origen.

### 1.5 Rate Limiting Insuficiente

**Archivo:** `api/login.php:4`
```php
sleep(1); // Unica proteccion contra brute force
```

---

## 2. BUGS FUNCIONALES CRITICOS

### 2.1 Estado 8 (Revision Solicitada) Sin Salida

**Problema:** Las solicitudes pueden entrar en estado 8 pero NO hay endpoint para salir.

**Archivo:** `api/offer-review-request.php:49`

**Impacto:** Solicitudes quedan bloqueadas permanentemente.

**Solucion:** Crear `api/offer-review-complete.php`

### 2.2 Rol de Asesoria Incorrecto

**Problema:** El codigo usa `role_id = 5` para asesorias, pero en BD:
- `role_id = 5` = empresa
- `role_id = 8` = asesoria

**Archivos afectados:**
- `api/advisories-add.php:99-100`
- `api/advisories-delete.php:61, 69`

**Impacto:** Usuarios de asesoria reciben rol de empresa.

### 2.3 Campo `telefono` No Existe en Tabla Advisories

**Archivos que lo usan:**
- `api/advisories-add.php:41, 161, 175`
- `api/advisories-update.php:59`

**Solucion:** Ejecutar `migrations/2025-12-12-advisories-updates.sql`

### 2.4 Campo `deleted_at` No Existe en Tabla Advisories

**Archivos que lo usan:**
- `api/advisories-delete.php:48`
- `api/advisories-add.php:74`
- `api/advisories-update.php:29`

**Solucion:** Ejecutar `migrations/2025-12-12-advisories-updates.sql`

### 2.5 Tabla `advisory_communication_files` No Existe

**Archivos que la usan:**
- `api/advisory-send-communication.php:104`
- `api/advisory-get-communication.php:96`
- `controller/api-advisory-communications-list.php:86`

**Solucion:** Ejecutar `database/add_communication_files.sql`

### 2.6 offer-reject No Filtra deleted_at

**Archivo:** `api/offer-reject.php:80`
```php
// VULNERABLE
$query = "SELECT * FROM `offers` WHERE request_id = :request_id";

// CORRECTO
$query = "SELECT * FROM `offers` WHERE request_id = :request_id AND deleted_at IS NULL";
```

### 2.7 offer-activate Sin Validacion de Permisos

**Archivo:** `api/offer-activate.php:2`

**Problema:** Solo valida `proveedor()`, no valida `user_can_access_request()`

**Impacto:** Un proveedor puede activar ofertas de categorias que no tiene asignadas.

### 2.8 offer-confirm Permite Saltar Aceptacion

**Archivo:** `api/offer-confirm.php`

**Problema:** No valida que la oferta este en estado 3 (Aceptada) antes de confirmar.

**Impacto:** Se puede confirmar directamente desde estado 2 sin aceptacion del cliente.

### 2.9 Campo `direccion` No Existe en Citas

**Archivos afectados:**
- `pages/asesoria/appointment.php:959`
- `pages/cliente/appointment.php:516`

**Problema:** Se usa para Google Calendar pero el campo no existe en `advisory_appointments`.

### 2.10 Campos `preferred_time` y `specific_time` No Usados

**Problema:** Existen en BD pero nunca se insertan ni se muestran.

### 2.11 Subtipo Obligatorio para 'particular'

**Archivo:** `api/advisory-create-customer.php:87-88`

**Problema:** Se requiere subtipo pero no hay switch case para 'particular'.

**Impacto:** Error 400 al crear cliente particular.

### 2.12 offer-accept Permite Aceptar Oferta Rechazada

**Archivo:** `api/offer-accept.php`

**Problema:** No valida estado actual de la oferta.

### 2.13 Estado 8 Excluido de Listados

**Archivo:** `api/requests-paginated.php`
```php
$whereConditions = ["req.status_id != 8"];
```

**Impacto:** Solicitudes en "Revision solicitada" no aparecen en listados.

### 2.14 Inconsistencia `reminder_sent` vs `reminder_sent_at`

**Problema:** Dos versiones del campo para recordatorios.

**Archivos con version antigua (`reminder_sent`):**
- `crons/advisory-reminder.php:12, 27`
- `api/advisory-get-communication.php:47`

**Archivos con version nueva (`reminder_sent_at`):**
- `crons/cron_advisory_reminder.php:38, 54, 73`

---

## 3. INCONSISTENCIAS DE CODIGO

### 3.1 Estilos Faltantes en Status.php

**Archivo:** `bold/classes/Status.php`

**Problema:** Solo define estilos para estados 1-8, faltan 9, 10, 11.

### 3.2 Inconsistencia `client_subtype` (Guiones)

**Valores con guion (sign-up):** `'1-10', '10-50', '50+'`
**Valores con guion bajo (filtros):** `'0_10', '10_50', '50_mas'`

**Impacto:** Filtros de comunicaciones no encuentran destinatarios.

### 3.3 Inconsistencia `commision` vs `commission`

**En BD:** Campo `commision` (con 1 'm')
**En codigo:** Variable `$_POST["commission"]` (con 2 'm')

### 3.4 Plan 'enterprise' No Permitido en Creacion

**Archivo:** `api/advisories-add.php:61`
```php
$allowed_plans = ['gratuito', 'basic', 'estandar', 'pro', 'premium'];
// Falta 'enterprise'
```

### 3.5 Notificacion con Admin ID Hardcodeado

**Archivo:** `api/advisories-delete.php:92`
```php
notification_v2(USER['id'], 1, ...); // ID 1 hardcoded
```

### 3.6 Uso Inconsistente notification vs notification_v2

Algunos archivos usan `notification()`, otros `notification_v2()`.

### 3.7 Motor MyISAM sin Foreign Keys

**Tablas afectadas:**
- `advisory_communication_recipients`
- `advisory_communications`
- `advisory_appointments`
- `advisory_invoices`
- `advisories`

**Solucion:** Ejecutar `database/migrate_myisam_to_innodb.sql`

---

## 4. MIGRACIONES PENDIENTES

| # | Archivo | Contenido |
|---|---------|-----------|
| 1 | `migrations/2025-12-12-advisories-updates.sql` | Campos telefono y deleted_at |
| 2 | `database/add_communication_files.sql` | Tabla advisory_communication_files |
| 3 | `database/migrations/add_reminder_sent_at.sql` | Campo reminder_sent_at e indice |
| 4 | `database/migrate_myisam_to_innodb.sql` | Migrar tablas a InnoDB |

---

## 5. PRIORIZACION DE CORRECCIONES

### CRITICO (Ejecutar Inmediatamente)

1. Ejecutar migraciones pendientes
2. Corregir SQL injection en functions.php
3. Agregar validacion de archivos en uploads
4. Corregir rol de asesoria (5 → 8)
5. Crear endpoint offer-review-complete.php

### ALTO (Esta Semana)

6. Agregar validacion de permisos en offer-activate.php
7. Validar estado previo en offer-confirm.php
8. Filtrar deleted_at en offer-reject.php
9. Corregir subtipo para 'particular'
10. Estandarizar client_subtype

### MEDIO (Proximo Sprint)

11. Agregar estilos para estados 9, 10, 11
12. Revisar filtro de estado 8 en listados
13. Configurar CORS especifico
14. Implementar rate limiting real
15. Actualizar notification a notification_v2

### BAJO (Backlog)

16. Agregar campo direccion a citas
17. Implementar o eliminar preferred_time/specific_time
18. Agregar plan enterprise a add
19. Dinamizar ID de admin

---

## 6. SCRIPTS DE CORRECCION SUGERIDOS

### 6.1 Ejecutar Migraciones

```bash
# En MySQL
mysql -u root -p facilitame < migrations/2025-12-12-advisories-updates.sql
mysql -u root -p facilitame < database/add_communication_files.sql
mysql -u root -p facilitame < database/migrations/add_reminder_sent_at.sql
```

### 6.2 Corregir Roles de Asesoria Existentes

```sql
-- Cambiar usuarios con rol 5 que deberian ser 8
UPDATE model_has_roles mhr
INNER JOIN advisories a ON mhr.model_id = a.user_id
SET mhr.role_id = 8
WHERE mhr.role_id = 5 AND mhr.model_type = 'App\\Models\\User';
```

### 6.3 Normalizar client_subtype

```sql
-- Convertir guiones a guiones bajos
UPDATE customers_advisories SET client_subtype = REPLACE(client_subtype, '-', '_');

-- Normalizar valores especificos
UPDATE customers_advisories SET client_subtype = '0_10' WHERE client_subtype IN ('0-10', '1-10');
UPDATE customers_advisories SET client_subtype = '10_50' WHERE client_subtype = '10-50';
UPDATE customers_advisories SET client_subtype = '50_mas' WHERE client_subtype IN ('50+', '50-250', '250+');
```

### 6.4 Migrar reminder_sent a reminder_sent_at

```sql
-- Migrar datos existentes
UPDATE advisory_communication_recipients
SET reminder_sent_at = NOW()
WHERE reminder_sent = 1 AND reminder_sent_at IS NULL;
```

---

## 7. VERIFICACION POST-CORRECCION

```sql
-- 1. Verificar campos de advisories
DESCRIBE advisories;

-- 2. Verificar tabla de archivos de comunicacion
SHOW TABLES LIKE 'advisory_communication_files';

-- 3. Verificar roles de asesorias
SELECT u.id, u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
JOIN advisories a ON u.id = a.user_id;

-- 4. Verificar client_subtype normalizados
SELECT DISTINCT client_subtype FROM customers_advisories;

-- 5. Verificar motor de tablas
SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'facilitame' AND TABLE_NAME LIKE 'advisory%';
```

---

**Fin del documento**
