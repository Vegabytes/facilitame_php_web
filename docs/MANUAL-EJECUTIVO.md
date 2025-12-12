# Manual Ejecutivo - Facilitame

## Guía para la Dirección y Propietarios

---

## 1. Resumen Ejecutivo

### 1.1 ¿Qué es Facilitame?

Facilitame es una plataforma SaaS (Software as a Service) B2B2C que conecta asesorías con sus clientes, digitalizando y automatizando la gestión documental, comunicaciones y servicios.

### 1.2 Propuesta de valor

**Para las asesorías:**
- Reducción del tiempo administrativo en un 60-70%
- Automatización de recepción y clasificación de facturas
- Gestión centralizada de clientes y documentos
- Integración con software contable (Inmatic)
- Canal de comunicación directo con clientes

**Para los clientes finales:**
- Envío de facturas desde cualquier dispositivo
- Recordatorios automáticos de vencimientos
- Comunicación directa con su asesoría
- Acceso 24/7 a su información

### 1.3 Modelo de negocio

- **Ingresos recurrentes (MRR/ARR):** Suscripciones anuales de asesorías
- **Modelo freemium:** Plan gratuito limitado + planes de pago
- **Escalabilidad:** Costes fijos bajos, margen creciente con volumen

---

## 2. Estructura de Planes y Precios

### 2.1 Tabla de planes

| Plan | Clientes | Precio/año | Margen est. |
|------|----------|------------|-------------|
| **Gratuito** | 10 | 0€ | Adquisición |
| **Basic** | 50 | 300€ | ~85% |
| **Estándar** | 150 | 650€ | ~87% |
| **Pro** | 500 | 1.799€ | ~89% |
| **Premium** | 1.500 | 2.799€ | ~90% |
| **Enterprise** | Ilimitado | 5.799€ | ~92% |

### 2.2 Funcionalidades por plan

| Funcionalidad | Gratuito | Basic | Estándar | Pro | Premium | Enterprise |
|---------------|----------|-------|----------|-----|---------|------------|
| Gestión clientes | Sí | Sí | Sí | Sí | Sí | Sí |
| Recepción facturas | No | Sí | Sí | Sí | Sí | Sí |
| Chat | No | Sí | Sí | Sí | Sí | Sí |
| Vencimientos | Sí | Sí | Sí | Sí | Sí | Sí |
| Google Calendar | No | No | Sí | Sí | Sí | Sí |
| Inmatic (OCR) | No | No | No | Sí | Sí | Sí |
| API | No | No | No | Sí | Sí | Sí |
| Multi-usuario | No | No | No | No | Sí | Sí |
| Soporte prioritario | No | Sí | Sí | Sí | Sí | Sí |
| Account manager | No | No | No | No | Sí | Sí |
| SLA 99.9% | No | No | No | No | No | Sí |
| Desarrollo a medida | No | No | No | No | No | Sí |

### 2.3 Estrategia de precios

1. **Plan Gratuito:** Captación y prueba del producto
2. **Basic (300€):** Entrada al modelo de pago, funciones esenciales
3. **Estándar (650€):** Plan "sweet spot", mejor relación precio/funcionalidad
4. **Pro (1.799€):** Para asesorías medianas con necesidad de automatización
5. **Premium (2.799€):** Asesorías grandes con equipos
6. **Enterprise (5.799€):** Grandes asesorías, personalización total

---

## 3. Métricas Clave (KPIs)

### 3.1 Métricas de negocio

| KPI | Descripción | Objetivo |
|-----|-------------|----------|
| **MRR** | Ingresos recurrentes mensuales | Crecimiento mensual >10% |
| **ARR** | Ingresos recurrentes anuales | - |
| **Churn Rate** | Tasa de cancelación mensual | <3% |
| **LTV** | Valor de vida del cliente | >36 meses de facturación |
| **CAC** | Coste de adquisición de cliente | <3 meses de facturación |
| **LTV/CAC** | Ratio de rentabilidad | >3x |

### 3.2 Métricas de producto

| KPI | Descripción | Objetivo |
|-----|-------------|----------|
| **Asesorías activas** | Con login últimos 30 días | >80% |
| **Clientes por asesoría** | Media de clientes vinculados | >30 |
| **Facturas/mes** | Facturas procesadas | Crecimiento >15%/mes |
| **Tiempo de respuesta** | A solicitudes de cita | <24h |
| **NPS** | Net Promoter Score | >50 |

### 3.3 Métricas técnicas

