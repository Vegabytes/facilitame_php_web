# ANALISIS DE REQUISITOS - MODULO ASESORIA

**Fecha**: 10 de Diciembre 2025
**Documento de referencia**: ASESORIA[1].pdf

---

## RESUMEN EJECUTIVO

Este documento compara los requisitos del documento de especificaciones con lo implementado actualmente en la plataforma Facilitame.

### Estado General

| Sección | Implementado | Pendiente | Excluido Fase 1 |
|---------|--------------|-----------|-----------------|
| 1. Registro Asesoría | Parcial | Pasarela pago | Inmatic |
| 2. Planes de Pago | Parcial | Pasarela | Inmatic |
| 3. Registro Cliente | Completo | - | - |
| 4. Procedimiento Post-Pago | No | Todo | Inmatic |
| 5. Funcionalidades | Completo | Mejoras menores | - |
| 6. Inicio Perfil | Completo | - | Google Calendar |
| 7. Enviar Facturas | Completo | - | - |
| 8. Solicitar Citas | Completo | - | - |
| 9. Comunicaciones | Completo | Adjuntos, reenvío 24h | - |
| 10. Facturas Inmatic | No | Todo | Excluido Fase 1 |
| 11. Comerciales | Parcial | Comisiones asesoría | - |

---

## DETALLE POR SECCIÓN

### 1. REGISTRO ASESORÍA

#### Requisitos del documento:
- [x] Añadir opción "asesoría" en registro
- [x] Solicitar datos fiscales (CIF, razón social, dirección, teléfono)
- [x] Código de identificación único
- [ ] **Pasarela de pago** para elegir plan (EXCLUIDO FASE 1)
- [ ] Mensaje post-pago: "nuestros expertos se pondrán en contacto"
- [ ] Crear solicitud a "Administración" si no incluye Inmatic (EXCLUIDO FASE 1)
- [ ] Crear solicitud a "Administración + Inmatic" si incluye Inmatic (EXCLUIDO FASE 1)

#### Estado actual:
- Registro de asesoría implementado en `pages/registro-asesoria.php`
- Datos fiscales se guardan en tabla `advisories`
- Código único generado automáticamente
- **Pendiente**: Pasarela de pago (excluida de Fase 1)

---

### 2. PLANES DE PAGO

#### Requisitos del documento:
| Plan | Precio | Características |
|------|--------|-----------------|
| Gratuito | 0€ | App sin envío de facturas |
| Basic | 300€/año | App + envío facturas (sin Inmatic) |
| Estándar | 650€/año | App + Inmatic |
| Pro | 1799€/año | App + Inmatic (mayor volumen) |
| Premium | 2799€/año | App + Inmatic (mayor volumen) |
| Enterprise | 5799€/año | App + Inmatic (mayor volumen) |

#### Estado actual:
- [x] Campo `plan` en tabla `advisories` (valores: gratuito, basic, estandar, pro, premium, enterprise)
- [x] Lógica para ocultar envío de facturas en plan gratuito
- [ ] **Pasarela de pago** (EXCLUIDO FASE 1)
- [ ] Integración con Inmatic (EXCLUIDO FASE 1)

#### Verificación en código:
```php
// api/advisory-upload-invoice.php línea 26-28
if ($advisory['plan'] === 'gratuito') {
    json_response("ko", "Tu asesoría tiene el plan gratuito sin envío de facturas", 4003);
}
```

---

### 3. REGISTRO CLIENTE CON CÓDIGO ASESORÍA

#### Requisitos del documento:
- [x] Campo "código de asesoría" en registro
- [x] Tipos de cliente: Autónomo, Empresa, C.B., Asociación
- [x] Subtipos por número de empleados
- [x] Notificación a asesoría cuando cliente se registra
- [x] Opción en perfil para añadir código de asesoría (clientes existentes)

#### Estado actual:
- Implementado en `pages/sign-up.php` y `api/sign-up.php`
- Campo `client_type` en usuarios
- Tabla `customers_advisories` para vincular cliente-asesoría
- Notificaciones funcionando

---

### 4. PROCEDIMIENTO POST-PAGO

