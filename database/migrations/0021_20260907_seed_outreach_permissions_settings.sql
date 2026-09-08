-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed outreach permissions and settings
-- Versão:    0021
-- Data:      2026-09-07
-- Descrição: Permissões do módulo de outreach (outreach.*) e configurações
--            de janela de envio, rate limit, cooldown e providers.
-- Objetivo:  Habilitar a automação comercial com ACL e limites/segurança
--            configuráveis pelo Super Admin (anti-spam por padrão).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('outreach.view',              'outreach', 'Visualizar área comercial/outreach.'),
    ('outreach.create',            'outreach', 'Preparar contatos e criar campanhas.'),
    ('outreach.update',            'outreach', 'Editar itens de outreach.'),
    ('outreach.delete',            'outreach', 'Excluir itens de outreach.'),
    ('outreach.send',              'outreach', 'Enviar mensagens.'),
    ('outreach.approve',           'outreach', 'Aprovar mensagens para envio.'),
    ('outreach.schedule',          'outreach', 'Agendar mensagens.'),
    ('outreach.cancel',            'outreach', 'Cancelar envios.'),
    ('outreach.export',            'outreach', 'Exportar métricas/histórico.'),
    ('outreach.manage_templates',  'outreach', 'Gerenciar templates.'),
    ('outreach.manage_sequences',  'outreach', 'Gerenciar sequências de follow-up.'),
    ('outreach.manage_providers',  'outreach', 'Gerenciar provedores (WhatsApp/e-mail/IA).');

INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`group` = 'outreach'
WHERE r.`slug` = 'super-admin'
  AND NOT EXISTS (SELECT 1 FROM `role_permission` rp WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`);

-- Perfis comerciais recebem o operacional (sem manage_providers).
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`key` IN (
    'outreach.view','outreach.create','outreach.update','outreach.send','outreach.approve',
    'outreach.schedule','outreach.cancel','outreach.export','outreach.manage_templates','outreach.manage_sequences'
)
WHERE r.`slug` IN ('admin', 'manager', 'sales')
  AND NOT EXISTS (SELECT 1 FROM `role_permission` rp WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`);

-- Configurações de outreach (janela de envio, limites, providers).
INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    ('outreach_send_window_start', '09:00', 'outreach', 0),
    ('outreach_send_window_end',   '18:00', 'outreach', 0),
    ('outreach_send_weekends',     '0',     'outreach', 0),
    ('outreach_timezone',          'America/Sao_Paulo', 'outreach', 0),
    ('outreach_rate_per_hour',     '30',    'outreach', 0),
    ('outreach_rate_per_day',      '150',   'outreach', 0),
    ('outreach_cooldown_hours',    '48',    'outreach', 0),  -- min interval between contacts to same recipient
    ('outreach_job_stuck_timeout', '600',   'outreach', 0),
    ('outreach_require_approval',  '1',     'outreach', 0),  -- human-in-the-loop by default
    ('outreach_report_default_expiry_days', '30', 'outreach', 0),
    -- WhatsApp provider (Evolution API) — inactive until configured.
    ('outreach_whatsapp_provider', 'evolution', 'outreach', 0),
    ('outreach_whatsapp_url',      NULL,    'outreach', 0),
    ('outreach_whatsapp_api_key',  NULL,    'outreach', 1),
    ('outreach_whatsapp_instance', NULL,    'outreach', 0),
    ('outreach_whatsapp_status',   '0',     'outreach', 0),
    -- AI provider — inactive until configured; system falls back to templates.
    ('outreach_ai_provider',       'openai','outreach', 0),
    ('outreach_ai_api_key',        NULL,    'outreach', 1),
    ('outreach_ai_model',          'gpt-4o-mini', 'outreach', 0),
    ('outreach_ai_status',         '0',     'outreach', 0);

-- =====================================================================
-- Fim da migration 0021.
-- =====================================================================
