# PLAN DE REFACTORIZACIÓN - FACILITAME

**Fecha**: 10 de Diciembre 2025
**Versión**: 1.0
**Autor**: Claude Code Assistant

---

## RESUMEN EJECUTIVO

Este documento describe el plan completo de refactorización del proyecto Facilitame, organizado en 4 fases principales con un enfoque incremental que minimiza el riesgo de romper funcionalidades existentes.

### Métricas Actuales del Proyecto

| Métrica | Valor Actual |
|---------|--------------|
| Archivos PHP en `/controller/` | 110 |
| Archivos PHP en `/api/` | 122 |
| Líneas en `functions.php` | 2,278 |
| Componentes duplicados | 47 |
| Archivos JS personalizados | 31 |
| Tablas en BD | 37 |
| Tablas con MyISAM (problema) | 5 |

### Objetivos de la Refactorización

1. **Seguridad**: Eliminar vulnerabilidades de SQL injection y XSS
2. **Mantenibilidad**: Reducir código duplicado en un 40-60%
3. **Rendimiento**: Optimizar queries y estructura de BD
4. **Escalabilidad**: Crear arquitectura modular para futuras mejoras

---

## FASE 1: CORRECCIONES CRÍTICAS DE SEGURIDAD
**Duración estimada**: 1-2 días
**Riesgo**: Bajo
**Prioridad**: URGENTE

### 1.1 SQL Injection en LIMIT/OFFSET

**Problema**: Variables insertadas directamente en queries SQL.

**Archivos afectados**:
- `controller/api-advisory-communications-list.php` (línea 59)
- `controller/api-advisory-communications-list-admin.php` (línea 72)
- `controller/api-users-paginated.php` (línea 67)
- `controller/api-advisory-commissions-paginated.php` (línea 85)
- `controller/customer-invoices-list.php` (línea 94)
- `bold/functions.php` (líneas 775, 813)

**Código actual (vulnerable)**:
```php
LIMIT $limit OFFSET $offset
```

**Código corregido**:
```php
LIMIT :limit OFFSET :offset
// Con bindValue:
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
```

### 1.2 XSS en parámetros GET

**Problema**: Parámetros GET sin escapar en HTML.

**Archivo afectado**:
- `components/proveedor-clientes-datatables-clientes.php` (línea 86)

**Código actual (vulnerable)**:
```php
href="/customer?id=<?php echo $customer["id"] ?>&r=<?php echo $_GET['r'] ?>"
```

**Código corregido**:
```php
href="/customer?id=<?php echo (int)$customer["id"] ?>&r=<?php echo htmlspecialchars($_GET['r'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
```

### 1.3 Validación de USER["categories"]

**Problema**: `USER["categories"]` se inserta directamente en queries.

**Archivos afectados**:
- `bold/functions.php` (línea 775)
- `api/requests-paginated.php` (línea 62)
- Múltiples controladores

**Solución**: Validar que sea una lista de enteros antes de usar.

---

## FASE 2: REDUCCIÓN DE CÓDIGO DUPLICADO
**Duración estimada**: 3-5 días
**Riesgo**: Medio
**Prioridad**: Alta

### 2.1 Crear clase PaginationHelper

**Objetivo**: Centralizar lógica de paginación usada en 30+ archivos.

**Ubicación**: `bold/classes/PaginationHelper.php`

**Código propuesto**:
```php
<?php
class PaginationHelper {
    private int $page;
    private int $limit;
    private int $offset;
    private string $search;

    public function __construct(
        int $defaultLimit = 10,
        int $maxLimit = 100
    ) {
        $this->page = max(1, intval($_GET['page'] ?? 1));
        $this->limit = min($maxLimit, max(1, intval($_GET['limit'] ?? $defaultLimit)));
        $this->offset = ($this->page - 1) * $this->limit;
        $this->search = trim($_GET['search'] ?? '');
    }

    public function getPage(): int { return $this->page; }
    public function getLimit(): int { return $this->limit; }
    public function getOffset(): int { return $this->offset; }
    public function getSearch(): string { return $this->search; }

    public function bindToStatement(PDOStatement $stmt): void {
        $stmt->bindValue(':limit', $this->limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $this->offset, PDO::PARAM_INT);
    }

    public function formatResponse(array $data, int $totalRecords): array {
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $this->page,
                'per_page' => $this->limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalRecords > 0 ? ceil($totalRecords / $this->limit) : 1,
                'from' => $totalRecords > 0 ? $this->offset + 1 : 0,
                'to' => min($this->offset + $this->limit, $totalRecords)
            ]
        ];
    }
}
```

