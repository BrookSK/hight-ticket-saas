-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create outreach templates, sequences and campaigns
-- Versão:    0018
-- Data:      2026-09-07
-- Descrição: Templates de mensagem (outreach_templates), sequências de
--            follow-up (outreach_sequences + steps) e campanhas comerciais
--            (outreach_campaigns).
-- Objetivo:  Fundação da automação de abordagem/follow-up (Fase 5).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `outreach_templates` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `name`       VARCHAR(150) NOT NULL,
    `channel`    VARCHAR(20) NOT NULL DEFAULT 'whatsapp', -- whatsapp|email
    `kind`       VARCHAR(30) NOT NULL DEFAULT 'first_contact', -- first_contact|follow_up|diagnosis|proposal|meeting|reactivation
    `subject`    VARCHAR(255) DEFAULT NULL,               -- e-mail only
    `body`       TEXT NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tpl_owner` (`owner_type`, `owner_id`),
    KEY `idx_tpl_channel` (`channel`),
    KEY `idx_tpl_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_sequences` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `name`       VARCHAR(150) NOT NULL,
    `channel`    VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_seq_owner` (`owner_type`, `owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_sequence_steps` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sequence_id`   BIGINT UNSIGNED NOT NULL,
    `step_order`    INT NOT NULL DEFAULT 1,
    `delay_days`    INT UNSIGNED NOT NULL DEFAULT 0,
    `channel`       VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    `template_id`   BIGINT UNSIGNED DEFAULT NULL,
    `stop_on_reply` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_step_sequence` (`sequence_id`),
    CONSTRAINT `fk_step_sequence`
        FOREIGN KEY (`sequence_id`) REFERENCES `outreach_sequences` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_step_template`
        FOREIGN KEY (`template_id`) REFERENCES `outreach_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_campaigns` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`  VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`    INT UNSIGNED NOT NULL,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `name`        VARCHAR(150) NOT NULL,
    `objective`   VARCHAR(255) DEFAULT NULL,
    `channel`     VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    `template_id` BIGINT UNSIGNED DEFAULT NULL,
    `sequence_id` BIGINT UNSIGNED DEFAULT NULL,
    `status`      VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|running|paused|completed|cancelled
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ocamp_owner` (`owner_type`, `owner_id`),
    KEY `idx_ocamp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0018.
-- =====================================================================
