# Guía de Despliegue - Facilitame

## Entornos

| Entorno | URL | Descripción |
|---------|-----|-------------|
| Local | http://facilitame.test | Desarrollo local (Laragon) |
| Demo | TBD | Entorno de pruebas |
| Production | TBD | Producción |

## Preparación para Despliegue a Demo

### 1. Archivos a Desplegar
```bash
# Sincronizar código (excluyendo uploads y configuración local)
rsync -avz --exclude='uploads/' \
           --exclude='.env' \
           --exclude='config.php' \
           --exclude='.git/' \
           --exclude='.idea/' \
           --exclude='node_modules/' \
           ./ user@demo-server:/path/to/facilitame/
```

### 2. Archivos a PRESERVAR en Demo
```
uploads/              # Documentos y fotos de usuarios
  ├── invoices/       # Facturas subidas
  ├── profile/        # Fotos de perfil
  ├── requests/       # Documentos de solicitudes
  └── offers/         # Documentos de ofertas
```

### 3. Configuración de Entorno

Crear/actualizar `config.php` en el servidor demo:
```php
<?php
// Entorno
define('ENVIRONMENT', 'DEMO');

// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'facilitame_demo');
define('DB_USER', 'facilitame_user');
define('DB_PASSWORD', 'secure_password');

// URLs
define('ROOT_URL', 'https://demo.facilitame.es');
define('ROOT_DIR', '/var/www/demo.facilitame.es');

// SMTP (en DEMO los emails van a un destinatario fijo)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@facilitame.es');
define('SMTP_PASSWORD', 'smtp_password');
define('SMTP_FROM', 'Facilítame Demo');
```

### 4. Migraciones de Base de Datos

```bash
# Exportar solo estructura (sin datos sensibles)
mysqldump -h localhost -u root facilitame \
  --no-data \
  --routines \
  --triggers > schema_update.sql

# Importar en demo
mysql -h demo-host -u facilitame_user -p facilitame_demo < schema_update.sql
```

### 5. Permisos de Directorios

```bash
# En el servidor demo
chmod -R 755 /path/to/facilitame/
chmod -R 777 /path/to/facilitame/uploads/
chown -R www-data:www-data /path/to/facilitame/
```

## Modo DEMO

Cuando `ENVIRONMENT === 'DEMO'`:

1. **Emails**: Todos se envían a `erlantz@facilitame.es` con prefijo "DEMO:"
2. **Logs**: Más verbosos para depuración
3. **Pagos**: Deshabilitados o en modo sandbox

## Checklist Pre-Despliegue

- [ ] Backup de la base de datos demo actual
- [ ] Backup de uploads demo actuales
- [ ] Verificar que .gitignore excluye archivos sensibles
- [ ] Actualizar schema.sql si hay cambios de BD
- [ ] Probar localmente todos los flujos críticos
- [ ] Verificar configuración SMTP para demo

## Checklist Post-Despliegue

- [ ] Verificar que uploads existentes siguen accesibles
- [ ] Probar login con usuarios existentes
- [ ] Probar flujo de registro nuevo
- [ ] Probar flujo de recuperación de contraseña
- [ ] Verificar que emails llegan (al buzón de demo)
- [ ] Probar APIs desde app móvil
- [ ] Verificar que crons funcionan

## Pruebas de App Móvil contra Demo

### Configuración de App
```
API_BASE_URL=https://demo.facilitame.es/api
ENVIRONMENT=demo
```

### Endpoints a Probar
1. `/api/login` - Autenticación
2. `/api/app-dashboard` - Dashboard
3. `/api/app-services` - Listado de servicios
4. `/api/app-token-save-fcm` - Push notifications
5. `/api/app-user-profile` - Perfil de usuario

### Casos de Prueba App
- [ ] Login con credenciales válidas
- [ ] Login con credenciales inválidas
- [ ] Ver dashboard
- [ ] Ver lista de servicios
- [ ] Ver detalle de servicio
- [ ] Subir documento
- [ ] Ver notificaciones
- [ ] Actualizar perfil
- [ ] Cambiar contraseña
- [ ] Logout

## Rollback

En caso de problemas:
```bash
# Restaurar backup de código
rsync -avz backup/code/ /path/to/facilitame/

# Restaurar backup de BD
mysql -u facilitame_user -p facilitame_demo < backup/db_backup.sql
```

## Contacto

Para problemas de despliegue, contactar al equipo de desarrollo.

---

*Guía de Despliegue - Facilitame*
