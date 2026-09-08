-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed prospecting permissions and settings
-- Versão:    0017
-- Data:      2026-09-07
-- Descrição: Permissões do módulo de prospecção (prospecting.*) e
--            configurações de limites globais/rate limit.
-- Objetivo:  Habilitar a máquina de oportunidades com ACL e limites
--            configuráveis pelo Super Admin (sem hardcode).
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('prospecting.view',    'prospecting', 'Visualizar prospecção e campanhas.'),
    ('prospecting.create',  'prospecting', 'Criar campanhas.'),
    ('prospecting.update',  'prospecting', 'Editar campanhas.'),
    ('prospecting.delete',  'prospecting', 'Excluir/arquivar campanhas.'),
    ('prospecting.run',     'prospecting', 'Executar campanhas.'),
    ('prospecting.pause',   'prospecting', 'Pausar campanhas.'),
    ('prospecting.cancel',  'prospecting', 'Cancelar campanhas.'),
    ('prospecting.review',  'prospecting', 'Revisar oportunidades.'),
    ('prospecting.convert', 'prospecting', 'Converter oportunidades em leads.'),
    ('prospecting.export',  'prospecting', 'Exportar oportunidades.');

INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`group` = 'prospecting'
WHERE r.`slug` = 'super-admin'
  AND NOT EXISTS (SELECT 1 FROM `role_permission` rp WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`);

INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`key` IN (
    'prospecting.view','prospecting.create','prospecting.update','prospecting.run',
    'prospecting.pause','prospecting.cancel','prospecting.review','prospecting.convert','prospecting.export'
)
WHERE r.`slug` IN ('admin', 'manager', 'sales')
  AND NOT EXISTS (SELECT 1 FROM `role_permission` rp WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`);

-- Configurações de prospecção (limites globais + rate limit + retenção).
INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    ('prospecting_max_active_campaigns', '3',    'prospecting', 0),
    ('prospecting_max_results_per_campaign', '200', 'prospecting', 0),
    ('prospecting_daily_discovery_limit', '500', 'prospecting', 0),
    ('prospecting_request_delay_ms',     '500',  'prospecting', 0),
    ('prospecting_job_stuck_timeout',    '600',  'prospecting', 0),
    ('prospecting_audit_cache_hours',    '168',  'prospecting', 0),  -- reuse recent audits (7 days)
    ('prospecting_data_retention_days',  '0',    'prospecting', 0);  -- 0 = keep

-- =====================================================================
-- Fim da migration 0017.
-- =====================================================================
