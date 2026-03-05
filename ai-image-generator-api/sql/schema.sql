-- Version: 1.0.0 — 2026-03-05
-- File: sql/schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ----------------------------
-- users (минимум для billing)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `balance` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- ai_images (минимум под INSERT из generation.php)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ai_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,

  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_ip` VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',

  `provider` VARCHAR(32) NOT NULL DEFAULT 'pollinations',
  `provider_model` VARCHAR(64) NOT NULL DEFAULT '',

  `prompt` TEXT NOT NULL,
  `negative_prompt` TEXT NULL,

  `seed` INT NULL,
  `width` INT NOT NULL DEFAULT 0,
  `height` INT NOT NULL DEFAULT 0,
  `aspect_ratio` VARCHAR(16) NOT NULL DEFAULT '1:1',

  `file_path` VARCHAR(255) NULL,
  `original_file_path` VARCHAR(255) NULL,
  `original_file_url` VARCHAR(255) NULL,
  `file_url` VARCHAR(255) NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,

  `is_private` ENUM('yes','no') NOT NULL DEFAULT 'no',
  `status` VARCHAR(32) NOT NULL DEFAULT 'generated',

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_images_uuid` (`uuid`),
  KEY `idx_ai_images_user_id` (`user_id`),
  KEY `idx_ai_images_status_created` (`status`, `created_at`),
  KEY `idx_ai_images_provider_model` (`provider`, `provider_model`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;