**Archivos a refactorizar** (usar PaginationHelper):
- `controller/api-requests-paginated-admin.php`
- `controller/api-requests-paginated-provider.php`
- `controller/api-requests-paginated-sales.php`
- `controller/api-incidents-paginated-admin.php`
- `controller/api-reviews-paginated-admin.php`
- `controller/api-postponed-paginated-admin.php`
- `controller/api-postponed-paginated-sales.php`
- `controller/api-postponed-paginated-provider.php`
- `controller/api-users-paginated.php`
- `controller/api-advisory-communications-list.php`
- `controller/api-advisory-communications-list-admin.php`
- `api/advisory-clients-paginated.php`
- Y 18+ archivos más...

### 2.2 Crear clase RoleFilter

**Objetivo**: Centralizar lógica de filtrado por rol.

**Ubicación**: `bold/classes/RoleFilter.php`

**Código propuesto**:
```php
<?php
class RoleFilter {
    public static function getRequestsWhereClause(string $tableAlias = 'req'): string {
        if (admin()) {
            return "1=1";
        }

        if (proveedor()) {
            $categories = self::getProviderCategories();
            if (empty($categories)) return "1=0";
            return "$tableAlias.category_id IN ($categories)";
        }

        if (comercial()) {
            return self::getComercialFilter($tableAlias);
        }

        if (cliente()) {
            return "$tableAlias.user_id = " . (int)USER['id'];
        }

        return "1=0"; // Default: sin acceso
    }

    private static function getProviderCategories(): string {
        return USER['categories'] ?? '';
    }

    private static function getComercialFilter(string $alias): string {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT GROUP_CONCAT(csc.customer_id)
            FROM customers_sales_codes csc
            INNER JOIN sales_codes sc ON sc.id = csc.sales_code_id
            WHERE sc.user_id = ? AND sc.deleted_at IS NULL
        ");
        $stmt->execute([USER['id']]);
        $customerIds = $stmt->fetchColumn();

        if (empty($customerIds)) return "1=0";
        return "$alias.user_id IN ($customerIds)";
    }
}
```

### 2.3 Consolidar funciones duplicadas en functions.php

**Funciones a unificar**:

| Funciones actuales | Nueva función |
|-------------------|---------------|
| `customer_get_sales_rep()`, `customer_get_sales_rep_name()`, `get_sales_rep_by_customer()` | `get_customer_sales_rep($id, $format = 'full')` |
| `get_requestor()`, `get_request_user()` | `get_request_user($id, $fields = '*')` |
| `get_file_types()`, `get_file_types_kp()` | `get_file_types($format = 'full')` |
| `get_statuses()`, `get_statuses_names()` | `get_statuses($format = 'full')` |
| `get_commissions()`, `get_commissions_kp()` | `get_commissions($format = 'full')` |

**Ejemplo de consolidación**:
```php
// ANTES: 3 funciones separadas
function customer_get_sales_rep($customer_id) { ... }
function customer_get_sales_rep_name($customer_id) { ... }
function get_sales_rep_by_customer($customer_id) { ... }

// DESPUÉS: 1 función con parámetro
function get_customer_sales_rep(int $customerId, string $format = 'full'): mixed {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.lastname, u.email, u.phone
        FROM users u
        INNER JOIN sales_codes sc ON sc.user_id = u.id
        INNER JOIN customers_sales_codes csc ON csc.sales_code_id = sc.id
        WHERE csc.customer_id = :customer_id
        AND sc.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) return null;

    return match($format) {
        'id' => (int)$result['id'],
        'name' => trim($result['name'] . ' ' . $result['lastname']),
        'full' => $result
    };
}
```

