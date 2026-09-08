-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create outreach messages, events and jobs
-- Versão:    0019
-- Data:      2026-09-07
-- Descrição: Caixa de saída de mensagens (outreach_messages), eventos de
--            entrega/leitura (outreach_events), sequências ativas por lead
--            (outreach_enrollments) e fila de jobs (outreach_jobs).
-- Objetivo:  Enviar/agendar mensagens com aprovação, acompanhar entregas e
--            executar follow-ups de forma assíncrona e idempotente.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `outreach_messages` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`     VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`       INT UNSIGNED NOT NULL,
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `lead_id`        BIGINT UNSIGNED DEFAULT NULL,
    `company_id`     BIGINT UNSIGNED DEFAULT NULL,
    `contact_id`     BIGINT UNSIGNED DEFAULT NULL,
    `campaign_id`    BIGINT UNSIGNED DEFAULT NULL,
    `template_id`    BIGINT UNSIGNED DEFAULT NULL,
    `report_id`      BIGINT UNSIGNED DEFAULT NULL,
    `channel`        VARCHAR(20) NOT NULL DEFAULT 'whatsapp', -- whatsapp|email
    `direction`      VARCHAR(10) NOT NULL DEFAULT 'outbound', -- outbound|inbound
    `to_address`     VARCHAR(190) DEFAULT NULL,   -- phone or e-mail
    `subject`        VARCHAR(255) DEFAULT NULL,
    `body`           TEXT NOT NULL,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|pending_approval|scheduled|sending|sent|delivered|read|failed|cancelled|received
    `scheduled_at`   DATETIME DEFAULT NULL,
    `sent_at`        DATETIME DEFAULT NULL,
    `delivered_at`   DATETIME DEFAULT NULL,
    `read_at`        DATETIME DEFAULT NULL,
    `provider`       VARCHAR(50) DEFAULT NULL,
    `provider_msg_id` VARCHAR(190) DEFAULT NULL,
    `error`          VARCHAR(500) DEFAULT NULL,
    `dedupe_key`     VARCHAR(64) DEFAULT NULL,     -- prevents duplicate sends per campaign+contact
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_msg_owner` (`owner_type`, `owner_id`),
    KEY `idx_msg_lead` (`lead_id`),
    KEY `idx_msg_status` (`status`),
    KEY `idx_msg_channel` (`channel`),
    KEY `idx_msg_dedupe` (`dedupe_key`),
    KEY `idx_msg_provider_msg` (`provider_msg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_events` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id`  BIGINT UNSIGNED DEFAULT NULL,
    `lead_id`     BIGINT UNSIGNED DEFAULT NULL,
    `type`        VARCHAR(30) NOT NULL,  -- created|approved|sent|delivered|read|failed|received|opt_out
    `provider`    VARCHAR(50) DEFAULT NULL,
    `external_id` VARCHAR(190) DEFAULT NULL,  -- webhook event id for idempotency
    `data`        TEXT DEFAULT NULL,     -- JSON
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_event_external` (`provider`, `external_id`),
    KEY `idx_event_message` (`message_id`),
    KEY `idx_event_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Active sequence enrollments per lead (drives follow-ups).
CREATE TABLE IF NOT EXISTS `outreach_enrollments` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_type`    VARCHAR(20) NOT NULL DEFAULT 'user',
    `owner_id`      INT UNSIGNED NOT NULL,
    `sequence_id`   BIGINT UNSIGNED NOT NULL,
    `lead_id`       BIGINT UNSIGNED NOT NULL,
    `contact_id`    BIGINT UNSIGNED DEFAULT NULL,
    `current_step`  INT NOT NULL DEFAULT 0,
    `status`        VARCHAR(20) NOT NULL DEFAULT 'active', -- active|stopped|completed
    `stop_reason`   VARCHAR(50) DEFAULT NULL, -- replied|meeting|won|opt_out|manual
    `next_run_at`   DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_enroll_owner` (`owner_type`, `owner_id`),
    KEY `idx_enroll_lead` (`lead_id`),
    KEY `idx_enroll_status_next` (`status`, `next_run_at`),
    CONSTRAINT `fk_enroll_sequence`
        FOREIGN KEY (`sequence_id`) REFERENCES `outreach_sequences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outreach_jobs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`         VARCHAR(30) NOT NULL, -- send_message|follow_up|process_webhook
    `message_id`   BIGINT UNSIGNED DEFAULT NULL,
    `enrollment_id` BIGINT UNSIGNED DEFAULT NULL,
    `status`       VARCHAR(20) NOT NULL DEFAULT 'queued', -- queued|processing|done|failed|dead
    `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `available_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `locked_by`    VARCHAR(64) DEFAULT NULL,
    `heartbeat_at` DATETIME DEFAULT NULL,
    `last_error`   VARCHAR(500) DEFAULT NULL,
    `payload`      MEDIUMTEXT DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ojob_status_available` (`status`, `available_at`),
    KEY `idx_ojob_heartbeat` (`heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0019.
-- =====================================================================
