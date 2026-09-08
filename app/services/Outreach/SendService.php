<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Libraries\Logger;
use App\Libraries\Outreach\OutreachProviders;
use App\Repositories\OutreachEventRepository;
use App\Repositories\OutreachMessageRepository;

/**
 * Dispatches a single outbox message through the appropriate channel provider.
 *
 * Idempotent by design: only messages in a sendable state are processed, the
 * row is flipped to "sending" first, and the provider result maps to
 * sent/failed. Re-running a job for an already-sent message is a no-op.
 *
 * The send policy (opt-out/cooldown/rate/window) is checked here as the last
 * line of defence, even if it was already checked at enqueue time.
 */
final class SendService extends Service
{
    private const SENDABLE = ['scheduled', 'pending_approval', 'sending', 'draft'];

    /**
     * Attempt to dispatch a message by id.
     *
     * @return array{ok:bool, status:string, reason?:string}
     */
    public function dispatch(int $messageId): array
    {
        $message = $this->messages()->find($messageId);
        if ($message === null) {
            return ['ok' => false, 'status' => 'not_found'];
        }

        $status = (string) $message['status'];
        if ($status === 'sent' || $status === 'delivered' || $status === 'read') {
            // Already sent — idempotent no-op.
            return ['ok' => true, 'status' => $status];
        }
        if (!in_array($status, self::SENDABLE, true)) {
            return ['ok' => false, 'status' => $status, 'reason' => 'not_sendable'];
        }

        $channel = (string) $message['channel'];
        $to = (string) ($message['to_address'] ?? '');
        if ($to === '') {
            $this->messages()->markFailed($messageId, 'missing_recipient');

            return ['ok' => false, 'status' => 'failed', 'reason' => 'missing_recipient'];
        }

        // Provider must be configured/active.
        if (!$this->providers()->channelReady($channel)) {
            $this->messages()->markFailed($messageId, 'provider_not_configured');

            return ['ok' => false, 'status' => 'failed', 'reason' => 'provider_not_configured'];
        }

        // Last-line policy check.
        $policy = $this->policy()->canSend($channel, $to);
        if (!$policy['allowed']) {
            // Suppression is terminal; window/rate/cooldown are retryable.
            if (($policy['reason'] ?? '') === 'suppressed') {
                $this->messages()->markFailed($messageId, 'suppressed');

                return ['ok' => false, 'status' => 'failed', 'reason' => 'suppressed'];
            }

            return ['ok' => false, 'status' => 'deferred', 'reason' => $policy['reason'] ?? 'blocked'];
        }

        $this->messages()->markSending($messageId);

        $result = $channel === 'email'
            ? $this->providers()->email()->send($to, (string) ($message['to_address'] ?? ''), (string) ($message['subject'] ?? ''), (string) $message['body'])
            : $this->providers()->whatsapp()->sendText($to, (string) $message['body']);

        $providerKey = $channel === 'email' ? $this->providers()->email()->key() : $this->providers()->whatsapp()->key();

        if ($result->success) {
            $this->messages()->markSent($messageId, $providerKey, $result->providerMessageId);
            $this->events()->recordUnique(
                $providerKey,
                $result->providerMessageId,
                'sent',
                $messageId,
                isset($message['lead_id']) ? (int) $message['lead_id'] : null
            );

            return ['ok' => true, 'status' => 'sent'];
        }

        $this->messages()->markFailed($messageId, (string) ($result->error ?? 'send_failed'));
        $this->logger()->warning('Outreach message failed to send.', [
            'message_id' => $messageId,
            'channel'    => $channel,
            'error'      => $result->error,
        ]);

        return ['ok' => false, 'status' => 'failed', 'reason' => (string) ($result->error ?? 'send_failed')];
    }

    private function providers(): OutreachProviders
    {
        /** @var OutreachProviders $p */
        $p = $this->container->get(OutreachProviders::class);

        return $p;
    }

    private function policy(): SendPolicyService
    {
        /** @var SendPolicyService $s */
        $s = $this->container->get(SendPolicyService::class);

        return $s;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function events(): OutreachEventRepository
    {
        /** @var OutreachEventRepository $r */
        $r = $this->container->get(OutreachEventRepository::class);

        return $r;
    }

    private function logger(): Logger
    {
        /** @var Logger $l */
        $l = $this->container->get('logger');

        return $l;
    }
}
