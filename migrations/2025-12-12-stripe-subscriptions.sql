-- ============================================================
-- MIGRACION: Sistema de Suscripciones con Stripe
-- 2025-12-12
-- ============================================================

-- Tabla principal de suscripciones
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `advisory_id` BIGINT UNSIGNED NOT NULL,
    `stripe_customer_id` VARCHAR(255) NOT NULL,
    `stripe_subscription_id` VARCHAR(255) DEFAULT NULL,
    `stripe_price_id` VARCHAR(255) DEFAULT NULL,
    `plan` ENUM('gratuito', 'basic', 'estandar', 'pro', 'premium', 'enterprise') NOT NULL DEFAULT 'gratuito',
    `status` ENUM('active', 'past_due', 'canceled', 'incomplete', 'trialing', 'unpaid') NOT NULL DEFAULT 'incomplete',
    `current_period_start` DATETIME DEFAULT NULL,
    `current_period_end` DATETIME DEFAULT NULL,
    `cancel_at_period_end` TINYINT(1) DEFAULT 0,
    `canceled_at` DATETIME DEFAULT NULL,
    `trial_start` DATETIME DEFAULT NULL,
    `trial_end` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_advisory` (`advisory_id`),
    KEY `idx_stripe_customer` (`stripe_customer_id`),
    KEY `idx_stripe_subscription` (`stripe_subscription_id`),
    KEY `idx_status` (`status`),
    KEY `idx_plan` (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de pagos/facturas
CREATE TABLE IF NOT EXISTS `subscription_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subscription_id` INT NOT NULL,
    `stripe_invoice_id` VARCHAR(255) NOT NULL,
    `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'eur',
    `status` ENUM('draft', 'open', 'paid', 'void', 'uncollectible') NOT NULL,
    `invoice_pdf` VARCHAR(500) DEFAULT NULL,
    `hosted_invoice_url` VARCHAR(500) DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `period_start` DATETIME DEFAULT NULL,
    `period_end` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_subscription` (`subscription_id`),
    KEY `idx_stripe_invoice` (`stripe_invoice_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Métodos de pago guardados
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `advisory_id` BIGINT UNSIGNED NOT NULL,
    `stripe_payment_method_id` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'card',
    `card_brand` VARCHAR(50) DEFAULT NULL,
    `card_last4` VARCHAR(4) DEFAULT NULL,
    `card_exp_month` INT DEFAULT NULL,
    `card_exp_year` INT DEFAULT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_advisory` (`advisory_id`),
    KEY `idx_stripe_pm` (`stripe_payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de eventos de Stripe (webhooks)
CREATE TABLE IF NOT EXISTS `stripe_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stripe_event_id` VARCHAR(255) NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `data` JSON DEFAULT NULL,
    `processed` TINYINT(1) DEFAULT 0,
    `processed_at` DATETIME DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_event` (`stripe_event_id`),
    KEY `idx_type` (`type`),
    KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir stripe_customer_id a advisories si no existe
SET @exist_stripe_customer := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'advisories'
    AND COLUMN_NAME = 'stripe_customer_id');

SET @sql_stripe_customer := IF(@exist_stripe_customer = 0,
    'ALTER TABLE `advisories` ADD COLUMN `stripe_customer_id` VARCHAR(255) DEFAULT NULL',
    'SELECT "Column stripe_customer_id already exists"');
PREPARE stmt FROM @sql_stripe_customer;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Stripe subscriptions migration completed' AS status;
