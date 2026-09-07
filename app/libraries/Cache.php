<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Centralised file cache.
 *
 * All caching (configurations, permissions, menus, languages, templates, heavy
 * queries) goes through this single layer so clearing is centralised. A driver
 * abstraction can be added later (Redis, etc.) without touching callers.
 */
final class Cache
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? dirname(__DIR__, 2) . '/storage/cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $payload = unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($payload) || !array_key_exists('expires', $payload)) {
            return $default;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($file);

            return $default;
        }

        return $payload['value'] ?? $default;
    }

    /**
     * Store a value. TTL of 0 means "forever".
     */
    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        $payload = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value'   => $value,
        ];

        @file_put_contents($this->path($key), serialize($payload), LOCK_EX);
    }

    public function forget(string $key): void
    {
        $file = $this->path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Clear the entire cache (centralised flush).
     */
    public function flush(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }
}
