# Analisis de Requisitos de Asesorias

**Version:** 1.0
**Fecha:** 12 de Diciembre 2025
**Basado en:** Documento ASESORIA.pdf

---

## 1. RESUMEN DE REQUISITOS DEL DOCUMENTO

El documento de requisitos define las funcionalidades esperadas para el modulo de asesorias en Facilitame.

---

## 2. PLANES Y PRECIOS

### 2.1 Estructura de Planes

| Plan | Precio Anual | Precio Mensual Equivalente |
|------|--------------|---------------------------|
| Gratuito | 0€ | 0€ |
| Basic | 300€ | 25€ |
| Estandar | 650€ | ~54€ |
| Pro | 1,799€ | ~150€ |
| Premium | 2,799€ | ~233€ |
| Enterprise | 5,799€ | ~483€ |

### 2.2 Funcionalidades por Plan

| Funcionalidad | Gratuito | Basic | Estandar | Pro | Premium | Enterprise |
|---------------|----------|-------|----------|-----|---------|------------|
| Registro de clientes | Si | Si | Si | Si | Si | Si |
| Codigo de identificacion | Si | Si | Si | Si | Si | Si |
| Gestion basica | Si | Si | Si | Si | Si | Si |
| Envio de facturas | No | Si | Si | Si | Si | Si |
| Comunicaciones | No | No | Si | Si | Si | Si |
| Citas y agenda | No | No | Si | Si | Si | Si |
| Integracion Inmatic | No | No | No | Si | Si | Si |
| Soporte prioritario | No | No | No | No | Si | Si |
| Personalizacion | No | No | No | No | No | Si |

---

## 3. COMPARATIVA: REQUISITOS vs IMPLEMENTACION

### 3.1 Registro de Asesorias

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Registro con CIF | Implementado | Campo cif en tabla advisories |
| Razon social | Implementado | Campo razon_social |
| Direccion | Implementado | Campo direccion |
| Email empresa | Implementado | Campo email_empresa |
| Telefono | Parcial | Campo en codigo pero falta en schema |
| Codigo identificacion unico | Implementado | Campo codigo_identificacion UNIQUE |

**Issues detectados:**
- Campo `telefono` se usa en APIs pero no existe en schema.sql
- Falta migrar: `migrations/2025-12-12-advisories-updates.sql`

### 3.2 Planes de Pago

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Plan gratuito | Implementado | enum 'gratuito' |
| Plan basic | Implementado | enum 'basic' |
| Plan estandar | Implementado | enum 'estandar' |
| Plan pro | Implementado | enum 'pro' |
| Plan premium | Implementado | enum 'premium' |
| Plan enterprise | Implementado | enum 'enterprise' |
| Validacion por plan | Parcial | Solo en envio de facturas |
| Upgrade/downgrade | No implementado | No hay API para cambiar plan |
| Facturacion automatica | No implementado | No hay integracion de pagos |

**Issues detectados:**
- `advisories-add.php` no permite crear con plan 'enterprise'
- No hay sistema de cobro ni facturacion
- No hay validacion de funcionalidades por plan (excepto facturas)

### 3.3 Gestion de Clientes

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Crear clientes nuevos | Implementado | advisory-create-customer.php |
| Vincular clientes existentes | Implementado | advisory-link-customer.php |
| Tipos de cliente | Implementado | autonomo, empresa, particular, comunidad, asociacion |
| Subtipos | Parcial | Implementado pero con bugs |
| Ver lista de clientes | Implementado | advisory-customers-paginated |
| Editar cliente | No implementado | No hay API |
| Eliminar/desvincular cliente | No implementado | No hay API |

**Issues detectados:**
- Bug: tipo 'particular' no tiene subtipos pero se requiere subtipo
- Bug: comunidad y asociacion usan rol 'particular' (role_id 6)
- Falta API para editar/desvincular clientes

