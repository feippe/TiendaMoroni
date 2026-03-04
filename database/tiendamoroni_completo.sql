-- ============================================================
--  TiendaMoroni – Script completo unificado
--  Ejecutar con: mysql -u root < tiendamoroni_completo.sql
--  MySQL 8.0+ | ENGINE=InnoDB | CHARSET=utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE DATABASE IF NOT EXISTS `tiendamoroni`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `tiendamoroni`;

-- 1. users
CREATE TABLE IF NOT EXISTS `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(120)    NOT NULL,
  `email`             VARCHAR(255)    NOT NULL,
  `password_hash`     VARCHAR(255)        NULL DEFAULT NULL,
  `avatar_url`        VARCHAR(500)        NULL DEFAULT NULL,
  `auth_provider`     ENUM('own','google') NOT NULL DEFAULT 'own',
  `google_id`         VARCHAR(80)         NULL DEFAULT NULL,
  `role`              ENUM('admin','buyer','vendor') NOT NULL DEFAULT 'buyer',
  `active`            TINYINT(1)      NOT NULL DEFAULT 1,
  `email_verified`    TINYINT(1)      NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME            NULL DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. vendors
CREATE TABLE IF NOT EXISTS `vendors` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`              BIGINT UNSIGNED NOT NULL,
  `business_name`        VARCHAR(180)    NOT NULL,
  `slug`                 VARCHAR(200)    NOT NULL,
  `business_description` TEXT                NULL DEFAULT NULL,
  `email`                VARCHAR(255)    NOT NULL,
  `phone`                VARCHAR(30)         NULL DEFAULT NULL,
  `is_verified`          TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendors_slug` (`slug`),
  KEY `idx_vendors_user` (`user_id`),
  CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(120)      NOT NULL,
  `slug`             VARCHAR(140)      NOT NULL,
  `description`      TEXT                  NULL,
  `image_url`        VARCHAR(500)          NULL DEFAULT NULL,
  `parent_id`        BIGINT UNSIGNED       NULL DEFAULT NULL,
  `meta_title`       VARCHAR(160)          NULL DEFAULT NULL,
  `meta_description` VARCHAR(320)          NULL DEFAULT NULL,
  `sort_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. products
CREATE TABLE IF NOT EXISTS `products` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`         BIGINT UNSIGNED NOT NULL,
  `category_id`       BIGINT UNSIGNED     NULL DEFAULT NULL,
  `name`              VARCHAR(255)    NOT NULL,
  `slug`              VARCHAR(280)    NOT NULL,
  `description`       LONGTEXT            NULL,
  `short_description` VARCHAR(500)        NULL DEFAULT NULL,
  `price`             DECIMAL(12,2)   NOT NULL,
  `stock`             INT UNSIGNED    NOT NULL DEFAULT 0,
  `status`            ENUM('active','inactive','draft') NOT NULL DEFAULT 'draft',
  `featured`          TINYINT(1)      NOT NULL DEFAULT 0,
  `main_image_url`    VARCHAR(500)        NULL DEFAULT NULL,
  `meta_title`        VARCHAR(160)        NULL DEFAULT NULL,
  `meta_description`  VARCHAR(320)        NULL DEFAULT NULL,
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

-- 5. product_images
CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED   NOT NULL,
  `image_url`  VARCHAR(500)      NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. product_questions
CREATE TABLE IF NOT EXISTS `product_questions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `question`    TEXT            NOT NULL,
  `answer`      TEXT                NULL,
  `answered_at` DATETIME            NULL DEFAULT NULL,
  `is_public`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pq_product` (`product_id`),
  KEY `idx_pq_user`    (`user_id`),
  CONSTRAINT `fk_pq_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pq_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `vendor_id`        BIGINT UNSIGNED     NULL DEFAULT NULL,
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

-- 8. order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `order_id`   BIGINT UNSIGNED   NOT NULL,
  `product_id` BIGINT UNSIGNED   NOT NULL,
  `quantity`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2)     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order`   (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. sessions
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

-- 10. email_verifications
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `email`      VARCHAR(150)    NOT NULL,
  `token_hash` VARCHAR(128)    NOT NULL,
  `expires_at` DATETIME        NOT NULL,
  `used`       TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at` DATETIME        DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ev_token` (`token_hash`),
  KEY `idx_ev_user`  (`user_id`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_token` (`token_hash`),
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. password_reset_attempts
CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45)  NOT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pra_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. media_folders
CREATE TABLE IF NOT EXISTS `media_folders` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120)    NOT NULL,
  `parent_id`  BIGINT UNSIGNED     NULL DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folders_parent` (`parent_id`),
  CONSTRAINT `fk_media_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. media_files
CREATE TABLE IF NOT EXISTS `media_files` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_id`  BIGINT UNSIGNED     NULL DEFAULT NULL,
  `filename`   VARCHAR(255)    NOT NULL,
  `url`        VARCHAR(500)    NOT NULL,
  `disk_path`  VARCHAR(500)    NOT NULL,
  `mime_type`  VARCHAR(100)    NOT NULL DEFAULT '',
  `size`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_files_folder` (`folder_id`),
  CONSTRAINT `fk_media_files_folder` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. vendor_contacts
