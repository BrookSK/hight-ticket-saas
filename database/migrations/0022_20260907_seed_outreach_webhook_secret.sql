-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed outreach webhook secret setting
-- Versão:    0022
-- Data:      2026-09-07
-- Descrição: Adiciona a configuração de segredo do webhook de outreach,
--            usada para autenticar os webhooks de provedores (WhatsApp).
-- Objetivo:  Permitir validação por segredo compartilhado (comparação em
--            tempo constante) nos webhooks públicos, sem sessão.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    ('outreach_webhook_secret', NULL, 'outreach', 1);

-- =====================================================================
-- Fim da migration 0022.
-- =====================================================================