| KPI | Descripción | Objetivo |
|-----|-------------|----------|
| **Uptime** | Disponibilidad del servicio | >99.5% |
| **Tiempo de carga** | Páginas principales | <2s |
| **Errores API** | Tasa de errores | <0.1% |
| **Tiempo resolución bugs** | Críticos/Altos | <24h/<72h |

---

## 4. Flujo de Adquisición de Clientes

### 4.1 Funnel de conversión

```
Visitante Web
    ↓
Registro Gratuito (Asesoría)
    ↓
Activación (1er cliente vinculado)
    ↓
Engagement (>5 facturas recibidas)
    ↓
Conversión (Upgrade a plan pago)
    ↓
Retención (Renovación anual)
    ↓
Expansión (Upgrade de plan)
```

### 4.2 Canales de adquisición

1. **Orgánico (SEO):** Posicionamiento en búsquedas relacionadas
2. **Referidos:** Programa de afiliados/comerciales
3. **Contenido:** Blog, guías, webinars
4. **Partnerships:** Colegios profesionales, asociaciones
5. **Paid (SEM):** Google Ads, LinkedIn Ads

### 4.3 Sistema de comerciales

- Código único por comercial
- Comisión por cliente captado (configurable)
- Dashboard de seguimiento
- Pagos automáticos de comisiones

---

## 5. Arquitectura del Sistema

### 5.1 Stack tecnológico

| Capa | Tecnología |
|------|------------|
| **Frontend** | PHP + JavaScript + Bootstrap |
| **Backend** | PHP 8.x |
| **Base de datos** | MySQL 8.x |
| **Pagos** | Stripe |
| **OCR** | Inmatic API |
| **Calendario** | Google Calendar API |
| **Hosting** | Servidor dedicado/VPS |
| **CDN** | Cloudflare (opcional) |

### 5.2 Integraciones actuales

| Sistema | Estado | Plan mínimo |
|---------|--------|-------------|
| **Stripe** | Activo | Todos |
| **Google Calendar** | Activo | Estándar+ |
| **Inmatic** | Activo | Pro+ |
| **Email (SMTP)** | Activo | Todos |
| **Push Notifications** | Activo | Todos |

### 5.3 Integraciones futuras potenciales

- **Contabilidad:** A3, Sage, ContaSol
- **Firma electrónica:** Docusign, Signaturit
- **Almacenamiento:** Google Drive, Dropbox
- **CRM:** Salesforce, HubSpot
- **Facturación electrónica:** FacturaE, Facturae

---

## 6. Estructura de la Base de Datos

### 6.1 Entidades principales

| Tabla | Descripción | Registros est. |
|-------|-------------|----------------|
| `users` | Todos los usuarios | Alto |
| `advisories` | Asesorías registradas | Medio |
| `customers_advisories` | Relación cliente-asesoría | Alto |
| `advisory_invoices` | Facturas de clientes | Muy alto |
| `advisory_appointments` | Citas | Alto |
| `vencimientos` | Vencimientos de clientes | Alto |
| `subscriptions` | Suscripciones de pago | Medio |

### 6.2 Relaciones clave

```
users (1) -----> (N) customers_advisories
                           |
advisories (1) <----+------+
    |
    +----> (N) advisory_invoices
    +----> (N) advisory_appointments
    +----> (N) vencimientos
    +----> (1) subscriptions
```

---

## 7. Roles de Usuario

### 7.1 Tipos de cuenta

| Rol | Descripción | Acceso |
|-----|-------------|--------|
| **Cliente** | Usuario final, cliente de asesoría | Panel cliente |
| **Asesoría** | Propietario/Admin de asesoría | Panel asesoría |
| **Comercial** | Captador de clientes | Panel comercial |
| **Administrador** | Staff de Facilitame | Panel admin |

### 7.2 Permisos por rol

**Cliente:**
- Ver/enviar facturas
- Ver vencimientos propios
- Solicitar/ver citas
- Chat con asesoría
- Ver/solicitar ofertas

**Asesoría:**
- Gestión completa de clientes
- Gestión de facturas (ver, procesar)
- Gestión de vencimientos (CRUD)
- Gestión de citas (CRUD)
- Publicar ofertas
- Chat con clientes
- Configuración de cuenta
- Integraciones (Inmatic, Calendar)

**Comercial:**
- Ver clientes captados
- Ver comisiones
- Dashboard de ventas

**Administrador:**
- Gestión global de usuarios
- Gestión de asesorías
- Métricas globales
- Configuración del sistema
- Soporte a usuarios

