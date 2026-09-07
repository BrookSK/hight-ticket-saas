<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Cache;

/**
 * Simple cache-backed rate limiter.
 *
 * Protects login, password recovery, the waitlist form and public endpoints
 * against brute force / flooding. Keys are namespaced per action + identifier
 * (e.g. IP). No external service required.
 */
final class RateLimiter extends Service
{
    /**
     * Whether the given key has exceeded max attempts within the decay window.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Record a hit for the key, initialising the decay window on first hit.
     */
    public function hit(string $key, int $decaySeconds): int
    {
        $cacheKey = $this->cacheKey($key);
        $attempts = $this->attempts($key) + 1;
        $this->cache()->set($cacheKey, $attempts, $decaySeconds);

        return $attempts;
    }

    public function attempts(string $key): int
    {
        $value = $this->cache()->get($this->cacheKey($key));

        return is_int($value) ? $value : 0;
    }

    public function clear(string $key): void
    {
        $this->cache()->forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return 'ratelimit.' . $key;
    }

    private function cache(): Cache
    {
        /** @var Cache $cache */
        $cache = $this->container->get('cache');

        return $cache;
    }
}
