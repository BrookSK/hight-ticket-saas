<?php

declare(strict_types=1);

namespace App\Libraries\Prospecting;

use App\Libraries\Prospecting\Providers\ImportedListProvider;

/**
 * Registry of discovery providers.
 *
 * New providers register here. Only the imported-list provider ships enabled in
 * this phase (no external credentials required). External providers (search
 * APIs, directories) can be registered and stay inactive until configured in
 * Configurações Gerais — the engine never calls an unconfigured provider.
 */
final class ProviderRegistry
{
    /** @var array<string, DiscoveryProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->register(new ImportedListProvider());
    }

    public function register(DiscoveryProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): ?DiscoveryProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * @return array<string, DiscoveryProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * @return array<string, DiscoveryProviderInterface>
     */
    public function configured(): array
    {
        return array_filter($this->providers, static fn (DiscoveryProviderInterface $p): bool => $p->isConfigured());
    }
}
