-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed audit permissions and settings
-- Versão:    0009
-- Data:      2026-09-07
-- Descrição: Permissões do módulo de auditoria (audits.*) e configurações
--            de limites do scanner + integração futura de PageSpeed.
-- Objetivo:  Habilitar o módulo de auditoria com ACL e limites
--            configuráveis pelo Super Admin, sem valores hardcoded.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('audits.view',    'audits', 'Visualizar auditorias.'),
    ('audits.create',  'audits', 'Criar auditorias.'),
    ('audits.run',     'audits', 'Executar/reprocessar auditorias.'),
    ('audits.update',  'audits', 'Editar auditorias.'),
    ('audits.delete',  'audits', 'Excluir auditorias.'),
    ('audits.export',  'audits', 'Exportar auditorias (PDF).'),
    ('audits.compare', 'audits', 'Comparar auditorias.');

-- Concede as novas permissões ao Super Admin.
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`group` = 'audits'
WHERE r.`slug` = 'super-admin'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- Concede visualização/criação/execução/exportação a perfis operacionais.
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`key` IN ('audits.view', 'audits.create', 'audits.run', 'audits.export', 'dashboard.view')
WHERE r.`slug` IN ('admin', 'manager', 'sales', 'developer')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- Configurações do scanner (limites) e integração futura de métricas.
INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    ('audit_max_pages',            '50',   'audit', 0),
    ('audit_max_depth',            '2',    'audit', 0),
    ('audit_request_timeout',      '15',   'audit', 0),   -- segundos por requisição
    ('audit_request_delay_ms',     '300',  'audit', 0),   -- delay entre requisições
    ('audit_max_response_bytes',   '3145728', 'audit', 0), -- 3 MB por resposta
    ('audit_max_concurrent',       '2',    'audit', 0),   -- auditorias simultâneas
    ('audit_stuck_timeout',        '600',  'audit', 0),   -- seg. sem heartbeat = presa
    ('audit_cache_ttl',            '86400','audit', 0),   -- cache de recursos
    ('audit_user_agent',           'LRVWebAuditBot/1.0 (+https://lrvweb.local/bot)', 'audit', 0),
    ('audit_rate_per_user_daily',  '50',   'audit', 0),   -- anti-abuso por usuário/dia
    ('pagespeed_api_key',          NULL,   'audit', 1),   -- integração futura (CWV)
    ('pagespeed_enabled',          '0',    'audit', 0);

-- =====================================================================
-- Fim da migration 0009.
-- =====================================================================
