# BUGS REPORTADOS POR EL EQUIPO - FACILITAME

**Fecha**: 10 de Diciembre 2025
**Reportado por**: Equipo de QA

---

## RESUMEN DE BUGS

| ID | Bug | Severidad | Módulo | Estado |
|----|-----|-----------|--------|--------|
| BUG-001 | Comercial puede abrir incidencia y solicitar revisión (no debería) | Alta | Permisos | Pendiente |
| BUG-002 | Error al iniciar una incidencia | Crítica | Incidencias | Pendiente |
| BUG-003 | Notificaciones de asesoría no acceden a la solicitud | Alta | Notificaciones | Pendiente |
| BUG-004 | Algunas notificaciones no aparecen en el panel izquierdo | Alta | Notificaciones | Pendiente |
| BUG-005 | No permite reagendar cita para el mismo día | Media | Citas | Pendiente |
| BUG-006 | "Ver todas" en campana de asesoría lleva a comunicaciones | Media | Navegación | Pendiente |
| BUG-007 | Citas de clientes no aparecen en notificaciones | Alta | Notificaciones | Pendiente |
| BUG-008 | Falta punto rojo de notificaciones en menú izquierdo | Media | UI | Pendiente |
| BUG-009 | Citas finalizadas se muestran por defecto (deberían ocultarse) | Baja | Filtros | Pendiente |
| BUG-010 | Error al enviar comunicación a "Solo empresas" | Alta | Comunicaciones | Pendiente |
| BUG-011 | No permite adjuntar archivos en comunicaciones | Alta | Comunicaciones | Pendiente |
| BUG-012 | Mensajes de cliente aparecen en notificaciones pero no en seguimiento | Alta | Seguimiento | Pendiente |
| BUG-013 | Imágenes no se descargan correctamente | Media | Archivos | Pendiente |
| BUG-014 | Estados de solicitud se ven diferente entre comercial y proveedor | Media | UI/Consistencia | Pendiente |
| BUG-015 | Error "no puedes comunicar una incidencia" en incidencia 1162 | Crítica | Incidencias | Pendiente |
| BUG-016 | Solicitar revisión no funciona | Crítica | Revisiones | Pendiente |

---

## DETALLE DE BUGS

### BUG-001: Permisos incorrectos para comercial

**Descripción**: El rol comercial puede abrir incidencias y solicitar revisiones cuando no debería tener ese permiso.

**Pasos para reproducir**:
1. Iniciar sesión como comercial
2. Abrir una solicitud
3. Intentar crear incidencia o solicitar revisión
4. El sistema permite la acción (ERROR)

**Comportamiento esperado**: El comercial no debería ver los botones de incidencia/revisión.

**Archivos a revisar**:
- `pages/comercial/request.php`
- `components/request-actions-comercial.php`
- `api/incident-create.php`
- `api/review-request.php`

---

### BUG-002: Error al iniciar incidencia

**Descripción**: Al intentar crear una incidencia, el sistema devuelve error.

**Pasos para reproducir**:
1. Abrir una solicitud
2. Click en "Crear incidencia"
3. Rellenar formulario
4. Enviar → ERROR

**Archivos a revisar**:
- `api/incident-create.php`
- `controller/api-incident-create.php`

---

### BUG-003: Notificaciones de asesoría no acceden a solicitud

**Descripción**: Desde el perfil de asesoría, al hacer click en una notificación, no redirige correctamente a la solicitud.

**Archivos a revisar**:
- `pages/asesoria/notifications.php`
- `components/notifications-list.php`
- `bold/functions.php` → función de notificaciones

---

### BUG-004: Notificaciones faltantes en panel izquierdo

**Descripción**: Algunas notificaciones aparecen en la campana pero no en la sección de notificaciones del sidebar.

**Archivos a revisar**:
- `layout/partials/sidebar/_menu_sidebar.php`
- `bold/functions.php` → `get_notifications()`
- `controller/api-notifications-*.php`

---

### BUG-005: No permite reagendar cita para el mismo día

**Descripción**: Al intentar reagendar una cita para el mismo día, el sistema lo rechaza.

**Archivos a revisar**:
- `api/advisory-update-appointment.php`
- `controller/api-advisory-update-appointment.php`

**Posible causa**: Validación de fecha que compara con fecha actual sin considerar hora.

---

### BUG-006: "Ver todas" redirige incorrectamente

**Descripción**: En asesoría, al dar click en la campana y luego "Ver todas", redirige a comunicaciones en lugar de notificaciones.

**Archivos a revisar**:
- `layout/partials/header/_notifications.php`
- `pages/asesoria/notifications.php`

---

### BUG-007: Citas de clientes no generan notificaciones