---

## 8. Seguridad

### 8.1 Medidas implementadas

| Área | Medida |
|------|--------|
| **Autenticación** | Contraseñas hasheadas (bcrypt) |
| **Sesiones** | Tokens seguros, expiración |
| **Comunicaciones** | SSL/TLS obligatorio |
| **Datos** | Cifrado en reposo (BD) |
| **Backups** | Copias diarias automatizadas |
| **Acceso** | Control por roles |
| **APIs** | Tokens de autenticación |
| **Pagos** | PCI DSS vía Stripe |

### 8.2 Cumplimiento normativo

- **GDPR:** Gestión de consentimientos, derecho al olvido
- **LOPD:** Registro de actividades de tratamiento
- **PCI DSS:** Delegado a Stripe (sin datos de tarjetas en servidor)

### 8.3 Recomendaciones futuras

- Implementar 2FA (autenticación de dos factores)
- Auditoría de accesos (logs detallados)
- Penetration testing periódico
- Seguro de ciberseguridad

---

## 9. Roadmap de Producto

### 9.1 Funcionalidades implementadas (v1.0)

- [x] Sistema de usuarios y roles
- [x] Gestión de asesorías y clientes
- [x] Envío y gestión de facturas
- [x] Sistema de vencimientos
- [x] Citas y agenda
- [x] Chat cliente-asesoría
- [x] Sistema de ofertas
- [x] Integración Inmatic (OCR)
- [x] Integración Google Calendar
- [x] Sistema de suscripciones (Stripe)
- [x] Panel de administración
- [x] Sistema de comerciales

### 9.2 Próximas funcionalidades (v1.1 - v2.0)

**Corto plazo (1-3 meses):**
- [ ] App móvil nativa (iOS/Android)
- [ ] Notificaciones push mejoradas
- [ ] Dashboard de métricas para asesorías
- [ ] Exportación de datos (Excel, CSV)

**Medio plazo (3-6 meses):**
- [ ] Firma electrónica de documentos
- [ ] Integración con más software contable
- [ ] Portal de auto-servicio mejorado
- [ ] Automatización de vencimientos (IA)

**Largo plazo (6-12 meses):**
- [ ] Análisis predictivo de facturación
- [ ] Chatbot de soporte
- [ ] Marketplace de servicios
- [ ] White-label para grandes clientes

---

## 10. Operaciones

### 10.1 Soporte

| Nivel | Canal | Tiempo respuesta |
|-------|-------|------------------|
| **L1** | Email, Chat | <4h (laborables) |
| **L2** | Teléfono | <2h |
| **L3** | Técnico | <24h |

### 10.2 Mantenimiento

- **Backups:** Diarios automáticos
- **Actualizaciones:** Ventana de mantenimiento semanal
- **Monitorización:** 24/7 automatizada
- **Incidencias:** Escalado según severidad

### 10.3 SLAs por plan

| Plan | Uptime | Soporte |
|------|--------|---------|
| Gratuito | 99% | Email |
| Basic | 99% | Email prioritario |
| Estándar | 99.5% | Email + Teléfono |
| Pro | 99.5% | Email + Teléfono |
| Premium | 99.9% | Dedicado |
| Enterprise | 99.9% | 24/7 |

---

## 11. Finanzas

### 11.1 Estructura de costes

| Categoría | % del ingreso |
|-----------|---------------|
| **Hosting/Infraestructura** | 5-8% |
| **Stripe (comisiones)** | 2-3% |
| **Inmatic (si aplicable)** | Variable |
| **Soporte** | 10-15% |
| **Desarrollo** | 15-20% |
| **Marketing** | 20-30% |
| **Administración** | 5-10% |
| **Margen bruto** | 25-40% |

### 11.2 Escenarios de crecimiento

**Escenario conservador (año 1):**
- 50 asesorías de pago
- ARPU: 800€
- ARR: 40.000€

**Escenario moderado (año 1):**
- 150 asesorías de pago
- ARPU: 1.000€
- ARR: 150.000€

**Escenario optimista (año 1):**
- 300 asesorías de pago
- ARPU: 1.200€
- ARR: 360.000€

### 11.3 Métricas financieras objetivo

| Métrica | Año 1 | Año 2 | Año 3 |
|---------|-------|-------|-------|
| Asesorías de pago | 100 | 300 | 700 |
| ARPU | 900€ | 1.100€ | 1.300€ |
| ARR | 90K€ | 330K€ | 910K€ |
| Churn anual | 15% | 12% | 10% |
| Margen bruto | 30% | 40% | 50% |

