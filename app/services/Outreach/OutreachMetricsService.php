<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\OutreachMessageRepository;
use App\Services\AccessContext;

/**
 * Aggregates outreach metrics for the seller dashboard.
 *
 * All figures are owner-scoped. Rates are derived from the message status
 * counts (sent/delivered/read/failed/received), so they reflect real activity
 * only — never inflated placeholders.
 */
final class OutreachMetricsService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $ctx = $this->context();
        $counts = $this->messages()->statusCounts($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());

        $sent = ($counts['sent'] ?? 0) + ($counts['delivered'] ?? 0) + ($counts['read'] ?? 0);
        $delivered = ($counts['delivered'] ?? 0) + ($counts['read'] ?? 0);
        $read = $counts['read'] ?? 0;
        $failed = $counts['failed'] ?? 0;
        $pending = ($counts['pending_approval'] ?? 0) + ($counts['scheduled'] ?? 0) + ($counts['draft'] ?? 0);

        return [
            'counts'         => $counts,
            'sent'           => $sent,
            'delivered'      => $delivered,
            'read'           => $read,
            'failed'         => $failed,
            'pending'        => $pending,
            'delivery_rate'  => $sent > 0 ? round($delivered / $sent * 100, 1) : 0.0,
            'read_rate'      => $delivered > 0 ? round($read / $delivered * 100, 1) : 0.0,
        ];
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
