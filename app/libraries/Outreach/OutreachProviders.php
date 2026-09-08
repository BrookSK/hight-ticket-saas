<?php

declare(strict_types=1);

namespace App\Libraries\Outreach;

/**
 * Registry that exposes the active channel providers.
 *
 * Built once (in the Kernel) from ConfigService values, so the rest of the
 * system depends only on the interfaces here — never on Evolution, a specific
 * SMTP setup, or an AI vendor. Swapping a provider is a Kernel wiring change.
 */
final class OutreachProviders
{
    public function __construct(
        private readonly WhatsAppProviderInterface $whatsapp,
        private readonly EmailProviderInterface $email,
        private readonly AIProviderInterface $ai
    ) {
    }

    public function whatsapp(): WhatsAppProviderInterface
    {
        return $this->whatsapp;
    }

    public function email(): EmailProviderInterface
    {
        return $this->email;
    }

    public function ai(): AIProviderInterface
    {
        return $this->ai;
    }

    /**
     * Resolve the provider for a channel key.
     */
    public function forChannel(string $channel): WhatsAppProviderInterface|EmailProviderInterface
    {
        return $channel === 'email' ? $this->email : $this->whatsapp;
    }

    public function channelReady(string $channel): bool
    {
        return $this->forChannel($channel)->isConfigured();
    }
}
