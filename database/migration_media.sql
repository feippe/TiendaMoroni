CREATE TABLE IF NOT EXISTS `media_folders` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `parent_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folders_parent` (`parent_id`),
  CONSTRAINT `fk_media_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `media_folders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_files` (
  `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `filename`  VARCHAR(255) NOT NULL,
  `url`       VARCHAR(500) NOT NULL,
  `disk_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL DEFAULT '',
  `size`      INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_files_folder` (`folder_id`),
  CONSTRAINT `fk_media_files_folder` FOREIGN KEY (`folder_id`) REFERENCES `media_folders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
