# Manual de Usuario - Asesorías

## Facilitame - Guía Completa para Asesorías

---

## 1. Introducción

### 1.1 ¿Qué es Facilitame?

Facilitame es una plataforma integral para asesorías que permite:

- Gestionar la relación con tus clientes de forma digital
- Recibir facturas y documentos automáticamente
- Gestionar vencimientos de tus clientes
- Agendar y gestionar citas
- Comunicarte con tus clientes vía chat
- Publicar ofertas y promociones
- Integrar con software contable (Inmatic)
- Sincronizar citas con Google Calendar

### 1.2 Planes disponibles

| Plan | Clientes | Precio/año | Características |
|------|----------|------------|-----------------|
| **Gratuito** | 10 | 0€ | Gestión básica |
| **Basic** | 50 | 300€ | + Recepción facturas, Chat |
| **Estándar** | 150 | 650€ | + Google Calendar, Exportación |
| **Pro** | 500 | 1.799€ | + Inmatic, API |
| **Premium** | 1.500 | 2.799€ | + Multi-usuario, Personalización |
| **Enterprise** | Ilimitado | 5.799€ | + SLA, Desarrollo a medida |

---

## 2. Registro y Configuración Inicial

### 2.1 Crear cuenta de asesoría

1. Accede a **[URL]/register**
2. Selecciona **"Soy Asesoría"**
3. Completa los datos:
   - Razón social
   - CIF
   - Email de empresa
   - Teléfono
   - Dirección
   - Contraseña
4. Acepta términos y condiciones
5. Verifica tu email

### 2.2 Tu código de asesoría

Al registrarte, se genera automáticamente un **código único** (ej: `ABC123`).

Este código es esencial:
- Los clientes lo necesitan para vincularse a tu asesoría
- Puedes encontrarlo en **Configuración > Mi Asesoría**
- Compártelo con tus clientes para que se registren

### 2.3 Configuración inicial recomendada

1. **Completar perfil:** Logo, descripción, horarios
2. **Configurar notificaciones:** Email, push
3. **Elegir plan:** Selecciona el plan adecuado a tu volumen
4. **Integrar Google Calendar:** Para sincronizar citas

---

## 3. Panel Principal (Dashboard)

### 3.1 Métricas principales

Al entrar verás:

- **Clientes totales:** Número de clientes vinculados
- **Facturas este mes:** Documentos recibidos
- **Citas pendientes:** Solicitudes sin confirmar
- **Vencimientos próximos:** Fechas importantes cercanas

### 3.2 Gráficos de actividad

- **Tendencia mensual:** Evolución de facturas recibidas
- **Distribución por tipo:** Gastos vs Ingresos
- **Actividad por cliente:** Clientes más activos

### 3.3 Alertas y notificaciones

El dashboard muestra alertas de:
- Nuevas facturas recibidas
- Solicitudes de cita pendientes
- Vencimientos próximos a caducar
- Mensajes de chat sin leer

---

## 4. Gestión de Clientes

### 4.1 Ver lista de clientes

1. Accede a **"Clientes"** en el menú lateral
2. Verás la lista con:
   - Nombre y apellidos
   - NIF/CIF
   - Email y teléfono
   - Facturas enviadas
   - Última actividad

### 4.2 Filtrar y buscar

- **Búsqueda:** Por nombre, email o NIF
- **Filtros:** Por estado, actividad reciente

### 4.3 Ver ficha de cliente

Haz clic en un cliente para ver:

- **Datos personales:** Información de contacto
- **Facturas:** Todas las facturas enviadas
- **Vencimientos:** Fechas importantes del cliente
- **Citas:** Historial de citas
- **Chat:** Conversación con el cliente

### 4.4 Añadir cliente manualmente

Si necesitas añadir un cliente que aún no se ha registrado:

1. Ve a **"Clientes"**
2. Haz clic en **"Añadir cliente"**
3. Completa los datos:
   - Nombre, apellidos
   - Email
   - Teléfono
   - NIF/CIF