---

## 12. Equipo y Roles

### 12.1 Roles necesarios

| Rol | Responsabilidad | Prioridad |
|-----|-----------------|-----------|
| **CEO/Dirección** | Estrategia, relaciones | Existente |
| **CTO/Desarrollo** | Producto, tecnología | Crítico |
| **Comercial** | Ventas, partnerships | Alto |
| **Soporte** | Atención al cliente | Alto |
| **Marketing** | Adquisición, contenido | Medio |
| **Finanzas** | Contabilidad, reporting | Medio |

### 12.2 Estructura recomendada inicial

```
Dirección (1)
    |
    +-- Desarrollo (1-2)
    +-- Comercial (1)
    +-- Soporte (1, puede ser parcial)
```

---

## 13. Riesgos y Mitigación

### 13.1 Riesgos identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Competencia | Alta | Medio | Diferenciación, servicio |
| Churn alto | Media | Alto | Onboarding, soporte |
| Fallo técnico | Baja | Alto | Redundancia, backups |
| Regulatorio | Baja | Medio | Cumplimiento proactivo |
| Dependencia Stripe | Baja | Alto | Alternativas (Redsys) |
| Ciberataque | Media | Alto | Seguridad, seguros |

### 13.2 Plan de contingencia

**Fallo del servidor:**
1. Failover automático (si configurado)
2. Restauración desde backup (<4h)
3. Comunicación a clientes

**Brecha de seguridad:**
1. Aislamiento inmediato
2. Análisis forense
3. Notificación AEPD (72h)
4. Comunicación a afectados

---

## 14. Glosario Técnico

| Término | Definición |
|---------|------------|
| **SaaS** | Software as a Service - modelo de software en la nube |
| **B2B2C** | Business to Business to Consumer - venta a empresas que venden a consumidores |
| **MRR** | Monthly Recurring Revenue - ingresos recurrentes mensuales |
| **ARR** | Annual Recurring Revenue - ingresos recurrentes anuales |
| **ARPU** | Average Revenue Per User - ingreso promedio por usuario |
| **Churn** | Tasa de cancelación de clientes |
| **LTV** | Lifetime Value - valor total que genera un cliente |
| **CAC** | Customer Acquisition Cost - coste de adquirir un cliente |
| **NPS** | Net Promoter Score - indicador de satisfacción |
| **OCR** | Optical Character Recognition - reconocimiento de texto en imágenes |
| **API** | Application Programming Interface - interfaz de programación |
| **Webhook** | Callback HTTP para notificaciones entre sistemas |
| **SSL/TLS** | Protocolos de cifrado de comunicaciones |
| **GDPR** | Reglamento General de Protección de Datos (UE) |
| **PCI DSS** | Payment Card Industry Data Security Standard |

---

## 15. Contactos Clave

### 15.1 Proveedores

| Servicio | Proveedor | Contacto |
|----------|-----------|----------|
| **Pagos** | Stripe | dashboard.stripe.com |
| **OCR** | Inmatic | inmatic.ai |
| **Hosting** | [Proveedor] | [Portal] |
| **Dominio** | [Registrador] | [Portal] |
| **Email** | [Proveedor] | [Portal] |

### 15.2 Soporte interno

| Área | Responsable | Contacto |
|------|-------------|----------|
| Desarrollo | [Nombre] | [Email] |
| Comercial | [Nombre] | [Email] |
| Soporte | [Nombre] | [Email] |

---

## 16. Checklist de Lanzamiento

### 16.1 Pre-lanzamiento

- [ ] Testing completo de funcionalidades
- [ ] Revisión de seguridad
- [ ] Backups configurados
- [ ] Monitorización activa
- [ ] Documentación completa
- [ ] Soporte preparado
- [ ] Plan de comunicación
- [ ] Términos y condiciones legales
- [ ] Política de privacidad
- [ ] Configuración de Stripe (producción)
- [ ] DNS y SSL configurados

### 16.2 Lanzamiento

- [ ] Despliegue en producción
- [ ] Verificación de funcionamiento
- [ ] Comunicación de lanzamiento
- [ ] Activación de analytics

### 16.3 Post-lanzamiento

- [ ] Monitorización intensiva (72h)
- [ ] Feedback de primeros usuarios
- [ ] Ajustes y correcciones rápidas
- [ ] Plan de mejoras iterativas

---

*Última actualización: Diciembre 2025*
*Versión del documento: 1.0*
*Confidencial - Uso interno*
