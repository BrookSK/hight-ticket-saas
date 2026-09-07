-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create plans
-- Versão:    0002
-- Data:      2026-09-07
-- Descrição: Tabela de planos comerciais (Free, Pro, Agency, Enterprise)
--            com preços, destaque, ordem, CTA, recursos e limitações.
-- Objetivo:  Permitir que os planos e preços sejam gerenciados pelo
--            Super Admin sem alteração de código (nada hardcoded nas Views).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `plans` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `slug`          VARCHAR(100) NOT NULL,
    `description`   VARCHAR(255) DEFAULT NULL,
    `price_monthly` DECIMAL(10,2) DEFAULT NULL,
    `price_yearly`  DECIMAL(10,2) DEFAULT NULL,
    `currency`      VARCHAR(3) NOT NULL DEFAULT 'BRL',
    `cta_label`     VARCHAR(100) DEFAULT NULL,
    `cta_url`       VARCHAR(255) DEFAULT NULL,
    -- JSON arrays with feature/limitation strings (translatable keys or text).
    `features`      TEXT DEFAULT NULL,
    `limitations`   TEXT DEFAULT NULL,
    `is_featured`   TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order`    INT NOT NULL DEFAULT 0,
    `status`        VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_plans_slug` (`slug`),
    KEY `idx_plans_status` (`status`),
    KEY `idx_plans_sort` (`sort_order`),
    KEY `idx_plans_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura conceitual inicial. Preços ficam NULL para serem definidos
-- posteriormente pelo Super Admin no painel (sem alteração de código).
INSERT INTO `plans`
    (`name`, `slug`, `description`, `price_monthly`, `price_yearly`, `cta_label`, `cta_url`, `features`, `limitations`, `is_featured`, `sort_order`, `status`)
VALUES
    ('Free', 'free', 'Porta de entrada gratuita para começar.', NULL, NULL, 'Entrar para a lista de espera', '/lista-de-espera', NULL, NULL, 0, 1, 'active'),
    ('Pro', 'pro', 'Para profissionais que gerenciam vários clientes.', NULL, NULL, 'Entrar para a lista de espera', '/lista-de-espera', NULL, NULL, 1, 2, 'active'),
    ('Agency', 'agency', 'Para agências que precisam escalar a operação.', NULL, NULL, 'Entrar para a lista de espera', '/lista-de-espera', NULL, NULL, 0, 3, 'active'),
    ('Enterprise', 'enterprise', 'Plano personalizado para grandes operações.', NULL, NULL, 'Falar com a equipe', '/contato', NULL, NULL, 0, 4, 'active');

-- =====================================================================
-- Fim da migration 0002.
-- =====================================================================
