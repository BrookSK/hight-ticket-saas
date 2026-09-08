<?php

declare(strict_types=1);

namespace App\Libraries\Prospecting;

/**
 * A single raw discovery result returned by a provider.
 *
 * Providers only return public, provider-supplied data. Normalization,
 * enrichment and auditing happen later in the pipeline — never in the provider.
 */
final class DiscoveryResultDto
{
    /**
     * @param array<string, mixed> $raw Additional raw provider payload.
     */
    public function __construct(
        public readonly ?string $externalId,
        public readonly ?string $name,
        public readonly ?string $website = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $category = null,
        public readonly array $raw = []
    ) {
    }

    /**
     * Stable hash used for idempotent discovery (avoids duplicate rows on
     * re-runs). Based on external id or normalized name+website.
     */
    public function dedupeHash(): string
    {
        $basis = $this->externalId !== null && $this->externalId !== ''
            ? 'ext:' . $this->externalId
            : 'nw:' . mb_strtolower(trim((string) $this->name)) . '|' . mb_strtolower(trim((string) $this->website));

        return hash('sha256', $basis);
    }
}
