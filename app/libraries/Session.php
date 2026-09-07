<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Secure session wrapper.
 *
 * Configures hardened cookie parameters and provides typed accessors plus
 * flash messages used by the toast/notification system.
 */
final class Session
{
    private bool $started = false;

    /**
     * @param array<string, mixed> $config Session config from config/app.php.
     */
    public function start(array $config, bool $secure): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;

            return;
        }

        session_name((string) ($config['name'] ?? 'lrvweb_session'));

        session_set_cookie_params([
            'lifetime' => (int) ($config['lifetime'] ?? 7200),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => (bool) ($config['http_only'] ?? true),
            'samesite' => (string) ($config['same_site'] ?? 'Lax'),
        ]);

        session_start();
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        if ($this->started) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if ($this->started) {
            $_SESSION = [];
            session_destroy();
            $this->started = false;
        }
    }

    /**
     * Store a flash value available on the next request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve and remove a flash value.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }
}