**Descripción**: Cuando un cliente agenda una cita, la asesoría no recibe notificación.

**Archivos a revisar**:
- `api/advisory-create-appointment.php`
- `controller/api-customer-request-appointment.php`
- `bold/functions.php` → `notification()`

---

### BUG-008: Falta indicador de notificaciones en menú

**Descripción**: No hay punto rojo con contador de notificaciones pendientes en cada apartado del menú lateral.

**Archivos a revisar**:
- `layout/partials/sidebar/_menu_sidebar.php`
- `assets/css/sidebar.css`

**Solución propuesta**: Añadir badge con contador en cada item del menú.

---

### BUG-009: Citas finalizadas visibles por defecto

**Descripción**: Las citas con estado "finalizado" se muestran por defecto, deberían ocultarse y solo verse al filtrar.

**Archivos a revisar**:
- `controller/api-advisory-appointments-paginated.php`
- `pages/asesoria/appointments.php`

---

### BUG-010: Error al enviar comunicación a empresas

**Descripción**: Al seleccionar "Solo empresas" como destinatario de una comunicación, da error.

**Archivos a revisar**:
- `api/advisory-send-communication.php`
- `controller/api-advisory-send-communication.php`

**Posible causa**: El filtro de tipo de cliente no encuentra usuarios con ese rol.

---

### BUG-011: No adjunta archivos en comunicaciones

**Descripción**: El formulario de comunicaciones no permite adjuntar archivos.

**Archivos a revisar**:
- `pages/asesoria/communications.php`
- `api/advisory-send-communication.php`

**Posible causa**: Falta implementar subida de archivos o el formulario no tiene `enctype="multipart/form-data"`.

---

### BUG-012: Mensajes no aparecen en seguimiento

**Descripción**: Los mensajes de clientes aparecen en notificaciones pero no en la sección de seguimiento de la solicitud.

**Archivos a revisar**:
- `controller/request.php`
- `components/request-chat-*.php`
- `api/app-request-get-chat.php`

---

### BUG-013: Imágenes no se descargan

**Descripción**: Algunas imágenes adjuntas no se pueden descargar.

**Archivos a revisar**:
- `api/download.php`
- `api/document-fetch.php`

**Solución propuesta**: Crear endpoint específico para descarga de archivos binarios.

---

### BUG-014: Estados inconsistentes entre roles

**Descripción**: Los estados de una solicitud se muestran de forma diferente para comercial vs proveedor.

**Archivos a revisar**:
- `bold/functions.php` → `get_badge_html()`
- `components/request-details-comercial.php`
- `components/request-details-proveedor.php`

---

### BUG-015: Error en incidencia 1162

**Descripción**: Al abrir la incidencia 1162, muestra "no puedes comunicar una incidencia".

**Pasos para reproducir**:
1. Ir a incidencia con ID 1162
2. Aparece mensaje de error

**Archivos a revisar**:
- `controller/incident.php`
- Verificar permisos del usuario sobre esa incidencia

---

### BUG-016: Solicitar revisión no funciona

**Descripción**: La funcionalidad de solicitar revisión no está operativa.

**Archivos a revisar**:
- `api/review-request.php`
- `controller/api-review-request.php`

---

## PRIORIDAD DE CORRECCIÓN

### Críticos (arreglar hoy):
1. BUG-002: Error al iniciar incidencia
2. BUG-015: Error en incidencia 1162
3. BUG-016: Solicitar revisión no funciona

### Altos (arreglar esta semana):
4. BUG-001: Permisos de comercial
5. BUG-003: Notificaciones de asesoría
6. BUG-004: Notificaciones faltantes
7. BUG-007: Citas sin notificaciones
8. BUG-010: Error comunicación a empresas
9. BUG-011: Adjuntar archivos
10. BUG-012: Mensajes en seguimiento

### Medios (siguiente semana):
11. BUG-005: Reagendar mismo día
12. BUG-006: Redirección incorrecta
13. BUG-008: Punto rojo en menú
14. BUG-013: Descarga de imágenes
15. BUG-014: Estados inconsistentes

### Bajos:
16. BUG-009: Filtro de finalizadas

---

## ARCHIVOS MÁS AFECTADOS

| Archivo | Bugs relacionados |
|---------|-------------------|
| `bold/functions.php` | BUG-003, BUG-004, BUG-007, BUG-014 |
| `api/incident-create.php` | BUG-002 |
| `api/advisory-send-communication.php` | BUG-010, BUG-011 |
| `controller/incident.php` | BUG-015 |
| `layout/partials/sidebar/_menu_sidebar.php` | BUG-004, BUG-008 |

---

*Reporte generado el 10 de Diciembre 2025*
