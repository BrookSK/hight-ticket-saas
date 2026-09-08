-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Seed CRM permissions
-- Versão:    0014
-- Data:      2026-09-07
-- Descrição: Permissões do módulo comercial (companies, contacts, leads,
--            activities, pipeline) e concessão aos perfis operacionais.
-- Objetivo:  Habilitar o CRM com ACL granular.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE: execução manual; migrations cumulativas; nunca editar depois.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('companies.view',   'companies',  'Visualizar empresas.'),
    ('companies.create', 'companies',  'Criar empresas.'),
    ('companies.update', 'companies',  'Editar empresas.'),
    ('companies.delete', 'companies',  'Excluir/arquivar empresas.'),
    ('contacts.view',    'contacts',   'Visualizar contatos.'),
    ('contacts.create',  'contacts',   'Criar contatos.'),
    ('contacts.update',  'contacts',   'Editar contatos.'),
    ('contacts.delete',  'contacts',   'Excluir contatos.'),
    ('leads.view',       'leads',      'Visualizar oportunidades.'),
    ('leads.create',     'leads',      'Criar oportunidades.'),
    ('leads.update',     'leads',      'Editar oportunidades.'),
    ('leads.delete',     'leads',      'Excluir/arquivar oportunidades.'),
    ('leads.assign',     'leads',      'Atribuir responsável.'),
    ('activities.view',   'activities', 'Visualizar atividades.'),
    ('activities.create', 'activities', 'Criar atividades/tarefas.'),
    ('activities.update', 'activities', 'Editar atividades/tarefas.'),
    ('activities.delete', 'activities', 'Excluir atividades/tarefas.'),
    ('pipeline.view',    'pipeline',   'Visualizar o pipeline.'),
    ('pipeline.update',  'pipeline',   'Mover cards no pipeline.');

-- Super Admin recebe todas as novas permissões.
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`group` IN ('companies', 'contacts', 'leads', 'activities', 'pipeline')
WHERE r.`slug` = 'super-admin'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- Perfis comerciais recebem o conjunto operacional (todas menos delete crítico).
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
JOIN `permissions` p ON p.`key` IN (
    'dashboard.view',
    'companies.view','companies.create','companies.update',
    'contacts.view','contacts.create','contacts.update',
    'leads.view','leads.create','leads.update','leads.assign',
    'activities.view','activities.create','activities.update',
    'pipeline.view','pipeline.update'
)
WHERE r.`slug` IN ('admin', 'manager', 'sales')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permission` rp
      WHERE rp.`role_id` = r.`id` AND rp.`permission_id` = p.`id`
  );

-- =====================================================================
-- Fim da migration 0014.
-- =====================================================================
