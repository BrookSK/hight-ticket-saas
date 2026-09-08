<?php

declare(strict_types=1);

namespace App\Libraries\Outreach\Providers;

use App\Libraries\Outreach\EmailProviderInterface;
use App\Libraries\Outreach\SendResult;
use App\Services\MailService;

/**
 * SMTP e-mail provider — real, reuses the existing MailService (which reads
 * SMTP settings from Configurações Gerais). Considered configured when the
 * MailService has an SMTP host + from address.
 */
final class SmtpEmailProvider implements EmailProviderInterface
{
    public function __construct(
        private readonly MailService $mail,
        private readonly bool $configured
    ) {
    }

    public function key(): string
    {
        return 'smtp';
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): SendResult
    {
        if (!$this->configured) {
            return SendResult::fail('smtp_not_configured');
        }

        $sent = $this->mail->send($toEmail, $toName, $subject, $htmlBody);

        return $sent ? SendResult::ok() : SendResult::fail('smtp_send_failed');
    }
}
