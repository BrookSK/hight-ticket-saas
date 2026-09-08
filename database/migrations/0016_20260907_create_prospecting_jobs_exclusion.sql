-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create prospecting jobs and exclusion list
-- Versão:    0016
-- Data:      2026-09-07
-- Descrição: Fila de jobs de prospecção (prospecting_jobs, fila em banco),
--            lista de exclusão (exclusion_list) e regras de oportunidade
--            configuráveis (opportunity_rules).
-- Objetivo:  Processamento assíncrono idempotente com retry/dead-job, e
--            score de oportunidade com regras/pesos configuráveis.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- prospecting_jobs: fila em banco (sem exigir Redis)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prospecting_jobs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`    BIGINT UNSIGNED NOT NULL,
    `result_id`      BIGINT UNSIGNED DEFAULT NULL,
    `type`           VARCHAR(30) NOT NULL, -- discovery|enrichment|audit|scoring
    `status`         VARCHAR(20) NOT NULL DEFAULT 'queued', -- queued|processing|done|failed|dead
    `attempts`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `available_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- for retry backoff
    `locked_by`      VARCHAR(64) DEFAULT NULL,
    `heartbeat_at`   DATETIME DEFAULT NULL,
    `last_error`     VARCHAR(500) DEFAULT NULL,
    `payload`        MEDIUMTEXT DEFAULT NULL, -- JSON
    `started_at`     DATETIME DEFAULT NULL,
    `finished_at`    DATETIME DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jobs_campaign` (`campaign_id`),
    KEY `idx_jobs_status_available` (`status`, `available_at`),
    KEY `idx_jobs_heartbeat` (`heartbeat_at`),
    CONSTRAINT `fk_jobs_campaign`
        FOREIGN KEY (`campaign_id`) REFERENCES `prospecting_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- exclusion_list: empresas/domínios que não devem ser prospectados
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exclusion_list` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `type`       VARCHAR(20) NOT NULL, -- domain|cnpj|email|phone|company
    `value`      VARCHAR(255) NOT NULL,
    `reason`     VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_exclusion` (`owner_type`, `owner_id`, `type`, `value`),
    KEY `idx_exclusion_lookup` (`type`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- opportunity_rules: regras/pesos configuráveis do Opportunity Score
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `opportunity_rules` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`         VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`           INT UNSIGNED DEFAULT NULL, -- null = global default
    `rule_key`           VARCHAR(50) NOT NULL,      -- e.g. OPP-NOSITE-001
    `name`               VARCHAR(150) NOT NULL,
    `description`        VARCHAR(255) DEFAULT NULL,
    `weight`             SMALLINT NOT NULL DEFAULT 0,
    `confidence`         VARCHAR(10) NOT NULL DEFAULT 'high',
    `recommended_service` VARCHAR(60) DEFAULT NULL,
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_opp_rule` (`owner_type`, `owner_id`, `rule_key`),
    KEY `idx_opp_rule_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regras padrão globais (owner_id NULL). Pesos ajustáveis pelo Super Admin.
INSERT INTO `opportunity_rules`
    (`owner_type`, `owner_id`, `rule_key`, `name`, `description`, `weight`, `confidence`, `recommended_service`, `is_active`)
VALUES
    ('user', NULL, 'OPP-NOSITE-001',    'Empresa sem website identificado', 'Nenhum website público foi encontrado.', 30, 'high', 'site_creation', 1),
    ('user', NULL, 'OPP-SITESCORE-001', 'Site com pontuação muito baixa',   'O site apresenta muitos problemas técnicos.', 25, 'high', 'optimization', 1),
    ('user', NULL, 'OPP-SITESCORE-002', 'Site com pontuação a melhorar',    'O site tem pontuação intermediária.', 12, 'medium', 'optimization', 1),
    ('user', NULL, 'OPP-SEO-001',       'SEO deficiente',                   'Problemas de SEO detectados na auditoria.', 10, 'medium', 'seo', 1),
    ('user', NULL, 'OPP-PERF-001',      'Performance ruim',                 'Problemas de performance detectados.', 10, 'medium', 'optimization', 1),
    ('user', NULL, 'OPP-SEC-001',       'Problemas de segurança',           'Cabeçalhos/HTTPS com problemas.', 8, 'medium', 'security', 1),
    ('user', NULL, 'OPP-WP-001',        'WordPress detectado',              'Site em WordPress (oportunidade de manutenção).', 8, 'medium', 'maintenance', 1),
    ('user', NULL, 'OPP-ELEMENTOR-001', 'Elementor detectado',              'Site em Elementor (oportunidade de otimização).', 6, 'medium', 'optimization', 1),
    ('user', NULL, 'OPP-CONTACT-001',   'Contato público encontrado',       'Há contato público (facilita abordagem).', 5, 'high', NULL, 1),
    ('user', NULL, 'OPP-WHATSAPP-001',  'WhatsApp encontrado',              'Número de WhatsApp público encontrado.', 5, 'high', NULL, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0016.
-- =====================================================================
