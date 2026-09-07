-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Add phone to users
-- Versão:    0005
-- Data:      2026-09-07
-- Descrição: Adiciona a coluna de telefone aos usuários.
-- Objetivo:  Atender o cadastro de usuários da Fase 1 (campo telefone).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
--             Esta migration ALTERA a tabela users criada na 0001 por meio
--             de uma NOVA migration (a 0001 permanece intacta).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE `users`
    ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `email`;

-- =====================================================================
-- Fim da migration 0005.
-- =====================================================================