### 2.4 Crear archivo de constantes

**Ubicación**: `bold/constants.php`

**Contenido**:
```php
<?php
// Etiquetas de importancia (usadas en comunicaciones)
const IMPORTANCE_LABELS = [
    'leve' => ['label' => 'Informativo', 'class' => 'info', 'icon' => 'information'],
    'media' => ['label' => 'Normal', 'class' => 'primary', 'icon' => 'notification'],
    'importante' => ['label' => 'Importante', 'class' => 'danger', 'icon' => 'notification-bing']
];

// Estados de solicitud con estilos
const REQUEST_STATUS_STYLES = [
    1 => ['name' => 'Pendiente', 'class' => 'warning', 'icon' => 'time'],
    2 => ['name' => 'En proceso', 'class' => 'info', 'icon' => 'loading'],
    3 => ['name' => 'Completado', 'class' => 'success', 'icon' => 'check'],
    // ... etc
];

// Tipos de cita
const APPOINTMENT_TYPES = [
    'llamada' => 'Llamada telefónica',
    'reunion_presencial' => 'Reunión presencial',
    'reunion_virtual' => 'Reunión virtual'
];

// Departamentos de asesoría
const ADVISORY_DEPARTMENTS = [
    'contabilidad' => 'Contabilidad',
    'fiscalidad' => 'Fiscalidad',
    'laboral' => 'Laboral',
    'gestion' => 'Gestión'
];

// Estados de cita
const APPOINTMENT_STATUSES = [
    'solicitado' => ['label' => 'Solicitado', 'class' => 'warning'],
    'agendado' => ['label' => 'Agendado', 'class' => 'info'],
    'finalizado' => ['label' => 'Finalizado', 'class' => 'success'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'danger']
];
```

---

## FASE 3: REFACTORIZACIÓN DE COMPONENTES Y FRONTEND
**Duración estimada**: 3-4 días
**Riesgo**: Medio
**Prioridad**: Media

### 3.1 Unificar componentes por rol

**Problema**: 4 versiones de cada componente (admin, cliente, comercial, proveedor).

**Solución**: Un componente con parámetros de rol.

**Ejemplo - request-details.php**:

**ANTES** (4 archivos):
```
components/request-details-admin.php
components/request-details-cliente.php
components/request-details-comercial.php
components/request-details-proveedor.php
```

**DESPUÉS** (1 archivo):
```php
<?php
// components/request-details.php
$role = USER['role'];
$canEdit = in_array($role, ['administrador', 'proveedor']);
$canDelete = $role === 'administrador';
$canSeeOffers = in_array($role, ['administrador', 'proveedor', 'comercial']);
$canSeeCustomerData = $role !== 'proveedor' || has_permission('view_customer_data');
?>

<div class="request-details">
    <!-- Contenido común -->

    <?php if ($canEdit): ?>
        <!-- Botones de edición -->
    <?php endif; ?>

    <?php if ($canSeeOffers): ?>
        <!-- Sección de ofertas -->
    <?php endif; ?>
</div>
```

**Componentes a unificar**:
- `request-details-*.php` → `request-details.php`
- `request-chat-*.php` → `request-chat.php`
- `request-documents-*.php` → `request-documents.php`
- `request-offers-*.php` → `request-offers.php`
- `home-datatable-solicitudes-*.php` → `home-datatable-solicitudes.php`
- `home-datatable-aplazados-*.php` → `home-datatable-aplazados.php`

### 3.2 Crear helpers JavaScript compartidos

**Ubicación**: `assets/js/bold/_helpers.js`

