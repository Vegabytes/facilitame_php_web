# REPORTE DE ISSUES DETECTADOS - FACILITAME

**Fecha de análisis**: 10 de Diciembre 2025
**Analizado por**: Claude Code Assistant

---

## RESUMEN

| Severidad | Cantidad | Estado |
|-----------|----------|--------|
| **CRÍTICO** | 8 | Pendiente |
| **WARNING** | 12 | Pendiente |
| **INFO** | 15+ | Pendiente |

---

## ISSUES CRÍTICOS (Requieren acción inmediata)

### 1. SQL Injection en LIMIT/OFFSET

**Severidad**: CRÍTICA
**Tipo**: Seguridad

Variables insertadas directamente en queries SQL sin usar prepared statements para LIMIT y OFFSET.

| Archivo | Línea | Código problemático |
|---------|-------|---------------------|
| `controller/api-advisory-communications-list.php` | 59 | `LIMIT $limit OFFSET $offset` |
| `controller/api-advisory-communications-list-admin.php` | 72 | `LIMIT $limit OFFSET $offset` |
| `controller/api-advisory-commissions-paginated.php` | 85 | `LIMIT $limit OFFSET $offset` |
| `controller/api-customer-communications-list.php` | 85 | `LIMIT $limit OFFSET $offset` |
| `controller/customer-invoices-list.php` | 94 | `LIMIT $limit OFFSET $offset` |
| `controller/api-customer-appointments-paginated.php` | 58 | `LIMIT $limit OFFSET $offset` |
| `api/customer-communications-list.php` | 72 | `LIMIT $limit OFFSET $offset` |

**Solución**:
```php
// ANTES (vulnerable):
$stmt = $pdo->prepare("SELECT * FROM tabla LIMIT $limit OFFSET $offset");

// DESPUÉS (seguro):
$stmt = $pdo->prepare("SELECT * FROM tabla LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
```

---

### 2. XSS en parámetro $_GET['r']

**Severidad**: CRÍTICA
**Tipo**: Seguridad

Parámetro GET reflejado en HTML sin escapar.

| Archivo | Línea | Código problemático |
|---------|-------|---------------------|
| `components/proveedor-clientes-datatables-clientes.php` | 86 | `<?php echo $_GET['r'] ?>` |

**Solución**:
```php
// ANTES (vulnerable):
<?php echo $_GET['r'] ?>

// DESPUÉS (seguro):
<?php echo htmlspecialchars($_GET['r'] ?? '', ENT_QUOTES, 'UTF-8') ?>
```

---

### 3. Tablas con Engine MyISAM

**Severidad**: CRÍTICA
**Tipo**: Base de datos

Tablas sin soporte para transacciones ni integridad referencial.

| Tabla | Engine actual | Problema |
|-------|--------------|----------|
| `advisories` | MyISAM | Sin FK, sin transacciones |
| `advisory_appointments` | MyISAM | Sin FK, sin transacciones |
| `advisory_communication_recipients` | MyISAM | Sin FK, sin transacciones |
| `advisory_communications` | MyISAM | Sin FK, sin transacciones |
| `advisory_invoices` | MyISAM | Sin FK, sin transacciones |

**Solución**:
```sql
ALTER TABLE advisories ENGINE=InnoDB;
ALTER TABLE advisory_appointments ENGINE=InnoDB;
ALTER TABLE advisory_communication_recipients ENGINE=InnoDB;
ALTER TABLE advisory_communications ENGINE=InnoDB;
ALTER TABLE advisory_invoices ENGINE=InnoDB;
```

---

### 4. Tipos de ID inconsistentes

**Severidad**: CRÍTICA
**Tipo**: Base de datos

Los tipos de columnas de FK no coinciden con las PK referenciadas.

| Tabla | Columna | Tipo actual | Debería ser |
|-------|---------|-------------|-------------|
| `advisory_appointments` | `advisory_id` | `int` | `bigint unsigned` |
| `advisory_appointments` | `customer_id` | `int` | `bigint unsigned` |
| `advisory_invoices` | `advisory_id` | `int` | `bigint unsigned` |
| `advisory_invoices` | `customer_id` | `int` | `bigint unsigned` |

---

## ISSUES DE WARNING (Corregir pronto)

### 5. Foreign Keys faltantes

**Severidad**: WARNING
**Tipo**: Base de datos

Relaciones sin integridad referencial.

| Tabla | Columna | Debería referenciar |
|-------|---------|---------------------|
| `advisories` | `user_id` | `users(id)` |
| `advisory_appointments` | `advisory_id` | `advisories(id)` |
| `advisory_appointments` | `customer_id` | `users(id)` |
| `advisory_invoices` | `advisory_id` | `advisories(id)` |
| `advisory_invoices` | `customer_id` | `users(id)` |
| `advisory_communications` | `advisory_id` | `advisories(id)` |
| `customers_advisories` | `advisory_id` | `advisories(id)` |
| `customers_advisories` | `customer_id` | `users(id)` |
| `user_pictures` | `user_id` | `users(id)` |
| `request_incidents` | `request_id` | `requests(id)` |

