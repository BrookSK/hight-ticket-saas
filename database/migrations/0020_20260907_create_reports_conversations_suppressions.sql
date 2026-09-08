-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create reports, conversations, suppressions and tasks
-- Versão:    0020
-- Data:      2026-09-07
-- Descrição: Relatórios comerciais compartilháveis (reports) + logs de
--            acesso (report_access_logs); threads de conversa
--            (conversation_threads); supressões/opt-out
--            (outreach_suppressions); tarefas e lembretes por lead
--            (lead_tasks, lead_reminders).
-- Objetivo:  Link público de relatório com tracking, caixa de conversas,
--            controle de opt-out e agenda de follow-up.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `reports` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`   VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`     INT UNSIGNED NOT NULL,
    `created_by`   INT UNSIGNED DEFAULT NULL,
    `lead_id`      BIGINT UNSIGNED DEFAULT NULL,
    `company_id`   BIGINT UNSIGNED DEFAULT NULL,
    `audit_id`     BIGINT UNSIGNED DEFAULT NULL,
    `type`         VARCHAR(30) NOT NULL DEFAULT 'diagnosis', -- diagnosis|opportunity|performance|website|custom
    `title`        VARCHAR(255) DEFAULT NULL,
    `token`        VARCHAR(64) NOT NULL,          -- unguessable public token
    `cta_label`    VARCHAR(120) DEFAULT NULL,
    `cta_url`      VARCHAR(255) DEFAULT NULL,
    `hide_internal_score` TINYINT(1) NOT NULL DEFAULT 1,
    `snapshot`     MEDIUMTEXT DEFAULT NULL,        -- JSON snapshot for the public page
    `views`        INT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at`   DATETIME DEFAULT NULL,          -- null = never
    `revoked_at`   DATETIME DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reports_token` (`token`),
    KEY `idx_reports_owner` (`owner_type`, `owner_id`),
    KEY `idx_reports_lead` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `report_access_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_id`  BIGINT UNSIGNED NOT NULL,
    `ip`         VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ral_report` (`report_id`),
    CONSTRAINT `fk_ral_report`
        FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversation_threads` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`       VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`         INT UNSIGNED NOT NULL,
    `lead_id`          BIGINT UNSIGNED DEFAULT NULL,
    `contact_id`       BIGINT UNSIGNED DEFAULT NULL,
    `channel`          VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    `peer_address`     VARCHAR(190) DEFAULT NULL,
    `status`           VARCHAR(20) NOT NULL DEFAULT 'open', -- open|awaiting_seller|replied|closed
    `intent`           VARCHAR(20) DEFAULT NULL,  -- high|medium|low
    `last_message_at`  DATETIME DEFAULT NULL,
    `needs_human`      TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_thread_owner` (`owner_type`, `owner_id`),
    KEY `idx_thread_lead` (`lead_id`),
    KEY `idx_thread_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_suppressions` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `type`       VARCHAR(20) NOT NULL,  -- phone|email|contact|company
    `value`      VARCHAR(190) NOT NULL,
    `reason`     VARCHAR(120) DEFAULT NULL, -- opt_out|manual|bounce
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_supp` (`owner_type`, `owner_id`, `type`, `value`),
    KEY `idx_supp_lookup` (`type`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lead_tasks` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`  VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`    INT UNSIGNED NOT NULL,
    `lead_id`     BIGINT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `title`       VARCHAR(200) NOT NULL,
    `due_at`      DATETIME DEFAULT NULL,
    `status`      VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|done|cancelled
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ltask_owner` (`owner_type`, `owner_id`),
    KEY `idx_ltask_lead` (`lead_id`),
    KEY `idx_ltask_due` (`due_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0020.
-- =====================================================================