**Contenido**:
```javascript
/**
 * Helpers compartidos para Facilitame
 */
const FacilitameHelpers = {
    /**
     * Mostrar mensaje de error con SweetAlert
     */
    showError: function(message, title = 'Error') {
        return Swal.fire({
            icon: 'error',
            title: title,
            html: message,
            buttonsStyling: false,
            confirmButtonText: 'Cerrar',
            customClass: { confirmButton: 'btn btn-primary' }
        });
    },

    /**
     * Mostrar mensaje de éxito con SweetAlert
     */
    showSuccess: function(message, title = 'Éxito') {
        return Swal.fire({
            icon: 'success',
            title: title,
            html: message,
            timer: 2000,
            showConfirmButton: false
        });
    },

    /**
     * Mostrar confirmación
     */
    confirm: function(message, title = '¿Estás seguro?') {
        return Swal.fire({
            icon: 'question',
            title: title,
            html: message,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary'
            }
        });
    },

    /**
     * Hacer petición POST con FormData
     */
    post: async function(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Error en petición:', error);
            throw error;
        }
    },

    /**
     * Hacer petición GET
     */
    get: async function(url, params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const fullUrl = queryString ? `${url}?${queryString}` : url;
            const response = await fetch(fullUrl);
            return await response.json();
        } catch (error) {
            console.error('Error en petición:', error);
            throw error;
        }
    },

    /**
     * Formatear fecha
     */
    formatDate: function(dateString, format = 'short') {
        if (!dateString) return '-';
        const date = new Date(dateString);

        if (format === 'short') {
            return date.toLocaleDateString('es-ES');
        }
        if (format === 'long') {
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        if (format === 'datetime') {
            return date.toLocaleString('es-ES');
        }
        return date.toLocaleDateString('es-ES');
    },

    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Mostrar loading en botón
     */
    setButtonLoading: function(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Enviar';
        }
    }
};

// Alias cortos
const FH = FacilitameHelpers;
```

**Archivos a refactorizar** (usar helpers):
- `assets/js/bold/login.js`
- `assets/js/bold/request.js`
- `assets/js/bold/chat.js`
- `assets/js/bold/profile.js`
- `assets/js/bold/sign-up.js`
- Y 12+ archivos más...

### 3.3 Separar functions.php en módulos

**Estructura propuesta**:
```
bold/
├── functions.php          (solo require de los módulos)
├── functions/
│   ├── auth.php          (proveedor(), admin(), cliente(), etc.)
│   ├── requests.php      (get_request(), get_requests(), etc.)
│   ├── users.php         (get_user(), get_customers(), etc.)
│   ├── notifications.php (notification(), send_notification(), etc.)
│   ├── utils.php         (fdate(), json_response(), validate_nif_cif_nie(), etc.)
│   ├── mail.php          (send_mail(), etc.)
│   └── pdf.php           (make_pdf(), etc.)
```

**Nuevo functions.php**:
```php
<?php
// bold/functions.php - Autocarga de módulos

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/utils.php';
require_once __DIR__ . '/functions/users.php';
require_once __DIR__ . '/functions/requests.php';
require_once __DIR__ . '/functions/notifications.php';
require_once __DIR__ . '/functions/mail.php';
require_once __DIR__ . '/functions/pdf.php';
```

---

## FASE 4: OPTIMIZACIÓN DE BASE DE DATOS
**Duración estimada**: 1-2 días
**Riesgo**: Alto (hacer backup primero)
**Prioridad**: Alta

### 4.1 Migrar tablas MyISAM a InnoDB

**Script SQL**:
```sql
-- IMPORTANTE: Hacer backup antes de ejecutar
-- mysqldump -u root facilitame > backup_antes_migracion.sql

-- Migrar tablas a InnoDB
ALTER TABLE advisories ENGINE=InnoDB;
ALTER TABLE advisory_appointments ENGINE=InnoDB;
ALTER TABLE advisory_communication_recipients ENGINE=InnoDB;
ALTER TABLE advisory_communications ENGINE=InnoDB;
ALTER TABLE advisory_invoices ENGINE=InnoDB;

-- Verificar migración
SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'facilitame'
AND ENGINE = 'MyISAM';
```

### 4.2 Corregir tipos de ID inconsistentes

**Script SQL**:
```sql
-- Corregir tipos en advisory_appointments
ALTER TABLE advisory_appointments
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

-- Corregir tipos en advisory_communication_recipients
ALTER TABLE advisory_communication_recipients
    MODIFY COLUMN communication_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

-- Corregir tipos en advisory_communications
ALTER TABLE advisory_communications
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL;

-- Corregir tipos en advisory_invoices
ALTER TABLE advisory_invoices
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

-- Corregir tipos en customers_advisories
ALTER TABLE customers_advisories
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL;
```

