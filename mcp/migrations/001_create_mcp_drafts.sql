CREATE TABLE `mcp_drafts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `draft_id` CHAR(32) NOT NULL,
  `premise` VARCHAR(210) NOT NULL,
  `workflow` VARCHAR(16) NOT NULL,
  `save_payload` MEDIUMTEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'prepared',
  `comic_id` BIGINT UNSIGNED NULL,
  `permalink` CHAR(32) NULL,
  `error_message` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `saved_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_drafts_draft_id_unique` (`draft_id`),
  KEY `mcp_drafts_expires_at_index` (`expires_at`),
  KEY `mcp_drafts_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
