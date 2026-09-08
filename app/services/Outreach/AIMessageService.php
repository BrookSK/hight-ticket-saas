<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Libraries\Outreach\OutreachProviders;
use Throwable;

/**
 * Assists message drafting.
 *
 * When an AI provider is configured, it may rephrase/refine a draft using ONLY
 * the supplied grounding data (it must not invent names, scores or facts). When
 * no AI is available or it fails, this service returns the template-rendered
 * text unchanged — the system never stops or blocks on AI.
 */
final class AIMessageService extends Service
{
    /**
     * Return a message body. Falls back to $templateBody when AI is unavailable.
     *
     * @param array<string, mixed> $context Grounding data (already collected).
     */
    public function draft(string $templateBody, array $context = []): string
    {
        $ai = $this->providers()->ai();
        if (!$ai->isConfigured()) {
            return $templateBody;
        }

        try {
            $prompt = 'Refine the following outreach message for clarity and a natural, '
                . 'professional tone. Do not invent any facts, names, numbers or contacts; '
                . 'only use the provided data. Keep it concise. Message: ' . $templateBody;
            $result = $ai->generate($prompt, $context);
        } catch (Throwable) {
            return $templateBody;
        }

        $result = is_string($result) ? trim($result) : '';

        return $result !== '' ? $result : $templateBody;
    }

    public function isAvailable(): bool
    {
        return $this->providers()->ai()->isConfigured();
    }

    private function providers(): OutreachProviders
    {
        /** @var OutreachProviders $p */
        $p = $this->container->get(OutreachProviders::class);

        return $p;
    }
}
