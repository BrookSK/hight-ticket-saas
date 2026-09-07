-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create audits core
-- Versão:    0007
-- Data:      2026-09-07
-- Descrição: Estrutura principal do motor de auditoria: auditorias
--            (audits), páginas rastreadas (audit_pages), resultados
--            processados/scores (audit_results).
-- Objetivo:  Armazenar auditorias de sites com scores, status e o
--            contexto de acesso (owner) para isolamento de dados.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- audits: uma auditoria de um site
-- owner_type/owner_id abstraem o proprietário (hoje 'user'; futuramente
-- 'tenant') para isolamento sem reescrever o módulo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audits` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`       VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`         INT UNSIGNED NOT NULL,
    `created_by`       INT UNSIGNED DEFAULT NULL,
    `url`              VARCHAR(500) NOT NULL,
    `normalized_url`   VARCHAR(500) NOT NULL,
    `host`             VARCHAR(255) NOT NULL,
    `scope`            VARCHAR(20) NOT NULL DEFAULT 'homepage',  -- homepage | full
    `max_pages`        INT UNSIGNED NOT NULL DEFAULT 1,
    `max_depth`        INT UNSIGNED NOT NULL DEFAULT 0,
    `status`           VARCHAR(30) NOT NULL DEFAULT 'queued',    -- queued|processing|completed|partial|failed|cancelled
    `progress`         TINYINT UNSIGNED NOT NULL DEFAULT 0,      -- 0-100
    `current_step`     VARCHAR(100) DEFAULT NULL,
    `pages_crawled`    INT UNSIGNED NOT NULL DEFAULT 0,
    `error_message`    VARCHAR(500) DEFAULT NULL,
    -- Overall + category scores (0-100), null until computed.
    `score_overall`        TINYINT UNSIGNED DEFAULT NULL,
    `score_performance`    TINYINT UNSIGNED DEFAULT NULL,
    `score_seo`            TINYINT UNSIGNED DEFAULT NULL,
    `score_security`       TINYINT UNSIGNED DEFAULT NULL,
    `score_accessibility`  TINYINT UNSIGNED DEFAULT NULL,
    `score_technology`     TINYINT UNSIGNED DEFAULT NULL,
    `score_content`        TINYINT UNSIGNED DEFAULT NULL,
    `score_best_practices` TINYINT UNSIGNED DEFAULT NULL,
    -- Worker coordination for idempotency / stuck detection.
    `locked_by`        VARCHAR(64) DEFAULT NULL,
    `heartbeat_at`     DATETIME DEFAULT NULL,
    `started_at`       DATETIME DEFAULT NULL,
    `finished_at`      DATETIME DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audits_owner` (`owner_type`, `owner_id`),
    KEY `idx_audits_status` (`status`),
    KEY `idx_audits_host` (`host`),
    KEY `idx_audits_created_at` (`created_at`),
    KEY `idx_audits_deleted_at` (`deleted_at`),
    KEY `idx_audits_heartbeat` (`heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_pages: cada página rastreada durante a auditoria
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_pages` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`       BIGINT UNSIGNED NOT NULL,
    `url`            VARCHAR(500) NOT NULL,
    `depth`          INT UNSIGNED NOT NULL DEFAULT 0,
    `status_code`    SMALLINT UNSIGNED DEFAULT NULL,
    `content_type`   VARCHAR(100) DEFAULT NULL,
    `title`          VARCHAR(500) DEFAULT NULL,
    `response_time_ms` INT UNSIGNED DEFAULT NULL,
    `html_size`      INT UNSIGNED DEFAULT NULL,
    `redirected_to`  VARCHAR(500) DEFAULT NULL,
    `fetch_status`   VARCHAR(30) NOT NULL DEFAULT 'ok',  -- ok|error|timeout|blocked|skipped
    `error`          VARCHAR(255) DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_pages_audit` (`audit_id`),
    KEY `idx_audit_pages_status` (`status_code`),
    CONSTRAINT `fk_audit_pages_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_results: bloco de dados processados/coletados (JSON) por chave
-- Separa "resultado processado" dos dados brutos das páginas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_results` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`   BIGINT UNSIGNED NOT NULL,
    `key`        VARCHAR(100) NOT NULL,     -- ex.: summary, robots, sitemap, https
    `data`       MEDIUMTEXT DEFAULT NULL,   -- JSON
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_audit_results` (`audit_id`, `key`),
    CONSTRAINT `fk_audit_results_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0007.
-- =====================================================================