CREATE TABLE IF NOT EXISTS `vendor_contacts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `lastname`   VARCHAR(100) NOT NULL,
  `phone`      VARCHAR(30)  NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `comments`   TEXT,
  `ip_address` VARCHAR(45)      DEFAULT NULL,
  `created_at` DATETIME         DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. site_settings
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `value`       VARCHAR(255) NOT NULL,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `site_settings` (`setting_key`, `value`) VALUES ('maintenance_mode', '0');

-- 17. wa_conversations
CREATE TABLE IF NOT EXISTS `wa_conversations` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number`     VARCHAR(20)  NOT NULL,
  `current_state`    VARCHAR(50)  NOT NULL DEFAULT 'WELCOME',
  `context_data`     JSON             NULL,
  `last_interaction` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wa_conv_phone` (`phone_number`),
  KEY `idx_wa_conv_state` (`current_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. wa_message_log
CREATE TABLE IF NOT EXISTS `wa_message_log` (
  `id`           INT UNSIGNED                NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(20)                 NOT NULL,
  `direction`    ENUM('incoming','outgoing') NOT NULL,
  `message_type` VARCHAR(30)                 NOT NULL,
  `payload`      JSON                        NOT NULL,
  `created_at`   DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wa_log_phone`     (`phone_number`),
  KEY `idx_wa_log_direction` (`direction`),
  KEY `idx_wa_log_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. wa_orders
CREATE TABLE IF NOT EXISTS `wa_orders` (
  `id`                  INT UNSIGNED                            NOT NULL AUTO_INCREMENT,
  `phone_number`        VARCHAR(20)                             NOT NULL,
  `catalog_id`          VARCHAR(50)                             NOT NULL,
  `product_retailer_id` VARCHAR(50)                             NOT NULL,
  `product_id`          BIGINT UNSIGNED                             NULL DEFAULT NULL,
  `vendor_id`           BIGINT UNSIGNED                             NULL DEFAULT NULL,
  `quantity`            SMALLINT UNSIGNED                       NOT NULL DEFAULT 1,
  `item_price`          DECIMAL(12,2)                           NOT NULL DEFAULT 0.00,
  `currency`            VARCHAR(10)                             NOT NULL DEFAULT 'UYU',
  `status`              ENUM('pending','contacted','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`          DATETIME                                NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wa_orders_phone`  (`phone_number`),
  KEY `idx_wa_orders_vendor` (`vendor_id`),
  KEY `idx_wa_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT IGNORE INTO `users` (`id`,`name`,`email`,`password_hash`,`auth_provider`,`role`,`email_verified`) VALUES
(1,'Admin TiendaMoroni','admin@tiendamoroni.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','own','admin',1),
(2,'Hermana López','artesanias.lopez@tiendamoroni.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','own','buyer',1);

INSERT IGNORE INTO `vendors` (`id`,`user_id`,`business_name`,`slug`,`email`,`phone`) VALUES
(1,2,'Artesanías Hermana López','artesanias-hermana-lopez','artesanias.lopez@tiendamoroni.com','+598 99 123 456');

INSERT IGNORE INTO `categories` (`id`,`name`,`slug`,`description`,`image_url`,`meta_title`,`meta_description`,`sort_order`) VALUES
(1,'Escrituras','escrituras','Tapas, fundas y accesorios artesanales para el Libro de Mormón y la Biblia.','https://picsum.photos/seed/scriptures-lds/600/400','Escrituras artesanales – TiendaMoroni','Descubrí tapas, fundas y marcadores artesanales para tus escrituras sagradas.',1),
(2,'Joyería y Accesorios','joyeria-accesorios','Llaveros CTR, pulseras, anillos y bijou con símbolos de la Iglesia hechos a mano.','https://picsum.photos/seed/jewelry-lds/600/400','Joyería artesanal – TiendaMoroni','Llaveros CTR, pulseras y accesorios con símbolos de la fe, creados por hermanos artesanos.',2),
(3,'Decoración del Hogar','decoracion-hogar','Cuadros con escrituras, figuras del Ángel Moroni y adornos con temática de la Iglesia.','https://picsum.photos/seed/homedeco-lds/600/400','Decoración del hogar – TiendaMoroni','Adornà tu hogar con cuadros, figuras y piezas artesanales con temática de la Iglesia.',3),
(4,'Artículos para Misioneros','misioneros','Fundas de credencial, libretas personalizadas y sets especiales para misioneros.','https://picsum.photos/seed/missionary-lds/600/400','Artículos para misioneros – TiendaMoroni','Equipá al misionero de tu familia con artesanías hechas con amor y fe.',4),
(5,'Regalos y Ocasiones','regalos','Regalos únicos para bautismos, confirmaciones, bodas en el templo y misiones.','https://picsum.photos/seed/gifts-lds/600/400','Regalos para ocasiones especiales – TiendaMoroni','Encontrá el regalo perfecto para cada momento especial de nuestra comunidad.',5),
(6,'Aceiteros y Bendición','aceiteros-bendicion','Aceiteros artesanales en madera, cuero y cerámica para la ordenanza de sanidad.','https://picsum.photos/seed/anointing-lds/600/400','Aceiteros artesanales – TiendaMoroni','Aceiteros para la ordenanza de sanidad, hechos con dedicación y respeto.',6);

INSERT IGNORE INTO `products` (`id`,`vendor_id`,`category_id`,`name`,`slug`,`description`,`short_description`,`price`,`stock`,`status`,`featured`,`main_image_url`,`meta_title`,`meta_description`) VALUES
(1,1,1,'Tapa artesanal para el Libro de Mormón — cuero marrón','tapa-libro-mormon-cuero-marron','<p>Tapa protectora hecha a mano en cuero genuino marrón oscuro.</p>','Tapa protectora en cuero genuino hecha a mano para el Libro de Mormón.',890.00,12,'active',1,'https://picsum.photos/seed/book-cover-lds/600/600','Tapa artesanal Libro de Mormón – TiendaMoroni','Tapa en cuero genuino hecha a mano para el Libro de Mormón.'),
(2,1,2,'Llavero CTR bordado a mano','llavero-ctr-bordado','<p>Llavero artesanal con el escudo CTR bordado en hilo dorado sobre fondo azul marino.</p>','Llavero con escudo CTR bordado en hilo dorado, hecho a mano en Uruguay.',320.00,30,'active',1,'https://picsum.photos/seed/ctr-keychain/600/600','Llavero CTR bordado – TiendaMoroni','Llavero artesanal CTR bordado a mano.'),
(3,1,3,'Cuadro con escritura de Moroni 10:4','cuadro-moroni-10-4','<p>Cuadro de madera pintado a mano con la escritura de Moroni 10:4.</p>','Cuadro artesanal con Moroni 10:4 pintado a mano, terminación dorada.',1250.00,8,'active',1,'https://picsum.photos/seed/moroni-scripture/600/600','Cuadro Moroni 10:4 artesanal – TiendaMoroni','Cuadro con escritura de Moroni 10:4 pintado a mano.'),
(4,1,6,'Set aceitero de bendición en madera de algarrobo','set-aceitero-madera-algarrobo','<p>Aceitero artesanal torneado en madera de algarrobo uruguayo.</p>','Aceitero artesanal torneado en algarrobo uruguayo con frasco de vidrio interior.',680.00,15,'active',1,'https://picsum.photos/seed/anointing-wood/600/600','Set aceitero artesanal – TiendaMoroni','Aceitero de bendición en madera de algarrobo uruguayo.'),
(5,1,4,'Funda de credencial para misionero — personalizada','funda-credencial-misionero','<p>Funda de credencial en cuero sintético con nombre del misionero bordado.</p>','Funda para credencial de misionero en cuero sintético con nombre bordado.',490.00,25,'active',1,'https://picsum.photos/seed/missionary-badge/600/600','Funda de credencial misionero – TiendaMoroni','Funda de credencial personalizada para misioneros.'),
(6,1,2,'Pulsera artesanal con símbolo del templo','pulsera-simbolo-templo','<p>Pulsera trenzada a mano en hilo de seda blanco y dorado.</p>','Pulsera trenzada en seda blanca y dorada con dije del templo.',420.00,20,'active',0,'https://picsum.photos/seed/temple-bracelet/600/600','Pulsera símbolo del templo – TiendaMoroni','Pulsera artesanal con símbolo del templo.'),
(7,1,5,'Set de regalo para bautismo','set-regalo-bautismo','<p>Set artesanal de bautismo: libreta, llavero CTR, marcador bordado y tarjeta personalizada.</p>','Set artesanal de bautismo con libreta, llavero CTR, marcador y tarjeta personalizada.',1490.00,10,'active',1,'https://picsum.photos/seed/baptism-gift/600/600','Set de bautismo artesanal – TiendaMoroni','Regalo artesanal completo para el día del bautismo.'),
(8,1,1,'Funda acolchada para escrituras — arpillera bordada','funda-escrituras-arpillera','<p>Funda protectora en arpillera natural con bordado artesanal del Ángel Moroni.</p>','Funda en arpillera natural con Ángel Moroni bordado en hilo dorado y azul.',780.00,18,'active',0,'https://picsum.photos/seed/scripture-bag/600/600','Funda escrituras arpillera – TiendaMoroni','Funda artesanal para escrituras con Ángel Moroni bordado.');

INSERT IGNORE INTO `product_images` (`product_id`,`image_url`,`sort_order`) VALUES
(1,'https://picsum.photos/seed/book-cover-detail/600/600',1),
(1,'https://picsum.photos/seed/book-cover-open/600/600',2),
(3,'https://picsum.photos/seed/scripture-frame-detail/600/600',1),
(4,'https://picsum.photos/seed/aceitero-detail/600/600',1),
(7,'https://picsum.photos/seed/baptism-set-open/600/600',1);

SET foreign_key_checks = 1;
SELECT 'TiendaMoroni: base de datos creada exitosamente.' AS resultado;