4. Se enviará invitación por email

### 4.5 Invitar clientes

Para que un cliente se vincule:

1. Comparte tu **código de asesoría**
2. El cliente se registra con ese código
3. Automáticamente queda vinculado

---

## 5. Facturas

### 5.1 Recepción de facturas

Cuando un cliente sube una factura:

1. Recibes notificación (email/push)
2. Aparece en la lista de **"Facturas"**
3. Estado inicial: **Pendiente**

### 5.2 Ver facturas

1. Accede a **"Facturas"** en el menú
2. Usa los filtros:
   - **Tipo:** Gasto/Ingreso
   - **Etiqueta:** Restaurante, Gasolina, etc.
   - **Estado:** Pendiente/Procesada
3. Haz clic en una factura para verla

### 5.3 Procesar facturas

Al revisar una factura:

1. **Ver documento:** Descarga o visualiza el archivo
2. **Marcar como procesada:** Indica que ya está contabilizada
3. **Ver datos OCR:** Si usas Inmatic, verás los datos extraídos

### 5.4 Subir facturas en nombre de cliente

Puedes subir facturas por tus clientes:

1. Ve a **"Facturas"**
2. Haz clic en **"Subir Facturas"**
3. Selecciona el cliente
4. Sube los archivos
5. Clasifica (tipo y etiqueta)

### 5.5 Estados de facturas

| Estado | Descripción |
|--------|-------------|
| **Pendiente** | Recibida, sin procesar |
| **Procesada** | Ya contabilizada |

---

## 6. Vencimientos

### 6.1 ¿Qué son los vencimientos?

Sistema para gestionar fechas importantes de tus clientes:

- Declaraciones de impuestos
- Renovación de seguros
- Caducidad de documentos
- Pagos periódicos
- Licencias y permisos

### 6.2 Crear vencimiento

1. Ve a **"Vencimientos"**
2. Haz clic en **"Nuevo vencimiento"**
3. Completa:
   - **Título:** Descripción breve
   - **Cliente:** A quién afecta
   - **Fecha:** Cuándo vence
   - **Tipo:** Fiscal, Seguro, Documento, etc.
   - **Recurrencia:** Único, Mensual, Trimestral, Anual
   - **Recordatorio:** Cuántos días antes avisar
4. Guarda el vencimiento

### 6.3 Vencimientos recurrentes

Para vencimientos que se repiten:

1. Selecciona la recurrencia adecuada
2. El sistema creará automáticamente los siguientes

### 6.4 Notificaciones automáticas

El sistema envía avisos:

- **30 días antes:** Recordatorio temprano
- **15 días antes:** Segundo aviso
- **7 días antes:** Aviso urgente
- **Día del vencimiento:** Último aviso

### 6.5 Vista de calendario

Accede a la vista calendario para:

- Ver todos los vencimientos del mes
- Identificar fechas con múltiples vencimientos
- Planificar tu trabajo

---

## 7. Citas

### 7.1 Tipos de citas

- **Presencial:** En tu oficina
- **Videollamada:** Reunión online
- **Telefónica:** Llamada de teléfono

### 7.2 Solicitudes de cita

Cuando un cliente solicita cita:

1. Recibes notificación
2. Aparece en **"Citas"** como **Solicitado**
3. Revisa el motivo y disponibilidad

### 7.3 Confirmar/Agendar cita

1. Abre la solicitud de cita
2. Haz clic en **"Agendar"**
3. Selecciona fecha y hora
4. El cliente recibe confirmación

### 7.4 Crear cita directamente

Para crear una cita sin solicitud:

1. Ve a **"Citas"**
2. Haz clic en **"Nueva cita"**
3. Selecciona el cliente
4. Indica fecha, hora y tipo
5. El cliente recibe la invitación

### 7.5 Estados de citas

