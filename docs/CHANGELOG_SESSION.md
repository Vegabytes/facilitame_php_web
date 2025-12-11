# Changelog - Sesión 11/12/2025

## Resumen de Cambios

### Archivos Creados

#### APIs Nuevas
1. **`api/advisories-add.php`** - Crear asesoría (Admin)
   - Validación de CIF único
   - Creación de usuario asociado opcional
   - Generación de código identificación único (ASE-{CIF})
   - Envío de email de activación

2. **`api/advisories-update.php`** - Actualizar asesoría (Admin)
   - Campos: razon_social, cif, email_empresa, direccion, telefono, plan, estado

3. **`api/advisories-delete.php`** - Eliminar asesoría (Admin)
   - Soft delete para mantener integridad histórica
   - Desvinculación de clientes
   - Eliminación de rol de asesoría si no tiene otros roles

4. **`api/customer-link-advisory.php`** - Vincular cliente con asesoría
   - Usa código de identificación
   - Notifica a la asesoría
   - Envía email de confirmación al cliente

#### JavaScript
5. **`assets/js/custom/activate-with-password.js`**
   - Formulario de activación con contraseña
   - Validación: mínimo 8 caracteres, letras y números
   - Faltaba y rompía el flujo de activación

#### Tests
6. **`tests/test_emails.php`** - Suite de tests de emails
   - Test de registro y activación
   - Test de recuperación de contraseña
   - Test de creación de usuario por admin/asesoría
   - Integración con MailHog

#### Documentación
7. **`docs/README.md`** - Documentación general del proyecto
8. **`docs/API_ASESORIA.md`** - Documentación de APIs de asesorías
9. **`docs/DEPLOYMENT.md`** - Guía de despliegue

### Archivos Modificados

#### Correcciones
1. **`api/restore.php`**
   - Eliminada variable `$data` indefinida en catch

2. **`api/sales-rep-delete.php`**
   - Cambiado de DELETE físico a soft delete
   - Ahora usa `UPDATE ... SET deleted_at = NOW()`

### Verificaciones Realizadas

#### Módulo Asesoría
- [x] Envío de facturas restringido por plan (gratuito no puede)
- [x] Estados de citas: solicitado, agendado, finalizado, cancelado
- [x] Comunicaciones con filtros por tipo cliente
- [x] Niveles de importancia: leve, media, importante
- [x] Recordatorio 24h para comunicaciones importantes no leídas
- [x] Chat asesoría-cliente implementado
- [x] Comisiones comerciales por asesoría

#### Flujos de Email
- [x] Registro → Activación (48h)
- [x] Recuperación → Restablecimiento (1h)
- [x] Admin crea usuario → Activación
- [x] Asesoría crea cliente → Activación

### Estructura de Documentación Creada

```
docs/
├── README.md           # Documentación general
├── API_ASESORIA.md     # APIs del módulo asesoría
├── DEPLOYMENT.md       # Guía de despliegue
└── CHANGELOG_SESSION.md # Este archivo
```

### Pendiente para Próximas Sesiones

#### Fase Actual
- [ ] Despliegue a entorno demo
- [ ] Pruebas de app móvil contra demo
- [ ] Verificar uploads existentes en demo

#### Fases Futuras
- [ ] Integración Inmatic (API v1.0.15)
- [ ] Integración Google Calendar
- [ ] Pasarela de pago
- [ ] Expandir app móvil a otros perfiles

### Estadísticas del Proyecto

| Métrica | Valor |
|---------|-------|
| APIs totales | 118 |
| Controladores | 106 |
| Tablas BD | 42 |
| Roles de usuario | 6 |

---

*Sesión de desarrollo - 11/12/2025*
