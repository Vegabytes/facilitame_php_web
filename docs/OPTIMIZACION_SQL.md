# SQL Optimization Report - Facilitame

**Fecha**: 10 de Diciembre 2025
**Generado por**: Agente de análisis

---

## Resumen de Issues Críticos

| Prioridad | Issue | Cantidad | Archivos |
|----------|-------|----------|----------|
| CRÍTICO | N+1 Query Problem | 4 instancias | functions.php, api/customer-communications-list.php, controller/api-advisory-communications-list.php |
| ALTO | SELECT * | 39+ | Varios archivos en api/, controller/, bold/ |
| ALTO | Subqueries Correlacionadas | 4 instancias | bold/functions.php, controller/api-advisory-communications-list.php |
| MEDIO | LIKE %search% | 2 instancias | api/customer-communications-list.php, controller/api-advisory-communications-list.php |
| MEDIO | WHERE 1 Pattern | 6 instancias | api/incident-report.php, api/offer-activate.php, etc. |
| MEDIO | Funciones de fecha en WHERE | 2 instancias | controller/invoices.php, api/customer-communications-list.php |
| MEDIO | Sin LIMIT en listas grandes | 1 instancia | request_get_all() |

---

## 1. N+1 Query Problem (Queries en Loops)

### Issue 1 - functions.php:1704-1722

```php
function request_get_all()
{
    $requests = $stmt->fetchAll();
    foreach ($requests as &$request) {
        $request["offers"] = request_get_offers($request["id"]);           // QUERY EN LOOP
        $request["commissions_admin"] = get_offers_commissions($request["id"]); // QUERY EN LOOP
        $request["sales_rep"] = request_get_sales_rep($request["id"]);    // QUERY EN LOOP
    }
}
```

**Solución**: Combinar las tres queries en un solo JOIN con GROUP BY, o hacer fetch de toda la data relacionada antes del loop.

---

### Issue 2 - functions.php:340-376 (get_services)

```php
foreach ($categories as $i => $cat) {
    $stmt = $pdo->prepare("SELECT * FROM form_categories WHERE category_id = :category_id..."); // QUERY EN LOOP
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE deleted_at IS NULL AND parent_id = :parent_id..."); // QUERY EN LOOP

    foreach ($subcategories as $j => $sub) {
        $stmt = $pdo->prepare("SELECT * FROM form_categories WHERE category_id = :category_id..."); // NESTED QUERY EN LOOP
    }
}
```

**Solución**: Hacer fetch de todas las form_categories y subcategorías de antemano usando IN clause, luego organizar en PHP.

---

### Issue 3 - api/customer-communications-list.php:84-89

```php
$stmt_files = $pdo->prepare("SELECT id, filename, url, mime_type, filesize FROM advisory_communication_files WHERE communication_id = ?");
foreach ($communications as &$comm) {
    $stmt_files->execute([$comm['id']]);    // QUERY EN LOOP
    $comm['attachments'] = $stmt_files->fetchAll();
}
```

**Solución**: Obtener todos los attachments en una query con `WHERE communication_id IN (ids)`, luego organizar en PHP.

---

### Issue 4 - controller/api-advisory-communications-list.php:86-94

```php
$stmt_files = $pdo->prepare("SELECT id, filename, url, mime_type, filesize FROM advisory_communication_files WHERE communication_id = ?");
foreach ($communications as $comm) {
    $stmt_files->execute([$comm['id']]);    // QUERY EN LOOP
    $attachments = $stmt_files->fetchAll();
}
```

**Solución**: Igual que Issue 3 - fetch todos los archivos con WHERE IN clause.

---

## 2. SELECT * en lugar de columnas específicas (38+ ocurrencias)

### Archivos afectados:

| Archivo | Línea | Query |
|---------|-------|-------|
| `api/advisory-update-appointment.php` | 30 | `SELECT * FROM advisory_appointments WHERE id = ?...` |
| `api/app-notifications-get-pending.php` | 12 | `SELECT * FROM notifications WHERE status = 0...` |
| `bold/functions.php` | 160 | `SELECT * FROM customers WHERE deleted = 0...` |
| `bold/functions.php` | 179 | `SELECT * FROM quotations WHERE customer_id IN...` |
| `bold/functions.php` | 1917 | `SELECT * FROM commissions_admin WHERE request_id...` |

