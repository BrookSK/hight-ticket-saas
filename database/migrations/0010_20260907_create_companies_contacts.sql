-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create companies and contacts
-- Versão:    0010
-- Data:      2026-09-07
-- Descrição: Entidades comerciais base: empresas (companies) e seus
--            contatos (contacts).
-- Objetivo:  Fundação do CRM (Fase 3): organizar empresas prospectadas e
--            seus contatos, isoladas por contexto de proprietário.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- companies
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`     VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`       INT UNSIGNED NOT NULL,
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `legal_name`     VARCHAR(200) DEFAULT NULL,
    `trade_name`     VARCHAR(200) NOT NULL,
    `cnpj`           VARCHAR(20) DEFAULT NULL,
    `website`        VARCHAR(255) DEFAULT NULL,
    `domain`         VARCHAR(255) DEFAULT NULL,  -- normalized canonical domain
    `phone`          VARCHAR(30) DEFAULT NULL,
    `whatsapp`       VARCHAR(30) DEFAULT NULL,
    `email`          VARCHAR(190) DEFAULT NULL,
    `address`        VARCHAR(255) DEFAULT NULL,
    `city`           VARCHAR(120) DEFAULT NULL,
    `state`          VARCHAR(60) DEFAULT NULL,
    `country`        VARCHAR(60) DEFAULT NULL,
    `zip_code`       VARCHAR(20) DEFAULT NULL,
    `segment`        VARCHAR(120) DEFAULT NULL,
    `description`    TEXT DEFAULT NULL,
    `instagram`      VARCHAR(255) DEFAULT NULL,
    `facebook`       VARCHAR(255) DEFAULT NULL,
    `linkedin`       VARCHAR(255) DEFAULT NULL,
    `youtube`        VARCHAR(255) DEFAULT NULL,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'active', -- active|inactive|archived
    `source`         VARCHAR(50) DEFAULT NULL,
    `last_activity_at` DATETIME DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_companies_owner` (`owner_type`, `owner_id`),
    KEY `idx_companies_domain` (`domain`),
    KEY `idx_companies_cnpj` (`cnpj`),
    KEY `idx_companies_status` (`status`),
    KEY `idx_companies_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contacts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`   VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`     INT UNSIGNED NOT NULL,
    `company_id`   BIGINT UNSIGNED NOT NULL,
    `first_name`   VARCHAR(120) NOT NULL,
    `last_name`    VARCHAR(120) DEFAULT NULL,
    `role_title`   VARCHAR(120) DEFAULT NULL,
    `email`        VARCHAR(190) DEFAULT NULL,
    `phone`        VARCHAR(30) DEFAULT NULL,
    `whatsapp`     VARCHAR(30) DEFAULT NULL,
    `linkedin`     VARCHAR(255) DEFAULT NULL,
    `notes`        TEXT DEFAULT NULL,
    `is_primary`   TINYINT(1) NOT NULL DEFAULT 0,
    `status`       VARCHAR(20) NOT NULL DEFAULT 'active',
    -- Origin/confidence of the data (prepares future automation/LGPD).
    `source`       VARCHAR(50) DEFAULT NULL,
    `data_confidence` VARCHAR(20) DEFAULT 'confirmed', -- confirmed|probable|unconfirmed
    `consent`      TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_contacts_owner` (`owner_type`, `owner_id`),
    KEY `idx_contacts_company` (`company_id`),
    KEY `idx_contacts_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_contacts_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0010.
-- =====================================================================
