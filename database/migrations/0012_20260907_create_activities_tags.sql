-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create activities, tags and taggables
-- Versão:    0012
-- Data:      2026-09-07
-- Descrição: Atividades/tarefas (activities), tags (tags) e o vínculo
--            polimórfico de tags a entidades (taggables).
-- Objetivo:  Permitir timeline, tarefas e classificação por tags no CRM.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- activities: notas, ligações, mensagens, e-mails, reuniões, tarefas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activities` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`   VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`     INT UNSIGNED NOT NULL,
    `company_id`   BIGINT UNSIGNED DEFAULT NULL,
    `contact_id`   BIGINT UNSIGNED DEFAULT NULL,
    `lead_id`      BIGINT UNSIGNED DEFAULT NULL,
    `user_id`      INT UNSIGNED DEFAULT NULL,
    `type`         VARCHAR(20) NOT NULL DEFAULT 'note', -- note|call|message|email|meeting|task|system|other
    `title`        VARCHAR(200) DEFAULT NULL,
    `description`  TEXT DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `status`       VARCHAR(20) NOT NULL DEFAULT 'done', -- pending|in_progress|done|cancelled
    `is_system`    TINYINT(1) NOT NULL DEFAULT 0,       -- automatic timeline events
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_activities_owner` (`owner_type`, `owner_id`),
    KEY `idx_activities_lead` (`lead_id`),
    KEY `idx_activities_company` (`company_id`),
    KEY `idx_activities_type` (`type`),
    KEY `idx_activities_status` (`status`),
    KEY `idx_activities_scheduled` (`scheduled_at`),
    KEY `idx_activities_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_activities_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activities_lead`
        FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- tags
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `name`       VARCHAR(60) NOT NULL,
    `slug`       VARCHAR(60) NOT NULL,
    `color`      VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tags_owner_slug` (`owner_type`, `owner_id`, `slug`),
    KEY `idx_tags_owner` (`owner_type`, `owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- taggables: vínculo polimórfico (company|lead|contact) <-> tag
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `taggables` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tag_id`         BIGINT UNSIGNED NOT NULL,
    `taggable_type`  VARCHAR(20) NOT NULL,  -- company|lead|contact
    `taggable_id`    BIGINT UNSIGNED NOT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_taggables` (`tag_id`, `taggable_type`, `taggable_id`),
    KEY `idx_taggables_target` (`taggable_type`, `taggable_id`),
    CONSTRAINT `fk_taggables_tag`
        FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0012.
-- =====================================================================
