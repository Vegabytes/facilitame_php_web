# AUDITORÍA COMPLETA - FACILITAME

**Fecha**: 10 de Diciembre 2025
**Analizado por**: Claude Code Assistant
**Versión del documento**: 1.0

---

## ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Bugs Reportados por el Equipo](#bugs-reportados)
3. [Análisis de Seguridad](#seguridad)
4. [Sistema de Notificaciones](#notificaciones)
5. [SEO y Meta Tags](#seo)
6. [Accesibilidad (WCAG)](#accesibilidad)
7. [Rendimiento Frontend](#rendimiento)
8. [Sistema de Autenticación](#autenticacion)
9. [Base de Datos](#base-de-datos)
10. [Plan de Acción Priorizado](#plan-accion)

---

## 1. RESUMEN EJECUTIVO {#resumen-ejecutivo}

### Estado General del Proyecto

| Área | Puntuación | Estado |
|------|------------|--------|
| Seguridad | 55/100 | Requiere atención |
| Funcionalidad | 70/100 | Bugs activos |
| SEO | 40/100 | Crítico (robots.txt bloquea) |
| Accesibilidad | 62/100 | Mejoras necesarias |
| Rendimiento | 45/100 | Assets muy pesados |
| Base de Datos | 65/100 | MyISAM + FKs faltantes |

### Métricas del Proyecto

| Métrica | Valor |
|---------|-------|
| Archivos PHP | 350+ |
| Líneas en functions.php | 2,278 |
| Tablas en BD | 37 |
| Bugs reportados activos | 16 |
| Vulnerabilidades de seguridad | 8 |
| Assets totales | ~100 MB |

---

## 2. BUGS REPORTADOS POR EL EQUIPO {#bugs-reportados}

### Bugs Críticos (Arreglar inmediatamente)

| ID | Bug | Causa Raíz | Archivo |
|----|-----|-----------|---------|
| BUG-002 | Error al iniciar incidencia | Solo funciona si `status_id = 7` (Activa) | `api/incident-report.php:6-18` |
| BUG-015 | Error "no puedes comunicar incidencia" #1162 | `user_can_access_request()` devuelve false | `api/incident-report.php:2` |
| BUG-016 | Solicitar revisión no funciona | Solo permite a clientes (`!cliente()`) | `api/offer-review-request.php:2` |

### Código del BUG-002 y BUG-015:
```php
// api/incident-report.php líneas 1-18
if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes comunicar una incidencia en esta solicitud.", 1598357393);
}

// Solo permite si status_id = 7 (Activa)
$query = "SELECT * FROM `requests` WHERE 1
AND deleted_at IS NULL
AND id = :request_id
AND status_id = 7";  // <-- PROBLEMA: Solo "Activa"
```

### Código del BUG-016:
```php
// api/offer-review-request.php líneas 1-4
if (!cliente() || !user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes solicitar una revisión de esta oferta.", 579238927);
}
// Solo clientes pueden solicitar revisión
```

### Bugs de Notificaciones

| ID | Bug | Causa Raíz |
|----|-----|-----------|
| BUG-003 | Notificaciones de asesoría no acceden a solicitud | Link incorrecto en notificaciones |
| BUG-004 | Algunas notificaciones no aparecen en panel | `get_notifications()` filtra por `request_id` |
| BUG-006 | "Ver todas" redirige a comunicaciones | `_notifications-menu.php:82` redirige a `/communications` |
| BUG-007 | Citas de clientes no generan notificaciones | APIs de citas NO llaman `notification()` |

### Bugs de Permisos

| ID | Bug | Causa Raíz |
|----|-----|-----------|
| BUG-001 | Comercial puede abrir incidencia | El frontend muestra botones pero API debería bloquear |
| BUG-014 | Estados diferentes entre comercial y proveedor | Diferentes componentes con diferentes mapeos |

### Bugs de Funcionalidad

| ID | Bug | Causa Raíz |
|----|-----|-----------|
| BUG-005 | No reagendar cita mismo día | Validación de fecha compara solo fecha, no hora |
| BUG-009 | Citas finalizadas visibles por defecto | Query no excluye `status = 'finalizado'` |
| BUG-010 | Error comunicación a "Solo empresas" | Filtro de tipo cliente mal implementado |
| BUG-011 | No adjunta archivos en comunicaciones | Falta `enctype="multipart/form-data"` o implementación |
| BUG-012 | Mensajes no aparecen en seguimiento | Query de chat no incluye todos los mensajes |
| BUG-013 | Imágenes no se descargan | `document-fetch.php` solo maneja ciertos tipos |

### Bugs de UI

| ID | Bug | Causa Raíz |
|----|-----|-----------|
| BUG-008 | Falta punto rojo notificaciones en menú | No implementado en sidebar |

---

## 3. ANÁLISIS DE SEGURIDAD {#seguridad}

### 3.1 SQL Injection (7 archivos afectados)

**CRÍTICO**: Variables insertadas directamente en LIMIT/OFFSET sin prepared statements.

| Archivo | Línea | Código Vulnerable |
|---------|-------|-------------------|
| `controller/api-advisory-communications-list.php` | 59 | `LIMIT $limit OFFSET $offset` |
| `controller/api-advisory-communications-list-admin.php` | 72 | `LIMIT $limit OFFSET $offset` |
| `controller/api-advisory-commissions-paginated.php` | 85 | `LIMIT $limit OFFSET $offset` |
| `controller/api-customer-communications-list.php` | 85 | `LIMIT $limit OFFSET $offset` |
| `controller/customer-invoices-list.php` | 94 | `LIMIT $limit OFFSET $offset` |
| `controller/api-customer-appointments-paginated.php` | 58 | `LIMIT $limit OFFSET $offset` |
| `api/customer-communications-list.php` | 72 | `LIMIT $limit OFFSET $offset` |

**Solución**:
```php
// ANTES (vulnerable)
$stmt = $pdo->prepare("SELECT * FROM tabla LIMIT $limit OFFSET $offset");

// DESPUÉS (seguro)
$stmt = $pdo->prepare("SELECT * FROM tabla LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
```

### 3.2 XSS (Cross-Site Scripting)

| Archivo | Línea | Código Vulnerable |
|---------|-------|-------------------|
| `components/proveedor-clientes-datatables-clientes.php` | 86 | `<?php echo $_GET['r'] ?>` |

**Solución**:
```php
<?php echo htmlspecialchars($_GET['r'] ?? '', ENT_QUOTES, 'UTF-8') ?>
```

### 3.3 GROUP BY Issues (Corregidos)

Todos los problemas de `ONLY_FULL_GROUP_BY` han sido corregidos con el patrón de subquery:
```php
// Patrón corregido
(SELECT COUNT(*) FROM notifications n
 WHERE n.request_id = req.id
 AND n.receiver_id = :user_id
 AND n.status = 0) > 0 AS has_notification
```

---

## 4. SISTEMA DE NOTIFICACIONES {#notificaciones}

### 4.1 Problema Principal: Citas NO generan notificaciones

**Causa**: Los APIs de citas solo envían email, NO insertan en tabla `notifications`.

**Archivos afectados**:
- `api/advisory-create-appointment.php` - Solo llama `send_appointment_email()`
- `controller/api-customer-request-appointment.php` - Solo llama `send_appointment_email()`
- `api/update-appointment-status.php` - NO notifica nada

**Solución**: Agregar llamadas a `notification()` después de cada acción de cita.

### 4.2 Problema: "Ver todas" redirige mal en asesoría

**Archivo**: `partials/menus/_notifications-menu.php` línea 82
```php
<?php elseif (asesoria()) : ?>
    <a href="/communications" class="btn-notif">  <!-- INCORRECTO -->
```

**Debería ser**:
```php
    <a href="/notifications" class="btn-notif">  <!-- CORRECTO -->
```

### 4.3 Problema: Asesoría sin "Notificaciones" en sidebar

**Archivo**: `layout/partials/sidebar/_menu_sidebar.php`

El menú de asesoría NO incluye enlace a Notificaciones (sí existe para cliente y comercial).

---

## 5. SEO Y META TAGS {#seo}

### 5.1 Problema CRÍTICO: robots.txt bloquea todo

**Archivo**: `/robots.txt`
```
User-agent: *
Disallow: /
```

**Impacto**: El sitio es COMPLETAMENTE invisible para buscadores.

**Solución**:
```
User-agent: *
Allow: /login
Allow: /sign-up
Allow: /recovery
Disallow: /api/
Disallow: /controller/
Disallow: /bold/
```

### 5.2 Meta Tags (Correctos)

- Title tag: ✅
- Meta description: ✅
- Open Graph: ✅
- Twitter Cards: ✅
- JSON-LD Schema: ✅
- Canonical URL: ✅

### 5.3 Faltantes

- Sitemap.xml: ❌ No existe
- Language inconsistente en plantillas de auth: `lang="en"` debería ser `lang="es"`

---

## 6. ACCESIBILIDAD (WCAG 2.1) {#accesibilidad}

### 6.1 Problemas Críticos (Level A)

| Problema | Ubicación | WCAG |
|----------|-----------|------|
| Sin "Skip to main content" link | Todas las páginas | 2.4.1 |
| Label sin `for` en recovery form | `recovery-content.php:43` | 1.3.1 |
| Modales sin `aria-labelledby` | Múltiples archivos | 1.3.1 |
| Botones de ícono sin `aria-label` | Tablas, acciones | 1.1.1 |
| Jerarquía de headings incorrecta | Múltiples páginas | 1.3.1 |

### 6.2 Problemas Medios (Level AA)

| Problema | Ubicación | WCAG |
|----------|-----------|------|
| Contraste de placeholders insuficiente | `login.css` | 1.4.3 |
| Sin `aria-describedby` en validación | Formularios | 1.3.1 |
| Tablas sin `aria-label` | DataTables | 1.3.1 |

### 6.3 Implementado Correctamente

- Focus states: ✅
- `prefers-reduced-motion`: ✅
- Viewport meta: ✅
- Language attribute: ✅

---

## 7. RENDIMIENTO FRONTEND {#rendimiento}

### 7.1 Tamaño de Assets (CRÍTICO)

| Tipo | Tamaño | Problema |
|------|--------|----------|
| CSS Total | 2.9 MB | Bundle monolítico |
| JS Total | 6.0 MB | plugins.bundle.js = 3.4 MB |
| Plugins | 31 MB | Sin minificar |
| Imágenes | 60 MB | Sin optimizar |
| **TOTAL** | **~100 MB** | **Muy pesado** |

### 7.2 Archivos más pesados

| Archivo | Tamaño |
|---------|--------|
| `style_bundle.min.css` | 1.59 MB |
| `plugins.bundle.js` | 3.4 MB |
| `plugins.bundle.css` | 866 KB |
| `widgets.bundle.js` | 525 KB |

### 7.3 Problemas Detectados

1. **Sin lazy loading**: Imágenes cargan todas inmediatamente
2. **Sin GZIP**: `.htaccess` no configura compresión
3. **Sin Cache-Control**: Headers de caché no configurados
4. **jQuery incluido**: Dependencia obsoleta en bundle
5. **Sin code splitting**: Mismo bundle para todas las páginas

### 7.4 Mejoras Estimadas

| Optimización | Reducción |
|--------------|-----------|
| GZIP compression | -70% transferencia |
| Lazy loading imágenes | -40% initial load |
| WebP para imágenes | -70% tamaño media |
| Code splitting | -50% JS por página |
| **Total potencial** | **-50% tamaño** |

---

## 8. SISTEMA DE AUTENTICACIÓN {#autenticacion}

### 8.1 Implementación Actual

- **Método**: JWT (JSON Web Tokens) stateless
- **Almacenamiento**: Cookie HTTP-only `auth_token`
- **Duración**: 1 hora (renovación automática a 10 min de expirar)
- **Roles**: admin, proveedor, comercial, asesoria, cliente (particular, autonomo, empresa)

### 8.2 Sesiones Concurrentes

**SÍ permite múltiples sesiones simultáneas con el mismo usuario.**

- No hay tabla de sesiones activas
- No hay invalidación de tokens anteriores
- Cada navegador obtiene token independiente

### 8.3 Estructura para Múltiples Usuarios por Asesoría (Futuro)

```sql
-- Nueva tabla propuesta
CREATE TABLE advisory_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    advisory_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    department ENUM('contabilidad', 'administracion', 'fiscal', 'laboral') NOT NULL,
    permissions JSON,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (advisory_id) REFERENCES advisories(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY (advisory_id, user_id)
);
```

---

## 9. BASE DE DATOS {#base-de-datos}

### 9.1 Tablas con Engine MyISAM (Migrar a InnoDB)

| Tabla | Problema |
|-------|----------|
| `advisories` | Sin transacciones, sin FK |
| `advisory_appointments` | Sin transacciones, sin FK |
| `advisory_communications` | Sin transacciones, sin FK |
| `advisory_communication_recipients` | Sin transacciones, sin FK |
| `advisory_invoices` | Sin transacciones, sin FK |

**Script de migración**:
```sql
ALTER TABLE advisories ENGINE=InnoDB;
ALTER TABLE advisory_appointments ENGINE=InnoDB;
ALTER TABLE advisory_communications ENGINE=InnoDB;
ALTER TABLE advisory_communication_recipients ENGINE=InnoDB;
ALTER TABLE advisory_invoices ENGINE=InnoDB;
```

### 9.2 Tipos de ID Inconsistentes

| Tabla | Columna | Tipo Actual | Debería Ser |
|-------|---------|-------------|-------------|
| `advisory_appointments` | `advisory_id` | `int` | `bigint unsigned` |
| `advisory_appointments` | `customer_id` | `int` | `bigint unsigned` |
| `advisory_invoices` | `advisory_id` | `int` | `bigint unsigned` |

### 9.3 Tabla sin Primary Key

```sql
-- Tabla regions NO tiene PK
ALTER TABLE regions MODIFY COLUMN code VARCHAR(2) NOT NULL;
ALTER TABLE regions ADD PRIMARY KEY (code);
```

### 9.4 Foreign Keys Faltantes (13 tablas)

Tablas principales sin integridad referencial:
- `advisories` → `users`
- `advisory_appointments` → `advisories`, `users`
- `advisory_invoices` → `advisories`, `users`
- `customers_advisories` → `advisories`, `users`
- `user_pictures` → `users`
- `request_incidents` → `requests`

---

## 10. PLAN DE ACCIÓN PRIORIZADO {#plan-accion}

### SEMANA 1: Críticos

| Prioridad | Tarea | Esfuerzo |
|-----------|-------|----------|
| 1 | Corregir 7 SQL Injection (LIMIT/OFFSET) | 2h |
| 2 | Corregir XSS en `$_GET['r']` | 30min |
| 3 | Corregir robots.txt | 15min |
| 4 | Agregar `notification()` en APIs de citas | 3h |
| 5 | Corregir BUG-002/015 (incidencias) | 2h |
| 6 | Corregir BUG-016 (revisiones) | 1h |

### SEMANA 2: Importantes

| Prioridad | Tarea | Esfuerzo |
|-----------|-------|----------|
| 7 | Migrar 5 tablas MyISAM a InnoDB | 1h |
| 8 | Corregir tipos de ID en advisory_* | 2h |
| 9 | Agregar Foreign Keys | 3h |
| 10 | Implementar GZIP en .htaccess | 30min |
| 11 | Agregar lazy loading a imágenes | 2h |
| 12 | Corregir bugs de notificaciones restantes | 4h |

### SEMANA 3: Mejoras

| Prioridad | Tarea | Esfuerzo |
|-----------|-------|----------|
| 13 | Agregar skip link para accesibilidad | 1h |
| 14 | Corregir labels sin `for` | 1h |
| 15 | Agregar aria-labels a botones de ícono | 3h |
| 16 | Crear sitemap.xml | 1h |
| 17 | Optimizar imágenes (WebP) | 4h |
| 18 | Implementar Cache-Control headers | 1h |

### SEMANA 4+: Refactoring

| Prioridad | Tarea | Esfuerzo |
|-----------|-------|----------|
| 19 | Crear PaginationHelper class | 4h |
| 20 | Modularizar functions.php | 8h |
| 21 | Unificar componentes duplicados | 8h |
| 22 | Code splitting para bundles JS/CSS | 16h |
| 23 | Implementar sistema de permisos robusto | 8h |

---

## ARCHIVOS DE TESTS CREADOS

| Archivo | Descripción |
|---------|-------------|
| `tests/run_tests.php` | Tests HTTP (ejecutar en navegador) |
| `tests/code_analysis.php` | Análisis estático de código |
| `tests/REPORTE_ISSUES.md` | Reporte de issues detectados |
| `tests/BUGS_REPORTADOS.md` | Bugs reportados por el equipo |

### Ejecutar Tests

**HTTP Tests** (requiere servidor):
```
http://facilitame.test/tests/run_tests.php
```

**Análisis Estático** (desde Laragon terminal):
```cmd
cd C:\Users\acast\Documents\Facilitame
php tests/code_analysis.php
```

---

## PREGUNTAS PARA EL CLIENTE

1. **Incidencias**: ¿Solo clientes deberían poder crear incidencias, o también proveedores/admin?
2. **Revisiones**: ¿Solo clientes pueden solicitar revisión de oferta?
3. **Comercial**: ¿Qué acciones específicas debería poder hacer el comercial en una solicitud?
4. **Citas mismo día**: ¿Se debería permitir reagendar una cita para más tarde el mismo día?
5. **Citas finalizadas**: ¿Cuántos días después de finalizar deberían desaparecer del listado?
6. **Múltiples usuarios asesoría**: ¿Qué departamentos/roles necesitan? ¿Qué permisos diferentes?

---

*Documento generado el 10 de Diciembre 2025*
