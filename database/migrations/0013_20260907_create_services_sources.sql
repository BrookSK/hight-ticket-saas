-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create services catalog and lead sources
-- Versão:    0013
-- Data:      2026-09-07
-- Descrição: Catálogo de serviços (services) e origens de lead
--            (lead_sources), ambos configuráveis (não hardcoded).
-- Objetivo:  Permitir classificar oportunidades por tipo de serviço e
--            origem sem valores fixos no código.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `services` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `name`       VARCHAR(120) NOT NULL,
    `slug`       VARCHAR(120) NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_services_owner` (`owner_type`, `owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lead_sources` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`   INT UNSIGNED NOT NULL,
    `name`       VARCHAR(120) NOT NULL,
    `slug`       VARCHAR(120) NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lead_sources_owner` (`owner_type`, `owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Fim da migration 0013.
-- =====================================================================
