<?php

declare(strict_types=1);

use App\Core\Container;
use App\Libraries\Csrf;
use App\Libraries\Translator;

/**
 * Global helper functions.
 *
 * Helpers only provide reusable utilities. They must not access the database,
 * contain business logic, or depend on controllers. The application container
 * is set once during bootstrap and used by translation/CSRF helpers.
 */

if (!function_exists('app')) {
    /**
     * Access the application container, or resolve a service from it.
     */
    function app(?string $id = null): mixed
    {
        static $container = null;

        // Allow the bootstrap to inject the container once.
        if ($id instanceof Container) {
            $container = $id;

            return $container;
        }

        if ($container === null) {
            /** @var Container|null $bootContainer */
            $bootContainer = $GLOBALS['__app_container'] ?? null;
            $container = $bootContainer;
        }

        if ($id === null) {
            return $container;
        }

        return $container?->get($id);
    }
}

if (!function_exists('__')) {
    /**
     * Translate a key. No user-facing text may be hard-coded.
     *
     * @param array<string, string|int|float> $replacements
     */
    function __(string $key, array $replacements = []): string
    {
        /** @var Translator|null $translator */
        $translator = app('translator');

        return $translator?->get($key, $replacements) ?? $key;
    }
}

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output (XSS protection).
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        /** @var Csrf|null $csrf */
        $csrf = app('csrf');

        return $csrf?->token() ?? '';
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render a hidden CSRF input for forms.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Render a hidden method-override input for forms (PUT/PATCH/DELETE).
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('config_value')) {
    /**
     * Read a database-backed setting through the single config layer.
     */
    function config_value(string $key, ?string $default = null): ?string
    {
        $config = app(App\Services\ConfigService::class);

        return $config instanceof App\Services\ConfigService
            ? $config->get($key, $default)
            : $default;
    }
}
