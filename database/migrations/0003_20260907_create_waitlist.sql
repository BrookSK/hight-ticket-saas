-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create waitlist
-- Versão:    0003
-- Data:      2026-09-07
-- Descrição: Tabela de interessados da lista de espera (waitlist_leads) e
--            o histórico de atividades de cada interessado
--            (waitlist_lead_activities).
-- Objetivo:  Capturar e gerenciar leads da lista de espera, com origem,
--            UTMs, status, notas e timeline de atividades. Preparado para
--            multi-tenancy futuro via coluna tenant_id (nullable).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- waitlist_leads
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `waitlist_leads` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Prepared for future multi-tenancy; nullable for now.
    `tenant_id`       INT UNSIGNED DEFAULT NULL,
    `name`            VARCHAR(150) NOT NULL,
    `email`           VARCHAR(190) NOT NULL,
    `phone`           VARCHAR(30) NOT NULL,
    `company`         VARCHAR(150) DEFAULT NULL,
    `sites_quantity`  VARCHAR(30) DEFAULT NULL,
    `main_service`    VARCHAR(100) DEFAULT NULL,
    `message`         TEXT DEFAULT NULL,
    `status`          VARCHAR(30) NOT NULL DEFAULT 'new',
    `source`          VARCHAR(50) NOT NULL DEFAULT 'landing_page',
    `source_url`      VARCHAR(500) DEFAULT NULL,
    `utm_source`      VARCHAR(150) DEFAULT NULL,
    `utm_medium`      VARCHAR(150) DEFAULT NULL,
    `utm_campaign`    VARCHAR(150) DEFAULT NULL,
    `utm_content`     VARCHAR(150) DEFAULT NULL,
    `utm_term`        VARCHAR(150) DEFAULT NULL,
    `consent`         TINYINT(1) NOT NULL DEFAULT 0,
    `notes`           TEXT DEFAULT NULL,
    `last_contact_at` DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_waitlist_email` (`email`),
    KEY `idx_waitlist_status` (`status`),
    KEY `idx_waitlist_source` (`source`),
    KEY `idx_waitlist_created_at` (`created_at`),
    KEY `idx_waitlist_tenant` (`tenant_id`),
    KEY `idx_waitlist_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- waitlist_lead_activities: timeline de atividades por lead
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `waitlist_lead_activities` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lead_id`     BIGINT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `type`        VARCHAR(50) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_lead` (`lead_id`),
    KEY `idx_activity_created_at` (`created_at`),
    CONSTRAINT `fk_waitlist_activity_lead`
        FOREIGN KEY (`lead_id`) REFERENCES `waitlist_leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_waitlist_activity_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0003.
-- =====================================================================
