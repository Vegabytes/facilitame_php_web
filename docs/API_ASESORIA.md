# API de Asesorías - Documentación

## Endpoints de Administración

### Crear Asesoría
```
POST /api/advisories-add
Auth: Admin
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| razon_social | string | Sí | Nombre de la asesoría |
| cif | string | Sí | CIF de la empresa (formato: A1234567B) |
| email_empresa | string | Sí | Email de contacto |
| direccion | string | No | Dirección física |
| telefono | string | No | Teléfono |
| plan | string | No | gratuito/basic/estandar/pro/premium (default: gratuito) |
| estado | string | No | pendiente/activo/suspendido (default: pendiente) |
| user_name | string | No | Nombre del usuario responsable |
| user_email | string | No | Email del usuario (se crea si no existe) |
| user_phone | string | No | Teléfono del usuario |

**Respuesta exitosa:**
```json
{
  "status": "ok",
  "message_html": "Asesoría creada correctamente",
  "code": 2001359100,
  "data": {
    "advisory_id": 15,
    "codigo_identificacion": "ASE-A12345678",
    "user_id": 450
  }
}
```

---

### Actualizar Asesoría
```
POST /api/advisories-update
Auth: Admin
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | int | Sí | ID de la asesoría |
| razon_social | string | No | Nuevo nombre |
| cif | string | No | Nuevo CIF |
| email_empresa | string | No | Nuevo email |
| direccion | string | No | Nueva dirección |
| telefono | string | No | Nuevo teléfono |
| plan | string | No | Nuevo plan |
| estado | string | No | Nuevo estado |
| user_id | int | No | ID del usuario responsable |

---

### Eliminar Asesoría
```
POST /api/advisories-delete
Auth: Admin
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| id | int | Sí | ID de la asesoría |

**Nota:** Se usa soft delete. Los clientes son desvinculados pero mantienen acceso a la app de servicios.

---

## Endpoints de Asesor

### Crear Cliente
```
POST /api/advisory-create-customer
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| name | string | Sí | Nombre |
| lastname | string | No | Apellidos |
| email | string | Sí | Email (único) |
| phone | string | No | Teléfono |
| nif_cif | string | No | NIF/CIF del cliente |
| client_type | string | No | autonomo/empresa/comunidad/asociacion |
| client_subtype | string | No | Subtipo específico |

---

### Vincular Cliente Existente
```
POST /api/advisory-link-customer
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| email | string | Sí | Email del cliente existente |
| client_type | string | No | Tipo de cliente |

---

### Crear Cita
```
POST /api/advisory-create-appointment
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| customer_id | int | Sí | ID del cliente |
| type | string | Sí | llamada/reunion_presencial/reunion_virtual |
| department | string | Sí | contabilidad/fiscalidad/laboral/gestion |
| preferred_time | string | Sí | manana/tarde/especifico |
| specific_time | string | No | Hora específica si preferred_time=especifico |
| reason | string | Sí | Motivo de la cita |
| scheduled_date | datetime | No | Fecha programada |

---

### Actualizar Cita
```
POST /api/advisory-update-appointment
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| appointment_id | int | Sí | ID de la cita |
| status | string | No | solicitado/agendado/finalizado/cancelado |
| scheduled_date | datetime | No | Nueva fecha |
| notes_advisory | string | No | Notas de la asesoría |
| cancellation_reason | string | No | Motivo de cancelación (si status=cancelado) |

---

### Enviar Comunicación
```
POST /api/advisory-send-communication
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| subject | string | Sí | Asunto |
| message | string | Sí | Contenido del mensaje |
| importance | string | No | leve/media/importante (default: media) |
| target_type | string | Sí | todos/autonomo/empresa/comunidad/asociacion |
| target_subtype | string | No | Filtro adicional |
| customer_ids | array | No | IDs específicos de clientes |

---

### Subir Factura
```
POST /api/advisory-upload-invoice
Auth: Asesoría
Content-Type: multipart/form-data
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| customer_id | int | Sí | ID del cliente |
| file | file | Sí | Archivo (PDF, imagen) |
| type | string | No | gasto/ingreso (default: gasto) |
| tag | string | No | Etiqueta |
| notes | string | No | Notas |
| month | int | No | Mes |
| year | int | No | Año |

**Restricción:** Plan gratuito no puede enviar facturas.

---

### Chat - Enviar Mensaje
```
POST /api/advisory-chat-send
Auth: Asesoría
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| appointment_id | int | Sí | ID de la cita |
| message | string | Sí | Contenido (max 5000 caracteres) |

---

## Endpoints de Cliente

### Vincularse a Asesoría
```
POST /api/customer-link-advisory
Auth: Cliente
```

**Parámetros:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| advisory_code | string | Sí | Código de identificación (ej: ASE-A12345678) |
| client_type | string | No | autonomo/empresa/comunidad/asociacion |
| client_subtype | string | No | Subtipo |

**Respuesta exitosa:**
```json
{
  "status": "ok",
  "message_html": "Te has vinculado correctamente a Asesoría XYZ",
  "code": 2001360100,
  "data": {
    "advisory_id": 15,
    "advisory_name": "Asesoría XYZ"
  }
}
```

---

## Estados de Citas

| Estado | Descripción |
|--------|-------------|
| solicitado | Cita solicitada, pendiente de agendar |
| agendado | Cita con fecha/hora confirmada |
| finalizado | Cita completada |
| cancelado | Cita cancelada (requiere motivo) |

## Niveles de Importancia (Comunicaciones)

| Nivel | Descripción | Recordatorio |
|-------|-------------|--------------|
| leve | Información general | No |
| media | Información relevante | No |
| importante | Acción requerida | Sí (24h si no leída) |

## Planes de Asesoría

| Plan | Envío Facturas | Características |
|------|----------------|-----------------|
| gratuito | No | Funcionalidad básica |
| basic | Sí | + Envío de facturas |
| estandar | Sí | + Más clientes |
| pro | Sí | + Comunicaciones ilimitadas |
| premium | Sí | + Soporte prioritario |
| enterprise | Sí | Personalizado |

---

*Documentación API Asesorías - Facilitame*
