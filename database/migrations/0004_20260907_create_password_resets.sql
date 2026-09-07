-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create password resets
-- Versão:    0004
-- Data:      2026-09-07
-- Descrição: Tabela de tokens de recuperação de senha.
-- Objetivo:  Suportar o fluxo "esqueci minha senha" com tokens de uso
--            único e expiração.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(190) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at`    DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_password_resets_email` (`email`),
    KEY `idx_password_resets_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Fim da migration 0004.
-- =====================================================================
