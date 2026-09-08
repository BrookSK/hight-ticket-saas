<?php

declare(strict_types=1);

namespace App\Libraries\Outreach\Providers;

use App\Libraries\Http\HttpClient;
use App\Libraries\Outreach\SendResult;
use App\Libraries\Outreach\WhatsAppProviderInterface;
use Throwable;

/**
 * Evolution API adapter for WhatsApp.
 *
 * Isolated behind WhatsAppProviderInterface so the rest of the system does not
 * depend on Evolution. Only active when URL + API key + instance are configured
 * in Configurações Gerais and the WhatsApp integration is enabled. Uses the
 * SSRF-protected HttpClient. Credentials are never logged.
 */
final class EvolutionWhatsAppProvider implements WhatsAppProviderInterface
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly ?string $baseUrl,
        private readonly ?string $apiKey,
        private readonly ?string $instance,
        private readonly bool $enabled
    ) {
    }

    public function key(): string
    {
        return 'evolution';
    }

    public function isConfigured(): bool
    {
        return $this->enabled
            && $this->baseUrl !== null && $this->baseUrl !== ''
            && $this->apiKey !== null && $this->apiKey !== ''
            && $this->instance !== null && $this->instance !== '';
    }

    public function sendText(string $toPhone, string $message): SendResult
    {
        if (!$this->isConfigured()) {
            return SendResult::fail('whatsapp_not_configured');
        }

        $digits = preg_replace('/\D+/', '', $toPhone) ?? '';
        if ($digits === '') {
            return SendResult::fail('invalid_phone');
        }

        // Evolution API: POST {base}/message/sendText/{instance}
        $url = rtrim((string) $this->baseUrl, '/') . '/message/sendText/' . rawurlencode((string) $this->instance);

        try {
            $response = $this->http->postJson($url, [
                'number' => $digits,
                'text'   => $message,
            ], ['apikey' => (string) $this->apiKey]);
        } catch (Throwable) {
            return SendResult::fail('whatsapp_request_failed');
        }

        if ($response->ok()) {
            $providerId = null;
            $decoded = json_decode($response->body, true);
            if (is_array($decoded)) {
                $providerId = $decoded['key']['id'] ?? ($decoded['id'] ?? null);
            }

            return SendResult::ok($providerId !== null ? (string) $providerId : null);
        }

        return SendResult::fail('whatsapp_http_' . $response->statusCode);
    }
}
