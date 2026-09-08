<?php

declare(strict_types=1);

namespace App\Libraries\Prospecting;

/**
 * Contract for discovery providers (sources of potential companies).
 *
 * A provider only returns public, authorized data. New sources (search APIs,
 * business directories, maps, open data) can be added by implementing this
 * interface, without changing the discovery engine.
 *
 * Providers must NOT scrape aggressively, bypass anti-bot/CAPTCHA, or fabricate
 * data. Credentials/config come from Configurações Gerais (never .env).
 */
interface DiscoveryProviderInterface
{
    /**
     * Machine key (e.g. "imported_list").
     */
    public function key(): string;

    /**
     * Human-readable name (translatable key resolved by the UI).
     */
    public function name(): string;

    /**
     * Whether the provider is ready to run (credentials/config present).
     */
    public function isConfigured(): bool;

    /**
     * Provider limits (max results, rate limit, delay), for planning/UI.
     *
     * @return array{max_results:int, delay_ms:int}
     */
    public function limits(): array;

    /**
     * Execute a search for the given campaign criteria and return raw results.
     *
     * @param array<string, mixed> $criteria Campaign criteria + provider input.
     * @return list<DiscoveryResultDto>
     */
    public function search(array $criteria): array;
}
