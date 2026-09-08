<?php

declare(strict_types=1);

namespace App\Libraries\Outreach;

/**
 * Contract for WhatsApp providers.
 *
 * The system depends on this interface, not on a specific implementation
 * (e.g. Evolution API). Providers must be explicitly configured/enabled by the
 * Super Admin; an unconfigured provider reports isConfigured() === false and is
 * never used to send.
 */
interface WhatsAppProviderInterface
{
    public function key(): string;

    public function isConfigured(): bool;

    /**
     * Send a text message to a phone number (E.164-ish digits).
     */
    public function sendText(string $toPhone, string $message): SendResult;
}
