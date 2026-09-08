-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create leads and lead-audit links
-- Versão:    0011
-- Data:      2026-09-07
-- Descrição: Oportunidades comerciais (leads), o vínculo N:N entre leads
--            e auditorias (lead_audits) e o histórico de mudanças de
--            status (lead_status_history).
-- Objetivo:  Permitir acompanhar oportunidades no pipeline, associadas a
--            empresas/contatos e às auditorias da Fase 2.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- leads
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leads` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`          VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`            INT UNSIGNED NOT NULL,
    `created_by`          INT UNSIGNED DEFAULT NULL,
    `company_id`          BIGINT UNSIGNED NOT NULL,
    `contact_id`          BIGINT UNSIGNED DEFAULT NULL,
    `responsible_user_id` INT UNSIGNED DEFAULT NULL,
    `title`               VARCHAR(200) DEFAULT NULL,
    `source`              VARCHAR(50) NOT NULL DEFAULT 'manual',
    `status`              VARCHAR(30) NOT NULL DEFAULT 'new',
    `temperature`         VARCHAR(10) NOT NULL DEFAULT 'cold', -- cold|warm|hot
    `service_type`        VARCHAR(60) DEFAULT NULL,
    `estimated_value`     DECIMAL(12,2) DEFAULT NULL,
    `currency`            VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `probability`         TINYINT UNSIGNED DEFAULT NULL, -- 0-100
    `expected_close_date` DATE DEFAULT NULL,
    `qualification`       VARCHAR(20) NOT NULL DEFAULT 'unqualified', -- unqualified|qualifying|qualified
    `next_step`           VARCHAR(255) DEFAULT NULL,
    `next_contact_at`     DATETIME DEFAULT NULL,
    -- Qualification detail (BANT-like), prepares proposal phase.
    `need`                TEXT DEFAULT NULL,
    `problem`             TEXT DEFAULT NULL,
    `budget`              VARCHAR(120) DEFAULT NULL,
    `authority`           VARCHAR(120) DEFAULT NULL,
    `urgency`             VARCHAR(60) DEFAULT NULL,
    `current_solution`    VARCHAR(200) DEFAULT NULL,
    `competitor`          VARCHAR(200) DEFAULT NULL,
    `objection`           TEXT DEFAULT NULL,
    `notes`               TEXT DEFAULT NULL,
    -- Win/loss outcome.
    `won_at`              DATETIME DEFAULT NULL,
    `lost_at`             DATETIME DEFAULT NULL,
    `final_value`         DECIMAL(12,2) DEFAULT NULL,
    `won_service`         VARCHAR(120) DEFAULT NULL,
    `loss_reason`         VARCHAR(120) DEFAULT NULL,
    `outcome_notes`       TEXT DEFAULT NULL,
    `last_activity_at`    DATETIME DEFAULT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_leads_owner` (`owner_type`, `owner_id`),
    KEY `idx_leads_company` (`company_id`),
    KEY `idx_leads_status` (`status`),
    KEY `idx_leads_temperature` (`temperature`),
    KEY `idx_leads_responsible` (`responsible_user_id`),
    KEY `idx_leads_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_leads_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_leads_contact`
        FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- lead_audits: vínculo N:N entre leads e auditorias (sem duplicar dados)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_audits` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lead_id`    BIGINT UNSIGNED NOT NULL,
    `audit_id`   BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_lead_audits` (`lead_id`, `audit_id`),
    KEY `idx_lead_audits_audit` (`audit_id`),
    CONSTRAINT `fk_lead_audits_lead`
        FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lead_audits_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- lead_status_history: histórico de mudanças de status do pipeline
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_status_history` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lead_id`      BIGINT UNSIGNED NOT NULL,
    `user_id`      INT UNSIGNED DEFAULT NULL,
    `from_status`  VARCHAR(30) DEFAULT NULL,
    `to_status`    VARCHAR(30) NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lead_status_history_lead` (`lead_id`),
    CONSTRAINT `fk_lead_status_history_lead`
        FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0011.
-- =====================================================================