#### Requisitos del documento:
- [ ] Crear solicitud a Inmatic para instalación (EXCLUIDO FASE 1)
- [ ] Seguimiento del estado de instalación
- [ ] Envío automático de factura a asesoría
- [ ] Cambio de estado a "activada" cuando finalice instalación

#### Estado actual:
- **No implementado** (depende de pasarela de pago e Inmatic)
- Excluido de Fase 1

---

### 5. FUNCIONALIDADES ASESORÍA

#### 5.1 Enviar Factura (Cliente → Asesoría)
- [x] Subir 1 o varias fotos/PDFs
- [x] Etiquetas (Restaurante, gasolina, proveedores...)
- [x] Almacenamiento de facturas enviadas
- [x] Filtrar por mes/trimestre
- [x] Ocultar opción si plan gratuito
- [x] Tipo: Gasto/Ingreso

**Archivos**:
- `pages/cliente/advisory-invoices.php`
- `api/advisory-upload-invoice.php`
- `controller/customer-invoices-list.php`

#### 5.2 Solicitar Reunión/Llamada (Cliente → Asesoría)
- [x] Tipos: Llamada, Reunión presencial, Reunión virtual
- [x] Estados: Solicitado, Agendado, Finalizado (+ Cancelado)
- [x] Departamentos: Contabilidad, Fiscalidad, Laboral, Gestión
- [x] Horario preferible
- [x] Motivo de solicitud (texto libre)
- [x] Chat en citas

**Archivos**:
- `pages/cliente/appointments.php`
- `pages/asesoria/appointments.php`
- `controller/api-customer-request-appointment.php`
- `controller/api-advisory-update-appointment.php`

#### 5.3 Comunicaciones (Asesoría → Clientes)
- [x] Crear mensajes con asunto y contenido
- [x] Envío de email + notificación app
- [x] Filtrar destinatarios (todos, autónomos, empresas, etc.)
- [x] Niveles de importancia (Leve, Media, Importante)
- [ ] **Adjuntar archivos** (PENDIENTE - BUG-011)
- [ ] Reenvío automático si no abierta en 24h (notificación importante)

**Archivos**:
- `pages/asesoria/communications.php`
- `pages/cliente/communications.php`
- `api/advisory-send-communication.php`

#### 5.4 Almacenaje de Facturas
- [x] Facturas almacenadas en `advisory_invoices`
- [x] Visualización por asesoría y cliente
- [ ] Política de retención 4 años (no implementada explícitamente)

#### 5.5 Chat Asesoría-Cliente
- [x] Chat implementado
- [x] Asesoría puede iniciar chat
- [x] Cliente puede iniciar chat
- [x] Historial de mensajes

**Archivos**:
- `pages/asesoria/customer.php` (sección chat)
- `api/advisory-chat-*.php`

---

### 6. INICIO PERFIL ASESORÍA

#### Requisitos del documento:
- [x] Acceso rápido a funciones principales
- [x] Botón "Enviar comunicaciones"
- [x] Panel de solicitudes de clientes (citas)
- [x] Vista de clientes asociados
- [x] Recibidor de chat (últimos mensajes)
- [ ] **Calendario** (para agendar reuniones) - EXCLUIDO FASE 1
- [ ] Integración Google Calendar - EXCLUIDO FASE 1

#### Estado actual:
- Dashboard implementado en `pages/asesoria/home.php`
- Widgets de resumen funcionando
- Calendario NO implementado (excluido de Fase 1)

---

### 7. ENVIAR FACTURAS (Usuario/Cliente)

#### Requisitos:
- [x] Botón prominente en pantalla inicial
- [x] Subir 1 o varias fotos
- [x] Subir PDFs, JPG
- [x] Etiquetas personalizables
- [x] Almacenamiento para revisión posterior
- [x] Filtro por mes y trimestre
- [x] Ocultar si plan gratuito

#### Estado: **COMPLETO**

---

### 8. SOLICITAR LLAMADAS/REUNIÓN

#### Requisitos:
- [x] Tipo: Llamada, Reunión (presencial/virtual)
- [x] Horario preferible (mañana, tarde, específico)
- [x] Motivo de solicitud (texto libre)
- [x] Departamento destino

