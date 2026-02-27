-- ============================================================
--  TiendaMoroni – Full Database Schema
--  MySQL 8.0+  |  ENGINE=InnoDB  |  CHARSET=utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(255)    NOT NULL,
  `password_hash` VARCHAR(255)        NULL  DEFAULT NULL,
  `avatar_url`    VARCHAR(500)        NULL  DEFAULT NULL,
  `auth_provider` ENUM('own','google') NOT NULL DEFAULT 'own',
  `google_id`     VARCHAR(80)         NULL  DEFAULT NULL,
  `role`          ENUM('admin','buyer','vendor') NOT NULL DEFAULT 'buyer',
  `active`        TINYINT(1)           NOT NULL DEFAULT 1,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Vendors ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vendors` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`              BIGINT UNSIGNED NOT NULL,
  `business_name`        VARCHAR(180)    NOT NULL,
  `slug`                 VARCHAR(200)    NOT NULL,
  `business_description` TEXT                NULL  DEFAULT NULL,
  `email`                VARCHAR(255)    NOT NULL,
  `phone`                VARCHAR(30)         NULL  DEFAULT NULL,
  `is_verified`          TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendors_slug` (`slug`),
  KEY `idx_vendors_user` (`user_id`),
  CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Categories ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(120)    NOT NULL,
  `slug`             VARCHAR(140)    NOT NULL,
  `description`      TEXT                NULL,
  `image_url`        VARCHAR(500)        NULL  DEFAULT NULL,
  `parent_id`        BIGINT UNSIGNED     NULL  DEFAULT NULL,
  `meta_title`       VARCHAR(160)        NULL  DEFAULT NULL,
  `meta_description` VARCHAR(320)        NULL  DEFAULT NULL,
  `sort_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Products ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`         BIGINT UNSIGNED NOT NULL,
  `category_id`       BIGINT UNSIGNED     NULL  DEFAULT NULL,
  `name`              VARCHAR(255)    NOT NULL,
  `slug`              VARCHAR(280)    NOT NULL,
  `description`       LONGTEXT            NULL,
  `short_description` VARCHAR(500)        NULL  DEFAULT NULL,
  `price`             DECIMAL(12,2)   NOT NULL,
  `stock`             INT UNSIGNED    NOT NULL DEFAULT 0,
  `status`            ENUM('active','inactive','draft') NOT NULL DEFAULT 'draft',
  `featured`          TINYINT(1)      NOT NULL DEFAULT 0,
  `main_image_url`    VARCHAR(500)        NULL  DEFAULT NULL,
  `meta_title`        VARCHAR(160)        NULL  DEFAULT NULL,
  `meta_description`  VARCHAR(320)        NULL  DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_vendor`   (`vendor_id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status`   (`status`),
  KEY `idx_products_featured` (`featured`),
  FULLTEXT KEY `ft_products_search` (`name`,`short_description`),
  CONSTRAINT `fk_products_vendor`   FOREIGN KEY (`vendor_id`)   REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product Images ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `image_url`  VARCHAR(500)    NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Product Q&A ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `product_questions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `question`    TEXT            NOT NULL,
  `answer`      TEXT                NULL,
  `answered_at` DATETIME            NULL  DEFAULT NULL,
  `is_public`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pq_product` (`product_id`),
  KEY `idx_pq_user`    (`user_id`),
  CONSTRAINT `fk_pq_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pq_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Orders ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `vendor_id`        BIGINT UNSIGNED     NULL  DEFAULT NULL,
  `status`           ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal`         DECIMAL(12,2)   NOT NULL,
  `total`            DECIMAL(12,2)   NOT NULL,
  `contact_phone`    VARCHAR(30)     NOT NULL,
  `shipping_address` TEXT            NOT NULL,
  `notes`            TEXT                NULL,
  `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user`   (`user_id`),
  KEY `idx_orders_vendor` (`vendor_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`),
  CONSTRAINT `fk_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Order Items ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2)   NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order`   (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sessions ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sessions` (
  `token`      CHAR(64)        NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `expires_at` DATETIME        NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  KEY `idx_sessions_user`    (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
-- ── Vendor contacts (publicar-gratis landing form) ────────────────────────────
CREATE TABLE IF NOT EXISTS vendor_contacts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  lastname     VARCHAR(100) NOT NULL,
  phone        VARCHAR(30)  NOT NULL,
  email        VARCHAR(150) NOT NULL,
  comments     TEXT,
  ip_address   VARCHAR(45),
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Site settings (key/value store) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  value       VARCHAR(255) NOT NULL,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_settings (setting_key, value)
VALUES ('maintenance_mode', '0');
