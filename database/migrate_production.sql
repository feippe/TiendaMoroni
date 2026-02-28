-- ============================================================
--  TiendaMoroni – Migration script (local → production)
--  Seguro de ejecutar: crea solo lo que falte, no toca datos.
--  MySQL 8.0+
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ────────────────────────────────────────────────────────────
-- 1. TABLA: users
--    Agregar columnas email_verified y email_verified_at
-- ────────────────────────────────────────────────────────────

-- email_verified
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'users'
    AND COLUMN_NAME  = 'email_verified'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `users` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email`',
  'SELECT ''[skip] users.email_verified ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- email_verified_at
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'users'
    AND COLUMN_NAME  = 'email_verified_at'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME NULL DEFAULT NULL AFTER `email_verified`',
  'SELECT ''[skip] users.email_verified_at ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- 2. TABLA: email_verifications
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `email`      VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` VARCHAR(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ev_token` (`token_hash`),
  KEY `idx_ev_user`  (`user_id`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 3. TABLA: password_resets
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` VARCHAR(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_token` (`token_hash`),
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 4. TABLA: password_reset_attempts  (rate limiting)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pra_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 5. TABLA: media_folders
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `media_folders` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folders_parent` (`parent_id`),
  CONSTRAINT `fk_media_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 6. TABLA: media_files
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `media_files` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
  `filename`   VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url`        VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk_path`  VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type`  VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size`       INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_files_folder` (`folder_id`),
  CONSTRAINT `fk_media_files_folder` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 7. TABLA: vendor_contacts
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vendor_contacts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname`   VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone`      VARCHAR(30)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `email`      VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments`   TEXT COLLATE utf8mb4_unicode_ci,
  `ip_address` VARCHAR(45)  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 8. TABLA: site_settings
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value`       VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valor por defecto (INSERT IGNORE no rompe si ya existe)
INSERT IGNORE INTO `site_settings` (`setting_key`, `value`)
VALUES ('maintenance_mode', '0');

-- ────────────────────────────────────────────────────────────

SET foreign_key_checks = 1;

SELECT 'Migración completada exitosamente.' AS resultado;
