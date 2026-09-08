-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create prospecting campaigns and discovery results
-- Versão:    0015
-- Data:      2026-09-07
-- Descrição: Campanhas de prospecção (prospecting_campaigns) e resultados
--            de descoberta intermediários (discovery_results).
-- Objetivo:  Fundação da máquina de oportunidades (Fase 4): definir buscas,
--            armazenar resultados brutos/normalizados antes de virarem leads.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- prospecting_campaigns
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prospecting_campaigns` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`           VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`             INT UNSIGNED NOT NULL,
    `created_by`           INT UNSIGNED DEFAULT NULL,
    `name`                 VARCHAR(200) NOT NULL,
    `provider`             VARCHAR(50) NOT NULL DEFAULT 'imported_list',
    `status`               VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|scheduled|processing|paused|completed|partial|failed|cancelled
    `segment`              VARCHAR(120) DEFAULT NULL,
    `keywords`             VARCHAR(255) DEFAULT NULL,
    `city`                 VARCHAR(120) DEFAULT NULL,
    `state`                VARCHAR(60) DEFAULT NULL,
    `country`              VARCHAR(60) DEFAULT NULL,
    `website_filter`       VARCHAR(20) NOT NULL DEFAULT 'any', -- any|with|without
    `technology_filter`    VARCHAR(30) DEFAULT NULL,           -- wordpress|elementor|woocommerce
    `min_opportunity_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `target_service`       VARCHAR(60) DEFAULT NULL,
    `max_results`          INT UNSIGNED NOT NULL DEFAULT 100,
    `max_audits`           INT UNSIGNED NOT NULL DEFAULT 50,
    `auto_audit`           TINYINT(1) NOT NULL DEFAULT 1,
    -- Provider input (e.g. imported list payload) kept as JSON/text.
    `input_payload`        MEDIUMTEXT DEFAULT NULL,
    `progress`             TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `current_step`         VARCHAR(100) DEFAULT NULL,
    -- Consumption counters (prepares billing/credits).
    `count_discovered`     INT UNSIGNED NOT NULL DEFAULT 0,
    `count_duplicated`     INT UNSIGNED NOT NULL DEFAULT 0,
    `count_enriched`       INT UNSIGNED NOT NULL DEFAULT 0,
    `count_audited`        INT UNSIGNED NOT NULL DEFAULT 0,
    `count_qualified`      INT UNSIGNED NOT NULL DEFAULT 0,
    `count_converted`      INT UNSIGNED NOT NULL DEFAULT 0,
    `count_requests`       INT UNSIGNED NOT NULL DEFAULT 0,
    `error_message`        VARCHAR(500) DEFAULT NULL,
    `started_at`           DATETIME DEFAULT NULL,
    `finished_at`          DATETIME DEFAULT NULL,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`           DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_campaigns_owner` (`owner_type`, `owner_id`),
    KEY `idx_campaigns_status` (`status`),
    KEY `idx_campaigns_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- discovery_results: resultado intermediário (antes de virar lead)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discovery_results` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`       VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`         INT UNSIGNED NOT NULL,
    `campaign_id`      BIGINT UNSIGNED NOT NULL,
    `provider`         VARCHAR(50) NOT NULL,
    `external_id`      VARCHAR(190) DEFAULT NULL,
    `dedupe_hash`      VARCHAR(64) DEFAULT NULL,  -- for idempotent discovery
    -- Raw (as received from provider).
    `raw_name`         VARCHAR(255) DEFAULT NULL,
    `raw_address`      VARCHAR(255) DEFAULT NULL,
    `raw_phone`        VARCHAR(60) DEFAULT NULL,
    `raw_website`      VARCHAR(255) DEFAULT NULL,
    `raw_category`     VARCHAR(120) DEFAULT NULL,
    `raw_data`         MEDIUMTEXT DEFAULT NULL,   -- JSON
    -- Normalized/enriched.
    `name`             VARCHAR(255) DEFAULT NULL,
    `domain`          VARCHAR(255) DEFAULT NULL,
    `website`          VARCHAR(255) DEFAULT NULL,
    `website_state`    VARCHAR(30) DEFAULT NULL,  -- none|unreachable|ok|blocked
    `phone`            VARCHAR(60) DEFAULT NULL,
    `whatsapp`         VARCHAR(60) DEFAULT NULL,
    `email`            VARCHAR(190) DEFAULT NULL,
    `city`             VARCHAR(120) DEFAULT NULL,
    `state`            VARCHAR(60) DEFAULT NULL,
    `category`         VARCHAR(120) DEFAULT NULL,
    `enrichment`       MEDIUMTEXT DEFAULT NULL,   -- JSON: socials, techs, contacts, sources
    -- Linking / outcome.
    `audit_id`         BIGINT UNSIGNED DEFAULT NULL,
    `company_id`       BIGINT UNSIGNED DEFAULT NULL,
    `lead_id`          BIGINT UNSIGNED DEFAULT NULL,
    `site_score`       TINYINT UNSIGNED DEFAULT NULL,
    `opportunity_score` TINYINT UNSIGNED DEFAULT NULL,
    `opportunity_confidence` VARCHAR(10) DEFAULT NULL, -- high|medium|low
    `recommended_service` VARCHAR(60) DEFAULT NULL,
    `priority`         VARCHAR(10) DEFAULT NULL,  -- high|medium|low
    `score_factors`    MEDIUMTEXT DEFAULT NULL,   -- JSON list of factors/evidence
    `dedupe_status`    VARCHAR(20) DEFAULT 'new', -- new|possible_duplicate|duplicate
    `status`           VARCHAR(20) NOT NULL DEFAULT 'new', -- new|processing|enriched|audited|qualified|discarded|converted|duplicate|failed|ignored
    `discard_reason`   VARCHAR(60) DEFAULT NULL,
    `error`            VARCHAR(500) DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dr_owner` (`owner_type`, `owner_id`),
    KEY `idx_dr_campaign` (`campaign_id`),
    KEY `idx_dr_status` (`status`),
    KEY `idx_dr_domain` (`domain`),
    KEY `idx_dr_opp_score` (`opportunity_score`),
    KEY `idx_dr_dedupe` (`dedupe_hash`),
    CONSTRAINT `fk_dr_campaign`
        FOREIGN KEY (`campaign_id`) REFERENCES `prospecting_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0015.
-- =====================================================================