### 3.4 Sistema de Citas

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Solicitar cita (cliente) | Implementado | api-customer-request-appointment |
| Crear cita (asesoria) | Implementado | advisory-create-appointment |
| Tipos de cita | Implementado | llamada, reunion_presencial, reunion_virtual |
| Departamentos | Implementado | contabilidad, fiscalidad, laboral, gestion |
| Estados | Implementado | solicitado, agendado, finalizado, cancelado |
| Sistema de confirmacion | Implementado (v2) | proposed_date, needs_confirmation_from |
| Cancelacion | Implementado | api-customer/advisory-cancel-appointment |
| Notas | Parcial | Solo notes_advisory funciona |
| Chat de cita | Implementado | advisory_messages con appointment_id |
| Integracion Google Calendar | Parcial | Solo enlace para agregar |

**Issues detectados:**
- Campo `direccion` no existe pero se usa en Google Calendar
- Campos `preferred_time` y `specific_time` no usados
- Campo `notes_customer` no implementado

### 3.5 Sistema de Comunicaciones

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Enviar comunicacion masiva | Implementado | advisory-send-communication |
| Segmentar por tipo cliente | Implementado | target_type filtro |
| Niveles de importancia | Implementado | leve, media, importante |
| Archivos adjuntos | Parcial | Codigo existe pero tabla falta |
| Ver comunicaciones enviadas | Implementado | advisory-communications-paginated |
| Tracking lectura | Implementado | advisory_communication_recipients.is_read |
| Recordatorios | Parcial | Campo reminder_sent existe |

**Issues detectados:**
- Tabla `advisory_communication_files` no existe en schema
- Falta migrar: `database/add_communication_files.sql`
- No hay validacion de plan para comunicaciones

### 3.6 Sistema de Facturas

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Cliente sube facturas | Implementado | advisory-upload-invoice |
| Asesoria sube facturas | Implementado | advisory-upload-customer-invoices |
| Clasificacion gasto/ingreso | Implementado | Campo type en advisory_invoices |
| Etiquetas | Implementado | Campo tag |
| Mes/ano/trimestre | Implementado | Campos month, year, quarter |
| Integracion Inmatic | No implementado | Solo documentacion preparada |
| Ver facturas | Implementado | advisory-customer-invoices-paginated |
| Eliminar factura | No implementado | No hay API |

**Issues detectados:**
- Validacion de plan inconsistente (cliente requiere plan, asesoria no)
- Integracion Inmatic pendiente
- Falta API para eliminar facturas

### 3.7 Chat Asesoria-Cliente

| Requisito | Estado | Observaciones |
|-----------|--------|---------------|
| Chat general | Implementado | advisory-chat-send |
| Chat por cita | Implementado | advisory-appointment-chat-send |
| Marcar como leido | Implementado | mark_*_messages_read |
| Historial | Implementado | advisory-chat-messages |
| Notificacion nuevo mensaje | Implementado | notification + email |

**Sin issues detectados**

---

## 4. FUNCIONALIDADES FALTANTES

### 4.1 Criticas (Bloquean uso real)

| Funcionalidad | Prioridad | Esfuerzo |
|---------------|-----------|----------|
| Ejecutar migraciones pendientes | Critica | 30 min |
| Corregir rol de asesoria (5→8) | Critica | 1 hora |
| Validacion de estado 'activo' | Critica | 2 horas |
| Sistema de pagos/suscripciones | Critica | 40+ horas |

### 4.2 Importantes (Limitan funcionalidad)

| Funcionalidad | Prioridad | Esfuerzo |
|---------------|-----------|----------|
| API editar cliente | Alta | 2 horas |
| API desvincular cliente | Alta | 2 horas |
| API eliminar factura | Alta | 1 hora |
| Validacion de funcionalidades por plan | Alta | 4 horas |
| Integracion Inmatic | Alta | 20 horas |

### 4.3 Mejoras (Experiencia de usuario)

| Funcionalidad | Prioridad | Esfuerzo |
|---------------|-----------|----------|
| Dashboard de asesoria | Media | 8 horas |
| Reportes y estadisticas | Media | 12 horas |
| Exportar datos a Excel | Media | 4 horas |
| Recordatorios automaticos | Media | 6 horas |
| Notificaciones por Telegram/WhatsApp | Baja | 8 horas |

---

## 5. MATRIZ DE VALIDACION POR PLAN

Esta tabla define que debe validarse segun el plan de la asesoria:

