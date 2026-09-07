-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed phase 1 permissions and settings
-- Versão:    0006
-- Data:      2026-09-07
-- Descrição: Adiciona permissões granulares e configurações gerais
--            necessárias à Fase 1 (waitlist, plans, roles, settings por
--            categoria, whatsapp, seo, ia) e concede todas ao perfil
--            Super Admin. Adiciona settings iniciais de site/e-mail/SEO/
--            WhatsApp/IA/Analytics.
-- Objetivo:  Habilitar o painel administrativo e o site institucional da
--            Fase 1 sem qualquer valor hardcoded no código.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Novas permissões granulares.
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('dashboard.view',   'dashboard', 'Acessar o painel administrativo.'),
    ('waitlist.view',    'waitlist',  'Visualizar a lista de espera.'),
    ('waitlist.edit',    'waitlist',  'Editar interessados da lista de espera.'),
    ('waitlist.delete',  'waitlist',  'Excluir interessados da lista de espera.'),
    ('waitlist.export',  'waitlist',  'Exportar a lista de espera.'),
    ('plans.view',       'plans',     'Visualizar planos.'),
    ('plans.create',     'plans',     'Criar planos.'),
    ('plans.edit',       'plans',     'Editar planos.'),
    ('plans.delete',     'plans',     'Excluir planos.'),
    ('roles.view',       'roles',     'Visualizar perfis.'),
    ('roles.edit',       'roles',     'Editar perfis e permissões.'),
    ('settings.site',    'settings',  'Editar configurações do site.'),
    ('settings.seo',     'settings',  'Editar configurações de SEO.'),
    ('settings.whatsapp','settings',  'Editar configurações de WhatsApp.'),
    ('settings.ai',      'settings',  'Editar configurações de IA.');

-- Concede TODAS as permissões existentes ao perfil Super Admin.
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.`slug` = 'super-admin'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- Concede um conjunto de leitura/edição da waitlist ao perfil Marketing.
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`key` IN ('dashboard.view', 'waitlist.view', 'waitlist.edit', 'waitlist.export')
WHERE r.`slug` = 'marketing'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- ---------------------------------------------------------------------
-- Configurações gerais iniciais por categoria (editáveis pelo Super Admin).
-- ---------------------------------------------------------------------
INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    -- Site
    ('site_description', 'Plataforma operacional completa para Agências Digitais.', 'site', 0),
    ('site_logo',        NULL,               'site', 0),
    ('site_favicon',     NULL,               'site', 0),
    ('site_phone',       NULL,               'site', 0),
    ('site_email',       NULL,               'site', 0),
    ('site_whatsapp',    NULL,               'site', 0),
    ('site_address',     NULL,               'site', 0),
    ('social_instagram', NULL,               'site', 0),
    ('social_facebook',  NULL,               'site', 0),
    ('social_linkedin',  NULL,               'site', 0),
    ('social_youtube',   NULL,               'site', 0),
    -- E-mail (SMTP)
    ('smtp_host',        NULL,               'email', 0),
    ('smtp_port',        '587',              'email', 0),
    ('smtp_username',    NULL,               'email', 0),
    ('smtp_password',    NULL,               'email', 1),
    ('smtp_encryption',  'tls',              'email', 0),
    ('smtp_from_name',   'LRV Web',          'email', 0),
    ('smtp_from_email',  NULL,               'email', 0),
    -- SEO
    ('seo_title',        'LRV Web — Plataforma para Agências Digitais', 'seo', 0),
    ('seo_description',  'Centralize prospecção, auditoria, CRM, entrega e manutenção em uma só plataforma.', 'seo', 0),
    ('seo_og_image',     NULL,               'seo', 0),
    ('seo_google_verification', NULL,        'seo', 0),
    -- WhatsApp (integração futura — Evolution API)
    ('whatsapp_message',  'Olá! Tenho interesse na plataforma LRV Web.', 'whatsapp', 0),
    ('whatsapp_provider', 'evolution',       'whatsapp', 0),
    ('whatsapp_api_url',  NULL,              'whatsapp', 0),
    ('whatsapp_api_key',  NULL,              'whatsapp', 1),
    ('whatsapp_instance', NULL,              'whatsapp', 0),
    ('whatsapp_status',   '0',               'whatsapp', 0),
    -- IA (integração futura)
    ('ai_provider',      'openai',           'ai', 0),
    ('ai_api_key',       NULL,               'ai', 1),
    ('ai_default_model', 'gpt-4o-mini',      'ai', 0),
    ('ai_temperature',   '0.7',              'ai', 0),
    ('ai_max_tokens',    '2048',             'ai', 0),
    ('ai_status',        '0',                'ai', 0),
    -- Analytics
    ('analytics_ga_id',      NULL,           'integrations', 0),
    ('analytics_gtm_id',     NULL,           'integrations', 0),
    ('analytics_meta_pixel', NULL,           'integrations', 0),
    ('analytics_clarity_id', NULL,           'integrations', 0),
    -- Segurança
    ('security_login_max_attempts', '5',     'security', 0),
    ('security_login_decay_seconds', '900',  'security', 0);

-- =====================================================================
-- Fim da migration 0006.
-- =====================================================================