### 4.3 Añadir Foreign Keys faltantes

**Script SQL**:
```sql
-- FK para advisories
ALTER TABLE advisories
    ADD CONSTRAINT fk_advisories_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- FK para advisory_appointments
ALTER TABLE advisory_appointments
    ADD CONSTRAINT fk_apt_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_apt_customer
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE;

-- FK para advisory_communications
ALTER TABLE advisory_communications
    ADD CONSTRAINT fk_comm_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE;

-- FK para advisory_communication_recipients
ALTER TABLE advisory_communication_recipients
    ADD CONSTRAINT fk_commrec_comm
    FOREIGN KEY (communication_id) REFERENCES advisory_communications(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_commrec_customer
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE;

-- FK para advisory_invoices
ALTER TABLE advisory_invoices
    ADD CONSTRAINT fk_inv_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_inv_customer
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE;

-- FK para customers_advisories
ALTER TABLE customers_advisories
    ADD CONSTRAINT fk_ca_customer
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ca_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id) ON DELETE CASCADE;

-- FK para user_pictures
ALTER TABLE user_pictures
    ADD CONSTRAINT fk_up_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- FK para request_incidents
ALTER TABLE request_incidents
    ADD CONSTRAINT fk_ri_request
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE;
```

### 4.4 Corregir tabla regions

**Script SQL**:
```sql
-- Añadir PK y charset correcto
ALTER TABLE regions
    MODIFY COLUMN code VARCHAR(2) NOT NULL,
    ADD PRIMARY KEY (code),
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4.5 Eliminar tablas de backup

**Script SQL**:
```sql
-- Solo después de verificar que no se usan
DROP TABLE IF EXISTS `2025-01-05--23-34-36--provider_comments`;
DROP TABLE IF EXISTS `2025-06-05--13-59-47--users`;
```

### 4.6 Añadir índices recomendados

**Script SQL**:
```sql
-- Índice para búsquedas de advisory_messages
CREATE INDEX idx_advisory_msg_conv_time
ON advisory_messages(advisory_id, customer_id, created_at);

-- Índice para búsquedas de advisory_appointments por fecha
CREATE INDEX idx_apt_scheduled
ON advisory_appointments(scheduled_date, status);

-- Índice compuesto para offers
CREATE INDEX idx_offers_status_provider
ON offers(status_id, provider_id);
```

---

## CRONOGRAMA PROPUESTO

```
Semana 1:
├── Día 1-2: FASE 1 - Correcciones de seguridad
│   ├── SQL Injection fixes
│   ├── XSS fixes
│   └── Testing
│
├── Día 3-5: FASE 2 - Parte 1
│   ├── Crear PaginationHelper
│   ├── Crear RoleFilter
│   └── Refactorizar 10 archivos paginados

