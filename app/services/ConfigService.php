<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Cache;
use App\Repositories\SettingRepository;

/**
 * Single access layer for database-backed configuration.
 *
 * Per the Master Specification, every setting (system name, logo, theme,
 * default language, SMTP, API keys, integrations, limits, uploads, cache,
 * security, webhooks, URLs, etc.) lives in the database and is read ONLY
 * through this service. Never read settings directly elsewhere and never use
 * .env or environment variables.
 *
 * The only file-based configuration is the database connection.
 */
final class ConfigService extends Service
{
    private const CACHE_KEY = 'settings.all';
    private const CACHE_TTL = 3600;

    /** @var array<string, string|null>|null In-request memoisation. */
    private ?array $settings = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $settings = $this->load();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->load());
    }

    /**
     * Persist a setting and refresh caches.
     */
    public function set(string $key, ?string $value, string $group = 'general'): void
    {
        $this->repository()->set($key, $value, $group);
        $this->flush();
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->load();
    }

    /**
     * Clear the settings cache (centralised).
     */
    public function flush(): void
    {
        $this->settings = null;
        $this->cache()->forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    private function load(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        /** @var array<string, string|null>|null $cached */
        $cached = $this->cache()->get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $this->settings = $cached;
        }

        $settings = $this->repository()->all();
        $this->cache()->set(self::CACHE_KEY, $settings, self::CACHE_TTL);

        return $this->settings = $settings;
    }

    private function repository(): SettingRepository
    {
        /** @var SettingRepository $repository */
        $repository = $this->container->get(SettingRepository::class);

        return $repository;
    }

    private function cache(): Cache
    {
        /** @var Cache $cache */
        $cache = $this->container->get('cache');

        return $cache;
    }
}
