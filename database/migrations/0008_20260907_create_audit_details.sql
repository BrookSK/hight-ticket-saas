-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create audit detail tables
-- Versão:    0008
-- Data:      2026-09-07
-- Descrição: Tabelas de detalhe da auditoria: problemas (audit_issues),
--            métricas (audit_metrics), links (audit_links), tecnologias
--            (audit_technologies), recursos (audit_resources), contatos
--            públicos (audit_contacts) e registros DNS (audit_dns).
-- Objetivo:  Armazenar de forma organizada e reutilizável os dados
--            coletados e processados de cada auditoria.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- audit_issues: problemas/recomendações detectados
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_issues` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`       BIGINT UNSIGNED NOT NULL,
    `rule_id`        VARCHAR(50) NOT NULL,      -- ex.: SEO-TITLE-001
    `category`       VARCHAR(30) NOT NULL,      -- seo|performance|security|accessibility|content|technology|best_practices
    `severity`       VARCHAR(20) NOT NULL,      -- critical|high|medium|low|info
    `confidence`     VARCHAR(10) NOT NULL DEFAULT 'high', -- high|medium|low
    `title`          VARCHAR(255) NOT NULL,
    `description`    TEXT DEFAULT NULL,
    `impact`         TEXT DEFAULT NULL,
    `recommendation` TEXT DEFAULT NULL,
    `evidence`       TEXT DEFAULT NULL,         -- JSON (url, header, element, metric...)
    `page_url`       VARCHAR(500) DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_issues_audit` (`audit_id`),
    KEY `idx_audit_issues_severity` (`severity`),
    KEY `idx_audit_issues_category` (`category`),
    CONSTRAINT `fk_audit_issues_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_metrics: métricas numéricas/textuais nomeadas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_metrics` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`    BIGINT UNSIGNED NOT NULL,
    `category`    VARCHAR(30) NOT NULL,
    `key`         VARCHAR(100) NOT NULL,
    `value`       VARCHAR(255) DEFAULT NULL,
    `unit`        VARCHAR(20) DEFAULT NULL,
    `available`   TINYINT(1) NOT NULL DEFAULT 1,  -- 0 quando "não disponível"
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_metrics_audit` (`audit_id`),
    KEY `idx_audit_metrics_key` (`key`),
    CONSTRAINT `fk_audit_metrics_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_links: links encontrados e seu status
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_links` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`    BIGINT UNSIGNED NOT NULL,
    `source_url`  VARCHAR(500) DEFAULT NULL,
    `target_url`  VARCHAR(500) NOT NULL,
    `type`        VARCHAR(20) NOT NULL DEFAULT 'internal', -- internal|external
    `status_code` SMALLINT UNSIGNED DEFAULT NULL,
    `state`       VARCHAR(20) NOT NULL DEFAULT 'ok',  -- ok|redirect|broken|timeout|blocked|unverified
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_links_audit` (`audit_id`),
    KEY `idx_audit_links_state` (`state`),
    CONSTRAINT `fk_audit_links_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_technologies: tecnologias detectadas (com confiança)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_technologies` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`   BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `category`   VARCHAR(50) DEFAULT NULL,   -- cms|framework|server|analytics|cdn|library|ecommerce|builder
    `version`    VARCHAR(50) DEFAULT NULL,
    `confidence` VARCHAR(10) NOT NULL DEFAULT 'medium',
    `evidence`   VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_tech_audit` (`audit_id`),
    CONSTRAINT `fk_audit_tech_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_resources: recursos (imagens, css, js, fonts) resumidos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_resources` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`    BIGINT UNSIGNED NOT NULL,
    `type`        VARCHAR(20) NOT NULL,  -- image|css|js|font|other
    `url`         VARCHAR(500) DEFAULT NULL,
    `size`        INT UNSIGNED DEFAULT NULL,
    `attributes`  TEXT DEFAULT NULL,     -- JSON (ex.: missing_alt, external, blocking)
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_resources_audit` (`audit_id`),
    KEY `idx_audit_resources_type` (`type`),
    CONSTRAINT `fk_audit_resources_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_contacts: informações de contato públicas encontradas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_contacts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`   BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(20) NOT NULL,  -- email|phone|whatsapp|social|address
    `value`      VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_contacts_audit` (`audit_id`),
    CONSTRAINT `fk_audit_contacts_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_dns: registros DNS públicos coletados (quando possível)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_dns` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_id`   BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(10) NOT NULL,  -- A|AAAA|CNAME|MX|TXT|NS
    `value`      VARCHAR(500) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_dns_audit` (`audit_id`),
    CONSTRAINT `fk_audit_dns_audit`
        FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0008.
-- =====================================================================