#### Estado: **COMPLETO**

---

### 9. COMUNICACIONES (Asesoría)

#### Requisitos:
- [x] Crear mensajes con contenido
- [x] Adjuntar documentación/PDF/enlaces
- [x] Filtrar por tipo de cliente
- [x] Niveles de importancia (Leve, Media, Importante)
- [ ] **Reenvío automático 24h** para importantes (PENDIENTE)

#### Estado: **PARCIAL**

**Pendiente**:
1. ~~Adjuntar archivos~~ - Reportado como BUG-011, necesita verificación
2. Reenvío automático si notificación importante no abierta en 24h

---

### 10. FACTURAS INMATIC

#### Requisitos:
- [ ] Envío automático a programa contable
- [ ] Integración API Inmatic

#### Estado: **EXCLUIDO FASE 1**

---

### 11. COMERCIALES

#### Requisitos:
- [x] Asesoría vinculada a comercial
- [x] Clientes de asesoría vinculados al mismo comercial
- [ ] **Comisiones específicas de asesoría**:
  - Gratuito: 0€ + 5€/cliente nuevo
  - Basic: 100€ + 5€/cliente nuevo
  - Otros planes: 100€ + 5€/cliente nuevo

#### Estado: **PARCIAL**

**Pendiente**:
- Sistema de comisiones específico para asesorías
- Filtro de comisiones por asesoría en panel admin

---

## FUNCIONALIDADES EXCLUIDAS DE FASE 1

Según indicación del usuario, las siguientes funcionalidades están **excluidas de la Fase 1**:

1. **Inmatic** - Integración completa con sistema de contabilidad
2. **Pasarela de pago** - Cobro automático de planes
3. **Google Calendar** - Sincronización de citas

---

## BUGS RELACIONADOS (del documento BUGS_REPORTADOS.md)

| Bug ID | Descripción | Módulo Asesoría |
|--------|-------------|-----------------|
| BUG-003 | Notificaciones no acceden a solicitud | ✅ CORREGIDO |
| BUG-005 | No permite reagendar mismo día | ✅ CORREGIDO |
| BUG-006 | "Ver todas" lleva a comunicaciones | Verificar |
| BUG-007 | Citas no generan notificaciones | ✅ CORREGIDO |
| BUG-009 | Finalizadas visibles por defecto | ✅ CORREGIDO |
| BUG-010 | Error comunicación "Solo empresas" | PENDIENTE |
| BUG-011 | No adjunta archivos en comunicaciones | PENDIENTE |
| BUG-013 | Imágenes no se descargan | ✅ CORREGIDO (file-download API) |

---

## RESUMEN DE PENDIENTES FASE 1

### Alta Prioridad
1. [ ] BUG-010: Error al enviar comunicación a "Solo empresas"
2. [ ] BUG-011: Adjuntar archivos en comunicaciones
3. [ ] Notificación a admin cuando asesoría elimina cliente

### Media Prioridad
4. [ ] Reenvío automático 24h para comunicaciones importantes
5. [ ] Sistema de comisiones específico para asesorías
6. [ ] Filtro de asesorías en panel de comisiones

### Baja Prioridad
7. [ ] Política de retención de facturas (4 años)

---

## CONCLUSIÓN

El módulo de Asesoría tiene implementadas las **funcionalidades core** requeridas:
- ✅ Registro de asesorías con código único
- ✅ Vinculación cliente-asesoría
- ✅ Envío de facturas (con restricción por plan)
- ✅ Sistema de citas (solicitar, agendar, finalizar)
- ✅ Comunicaciones (con filtros por tipo de cliente)
- ✅ Chat asesoría-cliente
- ✅ Notificaciones

**Pendiente para completar Fase 1**:
- Corregir bugs BUG-010 y BUG-011
- Implementar reenvío automático 24h
- Sistema de comisiones de asesoría

**Excluido de Fase 1** (según indicación del usuario):
- Pasarela de pago
- Integración Inmatic
- Google Calendar

---

*Documento generado el 10 de Diciembre 2025*