Semana 2:
├── Día 1-2: FASE 2 - Parte 2
│   ├── Consolidar funciones duplicadas
│   ├── Crear constants.php
│   └── Separar functions.php en módulos
│
├── Día 3-4: FASE 3 - Frontend
│   ├── Crear _helpers.js
│   ├── Unificar componentes principales
│   └── Refactorizar JS files
│
├── Día 5: FASE 4 - Base de datos
│   ├── Backup completo
│   ├── Migrar MyISAM a InnoDB
│   ├── Corregir tipos de ID
│   ├── Añadir Foreign Keys
│   └── Testing completo
```

---

## MÉTRICAS DE ÉXITO

### Antes vs Después

| Métrica | Antes | Después (esperado) |
|---------|-------|-------------------|
| Líneas en `functions.php` | 2,278 | ~500 (modularizado) |
| Archivos de paginación duplicados | 30+ | 30+ (usando helper) |
| Líneas de código duplicado | ~3,000 | ~800 |
| Componentes duplicados por rol | 20+ | 5-8 |
| Usos de Swal.fire() duplicados | 115 | 115 (usando helper) |
| Tablas sin FK | 13 | 2-3 |
| Tablas MyISAM | 5 | 0 |
| Vulnerabilidades SQL injection | 15+ | 0 |
| Vulnerabilidades XSS | 1+ | 0 |

---

## RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Romper funcionalidad existente | Media | Alto | Testing exhaustivo, cambios incrementales |
| Pérdida de datos en BD | Baja | Crítico | Backup antes de cada migración |
| Regresiones en frontend | Media | Medio | Testing manual de cada flujo |
| Tiempo mayor al estimado | Alta | Medio | Priorizar por impacto, dejar mejoras menores para después |

---

## CHECKLIST DE IMPLEMENTACIÓN

### Fase 1 - Seguridad
- [ ] Corregir SQL injection en LIMIT/OFFSET (todos los archivos)
- [ ] Corregir XSS en $_GET['r']
- [ ] Validar USER["categories"] antes de usar
- [ ] Testing de seguridad

### Fase 2 - Código duplicado
- [ ] Crear `bold/classes/PaginationHelper.php`
- [ ] Crear `bold/classes/RoleFilter.php`
- [ ] Crear `bold/constants.php`
- [ ] Consolidar funciones de sales_rep
- [ ] Consolidar funciones de file_types
- [ ] Consolidar funciones de statuses
- [ ] Separar functions.php en módulos
- [ ] Testing de funcionalidad

### Fase 3 - Frontend
- [ ] Crear `assets/js/bold/_helpers.js`
- [ ] Incluir _helpers.js en layout
- [ ] Refactorizar login.js
- [ ] Refactorizar request.js
- [ ] Unificar request-details-*.php
- [ ] Unificar request-chat-*.php
- [ ] Testing de UI

### Fase 4 - Base de datos
- [ ] Backup completo de BD
- [ ] Migrar tablas MyISAM a InnoDB
- [ ] Corregir tipos de ID
- [ ] Añadir Foreign Keys
- [ ] Añadir PK a regions
- [ ] Eliminar tablas backup
- [ ] Añadir índices recomendados
- [ ] Testing de integridad

---

## NOTAS FINALES

Este plan está diseñado para ser **incremental y reversible**. Cada fase puede implementarse de forma independiente, y los cambios pueden revertirse fácilmente si se detectan problemas.

Se recomienda:
1. **Hacer commit después de cada tarea completada**
2. **Probar en local antes de subir a producción**
3. **Mantener backup actualizado de la base de datos**
4. **Documentar cualquier cambio no previsto**

---

---

## ANEXO: SUITE DE TESTS

Se han creado los siguientes archivos de tests automatizados:

### Tests HTTP (run_tests.php)

**Ubicación**: `tests/run_tests.php`

**Ejecutar desde navegador**:
```
http://facilitame.test/tests/run_tests.php
```

**Ejecutar desde terminal** (Laragon):
```cmd
cd C:\Users\acast\Documents\Facilitame
php tests/run_tests.php
```

**Tests incluidos**:
- Conectividad básica (páginas públicas)
- APIs públicas (login, sign-up, recovery)
- APIs protegidas (verificar autenticación)
- Endpoints paginados (estructura de respuesta)
- SQL Injection (payloads maliciosos)
- XSS (payloads de script)
- Estructura de archivos
- Conexión a base de datos
- Rendimiento (tiempos de respuesta)
- Manejo de errores

### Análisis Estático (code_analysis.php)

**Ubicación**: `tests/code_analysis.php`

**Ejecutar**:
```cmd
php tests/code_analysis.php
```

**Análisis incluidos**:
- Vulnerabilidades de seguridad (SQL injection, XSS)
- Problemas de SQL (GROUP BY, queries sin preparar)
- Código duplicado (paginación, Swal.fire, componentes)
- Malas prácticas (funciones largas, debug statements)

### Reporte de Issues

**Ubicación**: `tests/REPORTE_ISSUES.md`

Documento con todos los issues detectados, priorizados por severidad.

---

*Documento generado el 10 de Diciembre 2025*
