-- ============================================================
--  WhatsApp Bot – TiendaMoroni – Migraciones de base de datos
--  Ejecutar una sola vez en producción y desarrollo.
-- ============================================================

SET NAMES utf8mb4;

-- ── Conversaciones (máquina de estados) ─────────────────────────────────────
-- Guarda el estado actual de cada conversación identificada por número de teléfono.
CREATE TABLE IF NOT EXISTS `wa_conversations` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `phone_number`     VARCHAR(20)     NOT NULL,
    `current_state`    VARCHAR(50)     NOT NULL DEFAULT 'WELCOME',
    `context_data`     JSON                NULL,
    `last_interaction` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_wa_conv_phone` (`phone_number`),
    KEY `idx_wa_conv_state` (`current_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Log de mensajes (debugging y auditoría) ──────────────────────────────────
-- Registra todos los mensajes entrantes y salientes para facilitar el debugging.
CREATE TABLE IF NOT EXISTS `wa_message_log` (
    `id`           INT UNSIGNED                    NOT NULL AUTO_INCREMENT,
    `phone_number` VARCHAR(20)                     NOT NULL,
    `direction`    ENUM('incoming', 'outgoing')    NOT NULL,
    `message_type` VARCHAR(30)                     NOT NULL,
    `payload`      JSON                            NOT NULL,
    `created_at`   DATETIME                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wa_log_phone`     (`phone_number`),
    KEY `idx_wa_log_direction` (`direction`),
    KEY `idx_wa_log_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pedidos vía WhatsApp ─────────────────────────────────────────────────────
-- Tabla independiente de `orders` porque los clientes de WhatsApp
-- no necesitan cuenta registrada en el sistema.
CREATE TABLE IF NOT EXISTS `wa_orders` (
    `id`                  INT UNSIGNED                                NOT NULL AUTO_INCREMENT,
    `phone_number`        VARCHAR(20)                                 NOT NULL,
    `catalog_id`          VARCHAR(50)                                 NOT NULL,
    `product_retailer_id` VARCHAR(50)                                 NOT NULL,
    `product_id`          BIGINT UNSIGNED                                 NULL  DEFAULT NULL,
    `vendor_id`           BIGINT UNSIGNED                                 NULL  DEFAULT NULL,
    `quantity`            SMALLINT UNSIGNED                           NOT NULL DEFAULT 1,
    `item_price`          DECIMAL(12,2)                               NOT NULL DEFAULT 0.00,
    `currency`            VARCHAR(10)                                 NOT NULL DEFAULT 'UYU',
    `status`              ENUM('pending','contacted','cancelled')     NOT NULL DEFAULT 'pending',
    `created_at`          DATETIME                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wa_orders_phone`  (`phone_number`),
    KEY `idx_wa_orders_vendor` (`vendor_id`),
    KEY `idx_wa_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Nota sobre tablas existentes ─────────────────────────────────────────────
-- La tabla `vendors` ya tiene el campo `phone` (VARCHAR 30).
-- La tabla `products` usa `id` como product_retailer_id en el feed XML (<g:id>).
-- No se requieren ALTER TABLE adicionales.