| Estado | Descripción |
|--------|-------------|
| **Solicitado** | Pendiente de confirmar |
| **Agendado** | Confirmada con fecha/hora |
| **Completado** | Ya realizada |
| **Cancelado** | Anulada |

### 7.6 Google Calendar

Si tienes el plan Estándar o superior:

1. Ve a **"Citas"**
2. Haz clic en **"Conectar Google Calendar"**
3. Autoriza la conexión
4. Las citas se sincronizan automáticamente

---

## 8. Chat

### 8.1 Comunicación con clientes

El chat permite:

- Mensajes instantáneos
- Envío de archivos
- Historial de conversaciones

### 8.2 Ver conversaciones

1. Accede a **"Chat"**
2. Verás la lista de conversaciones
3. Los mensajes sin leer aparecen destacados

### 8.3 Responder mensajes

1. Selecciona la conversación
2. Escribe tu respuesta
3. Adjunta archivos si es necesario
4. Envía el mensaje

### 8.4 Notificaciones

Recibirás notificación cuando:
- Un cliente te escriba
- Haya mensajes sin leer

---

## 9. Ofertas

### 9.1 Publicar ofertas

Puedes crear ofertas para tus clientes:

1. Ve a **"Ofertas"** (si está disponible en tu plan)
2. Haz clic en **"Nueva oferta"**
3. Completa:
   - Título
   - Descripción
   - Precio/descuento
   - Fecha de validez
   - Imagen (opcional)
4. Publica la oferta

### 9.2 Gestionar solicitudes

Cuando un cliente solicita una oferta:

1. Recibes notificación
2. Ves la solicitud en la lista
3. Contactas al cliente para dar seguimiento

---

## 10. Integración Inmatic

### 10.1 ¿Qué es Inmatic?

Inmatic es un software de OCR (reconocimiento óptico de caracteres) que:

- Lee automáticamente facturas
- Extrae datos (fecha, importe, proveedor, etc.)
- Prepara la información para contabilizar

### 10.2 Requisitos

- Plan **Pro** o superior (o modo prueba activado)
- Cuenta en Inmatic con API habilitada
- Token de API de Inmatic
- ID de empresa en Inmatic

### 10.3 Configurar Inmatic

1. Ve a **"Inmatic"** en el menú
2. Haz clic en **"Configurar"**
3. Introduce:
   - **Token de API:** Lo obtienes en tu panel de Inmatic
   - **ID de empresa:** Tu identificador en Inmatic
4. Haz clic en **"Probar conexión"**
5. Si es exitoso, guarda la configuración

### 10.4 Obtener credenciales de Inmatic

En tu panel de Inmatic:

1. Accede a **Configuración > API**
2. Genera un nuevo token
3. Copia el token (solo se muestra una vez)
4. Anota tu ID de empresa

### 10.5 Enviar facturas a Inmatic

Una vez configurado:

1. Ve a una factura pendiente
2. Haz clic en **"Enviar a Inmatic"**
3. La factura se procesa automáticamente
4. Los datos extraídos aparecen en la factura

### 10.6 Estados en Inmatic

| Estado | Significado |
|--------|-------------|
| **No enviado** | Factura local, no en Inmatic |
| **Pendiente** | Enviada, procesando OCR |
| **Procesado** | OCR completado, datos disponibles |
| **Aprobado** | Verificado y aprobado |
| **Error** | Problema al procesar |

### 10.7 Modo prueba

Para probar Inmatic sin plan Pro:

1. Ve a **"Inmatic"**
2. Activa **"Modo prueba"**
3. Podrás probar la funcionalidad con limitaciones

---

## 12. Configuración

### 12.1 Datos de la asesoría

En **Configuración > Mi Asesoría**:

- Razón social
- CIF
- Dirección
- Teléfono
- Email
- Logo
- Descripción
- Horario de atención

### 12.2 Código de asesoría

Tu código único para que los clientes se vinculen. No puede cambiarse.

### 12.3 Notificaciones

