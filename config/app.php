<?php

declare(strict_types=1);

/**
 * Minimal bootstrap-level application configuration.
 *
 * IMPORTANT: This file only holds values required BEFORE the database/config
 * layer is available (bootstrapping constants). Everything else — system name,
 * logo, theme, SMTP, API keys, integrations, limits, etc. — must be stored in
 * the database and read through App\Services\ConfigService (Configurações
 * Gerais). Never add business/runtime configuration here.
 */

return [
    // Base paths.
    'root_path'    => dirname(__DIR__),

    // Default locale used until the database-backed setting is loaded.
    'default_locale'  => 'pt-BR',
    'fallback_locale' => 'pt-BR',

    // Error display: keep technical errors out of the UI. Details go to logs.
    // This is a bootstrap safety flag only; it is not a runtime feature toggle.
    'debug' => false,

    // Session hardening defaults (applied by the session bootstrap).
    'session' => [
        'name'      => 'lrvweb_session',
        'lifetime'  => 7200,
        'http_only' => true,
        'same_site' => 'Lax',
        // 'secure' is decided at runtime based on HTTPS detection.
    ],
];