**Solución**: Especificar solo las columnas necesarias para reducir ancho de banda y memoria.

---

## 3. Queries sin LIMIT al obtener listas

### functions.php:1704

```php
function request_get_all()
{
    $stmt = $pdo->prepare("SELECT categories.name AS category_display, requests.*, ...
        FROM requests
        WHERE requests.deleted_at IS NULL
        ORDER BY created_at DESC");  // SIN LIMIT
    $stmt->execute();
    $requests = $stmt->fetchAll();
}
```

**Riesgo**: Obtiene TODAS las solicitudes sin paginación. Potencial consumo masivo de memoria.
**Solución**: Agregar `LIMIT 1000` o implementar paginación.

---

## 4. LIKE con Wildcard Inicial

### controller/api-advisory-communications-list.php:26

```php
$where[] = "(ac.subject LIKE ? OR ac.message LIKE ?)";
$params[] = '%' . $search . '%';
$params[] = '%' . $search . '%';
```

**Problema**: `LIKE %search%` con wildcard inicial no puede usar índices eficientemente.
**Solución**: Considerar full-text search si está disponible.

---

## 5. Patrón WHERE 1 (Anti-pattern)

### Archivos afectados:
- `api/incident-report.php:20`
- `api/offer-activate.php:15, 31, 47`
- `controller/invoices.php:113`

```php
$query = "SELECT * FROM `requests` WHERE 1";
// ... condiciones añadidas después
```

**Problema**: Usar `WHERE 1` es code smell.
**Solución**: Construir queries con condiciones iniciales apropiadas.

---

## 6. Subqueries Correlacionadas (Potencialmente Lentas)

### functions.php:1960-1964

```php
$stmt = $pdo->prepare("SELECT
    (SELECT content FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message_at,
    (SELECT COUNT(*) FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id AND is_read = 0 AND sender_type = 'customer') as unread_count
");
```

**Problema**: Múltiples subqueries correlacionadas consultando la misma tabla varias veces.
**Solución**: Usar window functions (ROW_NUMBER, RANK) o JOIN con tabla derivada.

---

### controller/api-advisory-communications-list.php:54-55

```php
(SELECT COUNT(*) FROM advisory_communication_recipients acr WHERE acr.communication_id = ac.id) as total_recipients,
(SELECT COUNT(*) FROM advisory_communication_recipients acr WHERE acr.communication_id = ac.id AND acr.is_read = 1) as read_count
```

**Problema**: Dos COUNT subqueries separadas sobre la misma tabla.
**Solución**: Usar un solo JOIN con GROUP BY y `SUM(CASE WHEN is_read=1 THEN 1 ELSE 0 END)`.

---

## 7. Funciones de Fecha en WHERE

### controller/invoices.php:113

```php
WHERE ... AND YEAR(invoice_date) = YEAR(CURRENT_DATE) AND MONTH(invoice_date) = MONTH(CURRENT_DATE)
```

**Problema**: Funciones en columnas del WHERE previenen uso de índices.
**Solución**: Usar comparación de rangos:
```sql
WHERE invoice_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
  AND invoice_date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
```

---

## 8. Índices Potencialmente Faltantes

Columnas filtradas frecuentemente que podrían no tener índices:

- `advisory_communications.importance`
- `advisory_communication_recipients.is_read`
- `advisory_communication_recipients.customer_id`
- `notifications.receiver_id`
- `requests.deleted_at`
- `advisory_appointments(advisory_id, id)` - índice compuesto

---

## Plan de Acción Recomendado

### Semana 1 - Críticos
1. [ ] Corregir N+1 en `request_get_all()`
2. [ ] Corregir N+1 en comunicaciones (attachments)
3. [ ] Añadir índices faltantes

### Semana 2 - Altos
4. [ ] Reemplazar SELECT * por columnas específicas (archivos más usados primero)
5. [ ] Optimizar subqueries correlacionadas

### Semana 3 - Medios
6. [ ] Eliminar patrón WHERE 1
7. [ ] Optimizar queries con funciones de fecha
8. [ ] Considerar full-text search para búsquedas

---

*Documento generado el 10 de Diciembre 2025*
