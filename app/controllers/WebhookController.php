<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\OutreachEventRepository;
use App\Repositories\OutreachMessageRepository;
use App\Services\ConfigService;

/**
 * Receives provider webhooks (WhatsApp delivery/read/inbound events).
 *
 * Public endpoint (no session). Security relies on a shared secret configured
 * by the Super Admin and compared in constant time. Processing is idempotent:
 * every event carries a provider event id and is recorded through the UNIQUE
 * (provider, external_id) constraint, so retried deliveries are no-ops.
 *
 * Delivery/read status updates key off provider_msg_id (no owner context
 * needed). This controller never trusts webhook content as instructions.
 */
final class WebhookController extends Controller
{
    /**
     * POST /webhooks/whatsapp?secret=... (secret also accepted via header).
     */
    public function whatsapp(Request $request): void
    {
        if (!$this->verifySecret($request)) {
            $this->response()->error('forbidden', [], 403);

            return;
        }

        $provider = (string) ($this->config()->get('outreach_whatsapp_provider', 'evolution') ?? 'evolution');
        $eventId = $this->extractEventId($request);
        $type = $this->normalizeType((string) ($request->input('event', $request->input('type', ''))));
        $providerMsgId = $this->extractMessageId($request);

        // Idempotency: record the event first; duplicates are ignored.
        $isNew = $this->events()->recordUnique($provider, $eventId, $type, null, null, $request->all());
        if (!$isNew) {
            // Already processed — acknowledge without reprocessing.
            $this->response()->success('duplicate');

            return;
        }

        // Map delivery/read status onto the outbox message by provider id.
        if ($providerMsgId !== null && $providerMsgId !== '') {
            $this->applyStatus($provider, $providerMsgId, $type);
        }

        // Acknowledge. Inbound message routing (into conversations) requires
        // owner resolution and is handled by an authenticated ingestion path;
        // here we only record the raw event for durability/audit.
        $this->response()->success('ok');
    }

    private function applyStatus(string $provider, string $providerMsgId, string $type): void
    {
        $map = [
            'delivered' => ['delivered', 'delivered_at'],
            'read'      => ['read', 'read_at'],
            'sent'      => ['sent', 'sent_at'],
        ];
        if (!isset($map[$type])) {
            return;
        }
        [$status, $column] = $map[$type];
        $this->messages()->updateStatusByProviderId($provider, $providerMsgId, $status, $column);
    }

    private function verifySecret(Request $request): bool
    {
        $expected = (string) ($this->config()->get('outreach_webhook_secret', '') ?? '');
        if ($expected === '') {
            // No secret configured: refuse rather than accept anonymous posts.
            return false;
        }
        $provided = (string) ($request->input('secret', $request->header('X-Webhook-Secret') ?? ''));

        return hash_equals($expected, $provided);
    }

    private function extractEventId(Request $request): ?string
    {
        $id = $request->input('event_id', $request->input('id', null));

        return $id !== null ? (string) $id : null;
    }

    private function extractMessageId(Request $request): ?string
    {
        $id = $request->input('message_id', $request->input('key_id', null));
        if ($id === null) {
            /** @var array<string, mixed>|null $key */
            $key = $request->input('key', null);
            if (is_array($key) && isset($key['id'])) {
                $id = $key['id'];
            }
        }

        return $id !== null ? (string) $id : null;
    }

    private function normalizeType(string $raw): string
    {
        $raw = strtolower(trim($raw));

        return match (true) {
            str_contains($raw, 'read')       => 'read',
            str_contains($raw, 'deliver')    => 'delivered',
            str_contains($raw, 'sent')       => 'sent',
            str_contains($raw, 'message')    => 'received',
            default                          => $raw !== '' ? $raw : 'event',
        };
    }

    private function events(): OutreachEventRepository
    {
        /** @var OutreachEventRepository $r */
        $r = $this->container->get(OutreachEventRepository::class);

        return $r;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $c */
        $c = $this->container->get(ConfigService::class);

        return $c;
    }
}
