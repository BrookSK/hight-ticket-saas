<?php

declare(strict_types=1);

namespace App\Libraries\Outreach;

/**
 * Contract for e-mail providers (SMTP, API-based, etc.).
 */
interface EmailProviderInterface
{
    public function key(): string;

    public function isConfigured(): bool;

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): SendResult;
}