Configura cómo quieres recibir alertas:

- **Email:** Para cada evento o resumen diario
- **Push:** Notificaciones en tiempo real

### 12.4 Equipo (planes Premium+)

Si tienes plan Premium o superior:

1. Ve a **"Equipo"**
2. Añade miembros con sus emails
3. Asigna roles y permisos
4. Gestiona el acceso del equipo

---

## 13. Comerciales (opcional)

### 13.1 ¿Qué son los comerciales?

Personas que captan clientes para tu asesoría a cambio de comisiones.

### 13.2 Crear comercial

1. Ve a **"Comerciales"**
2. Haz clic en **"Añadir comercial"**
3. Indica:
   - Nombre y datos de contacto
   - Porcentaje de comisión
4. Se genera un código único de comercial

### 13.3 Sistema de comisiones

- Los clientes indicn el código del comercial al registrarse
- El comercial recibe comisión por cada cliente captado
- Las comisiones se calculan automáticamente

---

## 14. API de Integración (Plan Pro+)

### 14.1 Acceso a la API

Si tienes plan Pro o superior:

1. Ve a **"Configuración > API"**
2. Genera un token de API
3. Usa el token para autenticarte

### 14.2 Documentación API

La API permite:

- Listar clientes
- Obtener facturas
- Crear vencimientos
- Gestionar citas

Documentación disponible en: `[URL]/api/docs`

---

## 15. Buenas Prácticas

### 15.1 Para captar clientes

1. Comparte tu código en tus comunicaciones
2. Incluye enlace de registro en tu firma de email
3. Crea material promocional con el código
4. Ofrece descuento por registro digital

### 15.2 Para gestionar facturas

1. Procesa las facturas semanalmente
2. Usa Inmatic para automatizar
3. Clasifica correctamente por etiquetas
4. Comunica a los clientes las facturas procesadas

### 15.3 Para vencimientos

1. Crea vencimientos recurrentes para impuestos habituales
2. Anticípate a las fechas importantes
3. Usa los recordatorios automáticos
4. Revisa el calendario mensualmente

### 15.4 Para citas

1. Responde las solicitudes en 24-48 horas
2. Sincroniza con Google Calendar
3. Confirma las citas por adelantado
4. Registra las citas completadas

---

## 16. Preguntas Frecuentes

### ¿Cuántos clientes puedo tener?

Depende de tu plan:
- Gratuito: 10 clientes
- Basic: 50 clientes
- Estándar: 150 clientes
- Pro: 500 clientes
- Premium: 1.500 clientes
- Enterprise: Ilimitado

### ¿Qué pasa si supero el límite de clientes?

No podrás añadir más clientes hasta que:
- Actualices tu plan
- Des de baja algún cliente existente

### ¿Puedo cambiar de plan en cualquier momento?

Sí. Los cambios son inmediatos:
- Al subir de plan: se prorratean los pagos
- Al bajar de plan: se aplica al final del periodo

### ¿Los datos están seguros?

Sí. Facilitame usa:
- Cifrado SSL en todas las comunicaciones
- Almacenamiento seguro en Europa
- Copias de seguridad diarias
- Cumplimiento GDPR/LOPD

### ¿Puedo exportar mis datos?

Sí. En Configuración puedes exportar:
- Lista de clientes
- Facturas
- Vencimientos

### ¿Cómo contacto con soporte?

- Email: soporte@facilitame.es
- Teléfono: [Número]
- Chat en la plataforma

---

## 17. Glosario

| Término | Definición |
|---------|------------|
| **Código de asesoría** | Identificador único para vincular clientes |
| **OCR** | Reconocimiento óptico de caracteres |
| **Inmatic** | Software de procesamiento de facturas |
| **API** | Interfaz de programación |
| **Webhook** | Notificación automática entre sistemas |
| **Token** | Clave de acceso a servicios |

---

*Última actualización: Diciembre 2025*
*Versión del manual: 1.0*
