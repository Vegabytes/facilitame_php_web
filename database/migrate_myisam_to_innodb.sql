-- ============================================
-- FACILITAME - Migración MyISAM a InnoDB
-- ============================================
-- Ejecutar en Laragon:
-- mysql -u root facilitame < database/migrate_myisam_to_innodb.sql
-- ============================================

-- Hacer backup primero (recomendado)
-- mysqldump -u root facilitame > backup_antes_migracion.sql

-- ============================================
-- PASO 1: Migrar tablas de MyISAM a InnoDB
-- ============================================

ALTER TABLE advisories ENGINE=InnoDB;
ALTER TABLE advisory_appointments ENGINE=InnoDB;
ALTER TABLE advisory_communications ENGINE=InnoDB;
ALTER TABLE advisory_communication_recipients ENGINE=InnoDB;
ALTER TABLE advisory_invoices ENGINE=InnoDB;

-- ============================================
-- PASO 2: Corregir tipos de columnas FK
-- ============================================
-- Las columnas FK deben coincidir con el tipo de la PK referenciada
-- users.id es BIGINT UNSIGNED, pero advisory_appointments usa INT

ALTER TABLE advisory_appointments
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE advisory_invoices
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE advisory_communications
    MODIFY COLUMN advisory_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE advisory_communication_recipients
    MODIFY COLUMN communication_id BIGINT UNSIGNED NOT NULL,
    MODIFY COLUMN customer_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE advisories
    MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL;

-- ============================================
-- PASO 3: Agregar Foreign Keys
-- ============================================

-- Tabla advisories
ALTER TABLE advisories
    ADD CONSTRAINT fk_advisories_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- Tabla advisory_appointments
ALTER TABLE advisory_appointments
    ADD CONSTRAINT fk_appointments_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_appointments_customer
    FOREIGN KEY (customer_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabla advisory_invoices
ALTER TABLE advisory_invoices
    ADD CONSTRAINT fk_invoices_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_invoices_customer
    FOREIGN KEY (customer_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabla advisory_communications
ALTER TABLE advisory_communications
    ADD CONSTRAINT fk_communications_advisory
    FOREIGN KEY (advisory_id) REFERENCES advisories(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabla advisory_communication_recipients
ALTER TABLE advisory_communication_recipients
    ADD CONSTRAINT fk_recipients_communication
    FOREIGN KEY (communication_id) REFERENCES advisory_communications(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_recipients_customer
    FOREIGN KEY (customer_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================
-- PASO 4: Agregar PK a tabla regions (si no existe)
-- ============================================

-- Primero verificar si tiene PK
-- Si no la tiene, ejecutar:
-- ALTER TABLE regions MODIFY COLUMN code VARCHAR(2) NOT NULL;
-- ALTER TABLE regions ADD PRIMARY KEY (code);

-- ============================================
-- VERIFICACION
-- ============================================

-- Verificar que todas las tablas son InnoDB:
SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'facilitame'
AND TABLE_NAME LIKE 'advisory%';

-- Verificar Foreign Keys creadas:
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'facilitame'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