---

### 6. Tabla `regions` sin PRIMARY KEY

**Severidad**: WARNING
**Tipo**: Base de datos

```sql
CREATE TABLE `regions` (
  `code` varchar(2) DEFAULT NULL,  -- Sin PK!
  `name` varchar(255) DEFAULT NULL
);
```

**Solución**:
```sql
ALTER TABLE regions MODIFY COLUMN code VARCHAR(2) NOT NULL;
ALTER TABLE regions ADD PRIMARY KEY (code);
```

---

### 7. Código de paginación duplicado

**Severidad**: WARNING
**Tipo**: Mantenibilidad

El mismo patrón de paginación se repite en **30+ archivos**:

```php
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;
```

**Archivos afectados** (parcial):
- `controller/api-requests-paginated-admin.php`
- `controller/api-requests-paginated-provider.php`
- `controller/api-requests-paginated-sales.php`
- `controller/api-incidents-paginated-admin.php`
- `controller/api-customers-paginated-admin.php`
- Y 25+ más...

---

### 8. functions.php demasiado grande

**Severidad**: WARNING
**Tipo**: Mantenibilidad

| Métrica | Valor |
|---------|-------|
| Líneas totales | 2,278 |
| Funciones | 130+ |

**Recomendación**: Separar en módulos por dominio.

---

## ISSUES INFORMATIVOS

### 9. Swal.fire() duplicado

**Severidad**: INFO
**Tipo**: Código duplicado

`Swal.fire()` usado **115 veces** en 17 archivos JS diferentes con el mismo patrón.

**Recomendación**: Crear helper `FH.showError()`, `FH.showSuccess()`.

---

### 10. Componentes duplicados por rol

**Severidad**: INFO
**Tipo**: Código duplicado

Componentes con 3-4 variantes por rol:

| Componente base | Variantes |
|-----------------|-----------|
| `request-details` | admin, cliente, comercial, proveedor |
| `request-chat` | admin, cliente, comercial, proveedor |
| `request-documents` | admin, cliente, proveedor |
| `request-offers` | admin, cliente, proveedor |
| `home-datatable-solicitudes` | admin, comercial, proveedor |

**Recomendación**: Unificar en un solo componente con parámetro de rol.

---

### 11. Funciones duplicadas

**Severidad**: INFO
**Tipo**: Código duplicado

| Funciones | Propósito | Acción |
|-----------|-----------|--------|
| `customer_get_sales_rep()`, `customer_get_sales_rep_name()`, `get_sales_rep_by_customer()` | Obtener comercial de cliente | Unificar |
| `get_requestor()`, `get_request_user()` | Obtener usuario de solicitud | Unificar |
| `get_file_types()`, `get_file_types_kp()` | Obtener tipos de archivo | Unificar |
| `get_statuses()`, `get_statuses_names()` | Obtener estados | Unificar |

---

### 12. Tablas de backup en producción

**Severidad**: INFO
**Tipo**: Limpieza

```
2025-01-05--23-34-36--provider_comments
2025-06-05--13-59-47--users
```

**Recomendación**: Eliminar o mover a otro schema.

---

### 13. Debug statements en código

**Severidad**: INFO
**Tipo**: Limpieza

Múltiples `error_log()` en archivos de producción.

---

## TESTS DISPONIBLES

Se han creado los siguientes archivos de tests:

| Archivo | Descripción | Cómo ejecutar |
|---------|-------------|---------------|
| `tests/run_tests.php` | Tests de endpoints HTTP | `http://facilitame.test/tests/run_tests.php` |
| `tests/code_analysis.php` | Análisis estático de código | `php tests/code_analysis.php` |

---

## PRIORIDAD DE CORRECCIÓN

### Semana 1 - Críticos
1. ✅ Corregir GROUP BY (ya hecho)
2. ⬜ Corregir SQL Injection en LIMIT/OFFSET
3. ⬜ Corregir XSS en $_GET['r']
4. ⬜ Migrar tablas MyISAM a InnoDB

### Semana 2 - Warnings
5. ⬜ Corregir tipos de ID en BD
6. ⬜ Añadir Foreign Keys
7. ⬜ Crear PaginationHelper

### Semana 3+ - Mejoras
8. ⬜ Modularizar functions.php
9. ⬜ Unificar componentes
10. ⬜ Crear helpers JS

---

*Reporte generado automáticamente el 10 de Diciembre 2025*
