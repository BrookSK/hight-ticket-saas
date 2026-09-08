<?php

declare(strict_types=1);

namespace App\Libraries\Outreach;

/**
 * Contract for AI providers used to assist message generation.
 *
 * The system never depends on a single vendor. When no AI provider is
 * configured/available, callers fall back to traditional templates — the system
 * must not stop. The AI must only interpret provided data; it never invents
 * facts (names, problems, scores, contacts).
 */
interface AIProviderInterface
{
    public function key(): string;

    public function isConfigured(): bool;

    /**
     * Generate text from a prompt with grounding context. Returns null on
     * failure so the caller can fall back to templates.
     *
     * @param array<string, mixed> $context Grounding data (already collected).
     */
    public function generate(string $prompt, array $context = []): ?string;
}
