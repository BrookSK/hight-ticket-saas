<?php

declare(strict_types=1);

namespace App\Libraries\Outreach\Providers;

use App\Libraries\Outreach\AIProviderInterface;

/**
 * Null AI provider — always "not configured".
 *
 * Used when no real AI provider is set up. Callers detect isConfigured() ===
 * false and fall back to traditional templates, so the system never stops.
 */
final class NullAiProvider implements AIProviderInterface
{
    public function key(): string
    {
        return 'none';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function generate(string $prompt, array $context = []): ?string
    {
        return null;
    }
}