```php
// Propuesta de implementacion
function advisory_can_use_feature($advisory_id, $feature) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT plan FROM advisories WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$advisory_id]);
    $advisory = $stmt->fetch();

    if (!$advisory) return false;

    $features_by_plan = [
        'gratuito' => ['clientes', 'chat'],
        'basic' => ['clientes', 'chat', 'facturas'],
        'estandar' => ['clientes', 'chat', 'facturas', 'comunicaciones', 'citas'],
        'pro' => ['clientes', 'chat', 'facturas', 'comunicaciones', 'citas', 'inmatic'],
        'premium' => ['clientes', 'chat', 'facturas', 'comunicaciones', 'citas', 'inmatic', 'soporte'],
        'enterprise' => ['clientes', 'chat', 'facturas', 'comunicaciones', 'citas', 'inmatic', 'soporte', 'personalizacion']
    ];

    $plan = $advisory['plan'];
    return in_array($feature, $features_by_plan[$plan] ?? []);
}
```

### Endpoints que requieren validacion:

| Endpoint | Feature |
|----------|---------|
| advisory-upload-invoice | facturas |
| advisory-upload-customer-invoices | facturas |
| advisory-send-communication | comunicaciones |
| advisory-create-appointment | citas |
| advisory-update-appointment | citas |
| advisory-invoice-send-to-inmatic | inmatic |

---

## 6. COMISIONES DE COMERCIALES

### 6.1 Segun documento

- Comerciales pueden captar asesorias
- Reciben comision por suscripciones
- Estructura de comisiones por plan

### 6.2 Estado actual

| Requisito | Estado |
|-----------|--------|
| Vincular asesoria a comercial | Implementado | advisories_sales_codes |
| Calcular comisiones | No implementado | No hay logica |
| Pagar comisiones | No implementado | No hay sistema |
| Ver comisiones | No implementado | No hay interfaz |

---

## 7. ESTADOS DE ASESORIA

### 7.1 Flujo esperado

```
Registro → pendiente → (aprobacion admin) → activo
                                               ↓
                                          (suspension)
                                               ↓
                                          suspendido
                                               ↓
                                        (reactivacion)
                                               ↓
                                             activo
```

### 7.2 APIs necesarias

| API | Estado |
|-----|--------|
| advisory-approve | No existe |
| advisory-suspend | No existe |
| advisory-reactivate | No existe |

### 7.3 Validaciones necesarias

Cuando estado != 'activo', la asesoria NO deberia poder:
- Crear clientes
- Enviar comunicaciones
- Gestionar citas
- Subir facturas
- Usar Inmatic

---

## 8. PLAN DE IMPLEMENTACION SUGERIDO

### Fase 1: Estabilizacion (1-2 dias)

1. Ejecutar migraciones pendientes
2. Corregir bug de rol de asesoria
3. Agregar validacion de estado 'activo'
4. Corregir bug de subtipo para 'particular'
5. Crear tabla advisory_communication_files

### Fase 2: Completar CRUD (3-5 dias)

1. API editar cliente
2. API desvincular cliente
3. API eliminar factura
4. APIs de estados de asesoria (approve, suspend, reactivate)

### Fase 3: Validaciones por Plan (2-3 dias)

1. Crear funcion advisory_can_use_feature()
2. Agregar validaciones a todos los endpoints
3. Mostrar mensajes de upgrade en frontend

### Fase 4: Integracion Inmatic (3-5 dias)

1. Implementar InmaticClient
2. Crear APIs de configuracion y envio
3. Configurar webhooks
4. Crear interfaz en frontend

### Fase 5: Sistema de Pagos (futuro)

1. Integrar pasarela de pagos (Redsys u otra)
2. Sistema de suscripciones
3. Facturacion automatica
4. Gestion de upgrades/downgrades
5. Notificaciones de vencimiento

---

## 9. CONCLUSIONES

El modulo de asesorias tiene una **base solida** pero requiere:

1. **Correccion inmediata** de bugs criticos (migraciones, roles)
2. **Implementacion de validaciones** por plan y estado
3. **Completar funcionalidades** basicas (editar, eliminar)
4. **Desarrollar integracion** con Inmatic
5. **Sistema de pagos** para monetizacion

**Estimacion total para produccion completa:** 4-6 semanas de desarrollo

---

**Fin del documento**
