<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\OutreachMessageRepository;
use App\Services\AccessContext;
use App\Services\ConfigService;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Centralises the anti-spam send policy.
 *
 * Before any message is actually dispatched, canSend() enforces, in order:
 *  1. opt-out / suppression,
 *  2. per-recipient cooldown,
 *  3. per-owner hourly and daily rate limits,
 *  4. the allowed send window (business hours / weekends).
 *
 * Every rejection carries a machine-readable reason so callers can surface a
 * clear message and (for the worker) reschedule instead of dropping.
 */
final class SendPolicyService extends Service
{
    /**
     * @return array{allowed:bool, reason?:string, retry_at?:string}
     */
    public function canSend(string $channel, string $toAddress): array
    {
        // 1. Opt-out / suppression is absolute.
        if ($this->suppression()->isSuppressed($channel, $toAddress)) {
            return ['allowed' => false, 'reason' => 'suppressed'];
        }

        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return ['allowed' => false, 'reason' => 'no_owner'];
        }

        // 2. Cooldown between contacts to the same recipient.
        $cooldownHours = (int) ($this->config()->get('outreach_cooldown_hours', '48') ?? 48);
        if ($cooldownHours > 0) {
            $last = $this->messages()->lastOutboundTo($ctx->ownerType(), $ownerId, $toAddress);
            if ($last !== null) {
                $ref = $last['sent_at'] ?? $last['created_at'] ?? null;
                if ($ref !== null) {
                    $elapsed = time() - (int) strtotime((string) $ref);
                    if ($elapsed < $cooldownHours * 3600) {
                        return ['allowed' => false, 'reason' => 'cooldown'];
                    }
                }
            }
        }

        // 3. Rate limits (rolling windows).
        $perHour = (int) ($this->config()->get('outreach_rate_per_hour', '30') ?? 30);
        $perDay = (int) ($this->config()->get('outreach_rate_per_day', '150') ?? 150);
        if ($perHour > 0 && $this->messages()->countSentSince($ctx->ownerType(), $ownerId, 3600) >= $perHour) {
            return ['allowed' => false, 'reason' => 'rate_hour'];
        }
        if ($perDay > 0 && $this->messages()->countSentSince($ctx->ownerType(), $ownerId, 86400) >= $perDay) {
            return ['allowed' => false, 'reason' => 'rate_day'];
        }

        // 4. Send window.
        $window = $this->windowCheck();
        if (!$window['open']) {
            return ['allowed' => false, 'reason' => 'outside_window', 'retry_at' => $window['next']];
        }

        return ['allowed' => true];
    }

    /**
     * Whether the current time is inside the configured send window and, if
     * not, when the next window opens (ISO datetime string).
     *
     * @return array{open:bool, next:string}
     */
    public function windowCheck(): array
    {
        $tz = new DateTimeZone((string) ($this->config()->get('outreach_timezone', 'America/Sao_Paulo') ?? 'America/Sao_Paulo'));
        $now = new DateTimeImmutable('now', $tz);

        $allowWeekends = (string) ($this->config()->get('outreach_send_weekends', '0') ?? '0') === '1';
        $start = (string) ($this->config()->get('outreach_send_window_start', '09:00') ?? '09:00');
        $end = (string) ($this->config()->get('outreach_send_window_end', '18:00') ?? '18:00');

        $isWeekend = (int) $now->format('N') >= 6;
        $current = $now->format('H:i');

        $open = ($allowWeekends || !$isWeekend) && $current >= $start && $current < $end;

        return ['open' => $open, 'next' => $this->nextWindowStart($now, $start, $allowWeekends)->format('Y-m-d H:i:s')];
    }

    private function nextWindowStart(DateTimeImmutable $from, string $start, bool $allowWeekends): DateTimeImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $start));
        $candidate = $from->setTime($h, $m);
        if ($candidate <= $from) {
            $candidate = $candidate->modify('+1 day');
        }
        // Skip weekends when not allowed.
        while (!$allowWeekends && (int) $candidate->format('N') >= 6) {
            $candidate = $candidate->modify('+1 day')->setTime($h, $m);
        }

        return $candidate;
    }

    public function requiresApproval(): bool
    {
        return (string) ($this->config()->get('outreach_require_approval', '1') ?? '1') === '1';
    }

    private function suppression(): SuppressionService
    {
        /** @var SuppressionService $s */
        $s = $this->container->get(SuppressionService::class);

        return $s;
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

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
