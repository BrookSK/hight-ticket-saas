-- =====================================================================
-- Migration
-- ---------------------------------------------------------------------
-- Nome:      Create core tables
-- Versão:    0001
-- Data:      2026-09-07
-- Descrição: Estrutura inicial do sistema: perfis (roles), permissões
--            granulares (permissions), vínculo perfil-permissão
--            (role_permission), usuários (users), configurações gerais
--            (settings) e log de auditoria de ações (activity_logs).
-- Objetivo:  Fornecer a fundação de autenticação, ACL, configurações
--            armazenadas no banco e auditoria, conforme o Master
--            Specification.
-- Autor:     LRV Web
-- ---------------------------------------------------------------------
-- IMPORTANTE:
--   * Execução SEMPRE manual. Não há execução automática nem rota.
--   * Migrations são cumulativas. NUNCA editar esta migration depois de
--     aplicada: alterações futuras exigem uma NOVA migration.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- roles: perfis de usuário (configuráveis)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`),
    KEY `idx_roles_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- permissions: permissões granulares (ex.: users.view, settings.smtp)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(150) NOT NULL,
    `group`       VARCHAR(100) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_key` (`key`),
    KEY `idx_permissions_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- role_permission: vínculo N:N entre perfis e permissões
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permission` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permission_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users: usuários do sistema (com soft delete)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(150) NOT NULL,
    `email`          VARCHAR(190) NOT NULL,
    `password_hash`  VARCHAR(255) NOT NULL,
    `role_id`        INT UNSIGNED DEFAULT NULL,
    `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'active',
    `last_login_at`  DATETIME DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings: configurações gerais armazenadas no banco (chave/valor)
-- Única exceção fora do banco: a conexão (config/database.php).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`        VARCHAR(150) NOT NULL,
    `value`      TEXT DEFAULT NULL,
    `group`      VARCHAR(100) NOT NULL DEFAULT 'general',
    `is_secret`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`key`),
    KEY `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- activity_logs: auditoria de ações importantes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `object_type` VARCHAR(100) DEFAULT NULL,
    `object_id`   VARCHAR(100) DEFAULT NULL,
    `result`      VARCHAR(50) DEFAULT NULL,
    `page`        VARCHAR(255) DEFAULT NULL,
    `ip`          VARCHAR(45) DEFAULT NULL,
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `os`          VARCHAR(100) DEFAULT NULL,
    `browser`     VARCHAR(100) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_user` (`user_id`),
    KEY `idx_activity_action` (`action`),
    KEY `idx_activity_created_at` (`created_at`),
    CONSTRAINT `fk_activity_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Dados iniciais (seed) — perfis, permissões base e usuário Super Admin
-- =====================================================================

-- Perfis iniciais.
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
    ('Super Administrador', 'super-admin', 'Acesso absoluto ao sistema.', 1),
    ('Administrador',       'admin',       'Administra a agência.', 1),
    ('Gerente',             'manager',     'Gestão operacional.', 1),
    ('Comercial',           'sales',       'Prospecção e vendas.', 1),
    ('Marketing',           'marketing',   'Marketing e conteúdo.', 1),
    ('Financeiro',          'finance',     'Financeiro e cobrança.', 1),
    ('Suporte',             'support',     'Atendimento e suporte.', 1),
    ('Desenvolvedor',       'developer',   'Entrega e desenvolvimento.', 1),
    ('Cliente',             'client',      'Cliente da agência.', 1),
    ('Visualizador',        'viewer',      'Somente leitura.', 1);

-- Permissões base do núcleo.
INSERT INTO `permissions` (`key`, `group`, `description`) VALUES
    ('users.view',      'users',    'Visualizar usuários.'),
    ('users.create',    'users',    'Criar usuários.'),
    ('users.edit',      'users',    'Editar usuários.'),
    ('users.delete',    'users',    'Excluir usuários.'),
    ('settings.view',   'settings', 'Visualizar configurações.'),
    ('settings.edit',   'settings', 'Editar configurações.'),
    ('settings.smtp',   'settings', 'Editar configuração de e-mail (SMTP).'),
    ('settings.api',    'settings', 'Editar chaves de API.'),
    ('logs.view',       'logs',     'Visualizar logs de auditoria.');

-- Configurações gerais iniciais (valores editáveis pelo Super Admin).
INSERT INTO `settings` (`key`, `value`, `group`, `is_secret`) VALUES
    ('system_name',     'LRV Web', 'general', 0),
    ('default_locale',  'pt-BR',   'general', 0),
    ('theme',           'light',   'appearance', 0),
    ('maintenance_mode', '0',      'system', 0);

-- Usuário Super Admin inicial.
-- E-mail: admin@lrvweb.local
-- Senha:  ChangeMe!2026  (ALTERE IMEDIATAMENTE após o primeiro acesso)
-- O hash abaixo foi gerado com password_hash(..., PASSWORD_BCRYPT).
INSERT INTO `users` (`name`, `email`, `password_hash`, `role_id`, `is_super_admin`, `status`)
SELECT 'Super Admin', 'admin@lrvweb.local',
       '$2y$12$OJZiPdXvBgvQES61ZJQnBuJJ4SmxrwXgjIAVKzxPQyZ47S4DFD6y6', r.`id`, 1, 'active'
FROM `roles` r WHERE r.`slug` = 'super-admin' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Fim da migration 0001.
-- =====================================================================
