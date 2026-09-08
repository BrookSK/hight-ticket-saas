<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;
use Throwable;

/**
 * Data access for outreach delivery/inbound events.
 *
 * Enforces webhook idempotency through the UNIQUE (provider, external_id) key:
 * recordUnique() returns false when the event was already processed.
 */
final class OutreachEventRepository extends Repository
{
    /**
     * Record an event, deduplicated by (provider, external_id).
     * Returns false if the event already existed (idempotent no-op).
     *
     * @param array<string, mixed> $data
     */
    public function recordUnique(string $provider, ?string $externalId, string $type, ?int $messageId, ?int $leadId, array $data = []): bool
    {
        try {
            $this->db->execute(
                'INSERT INTO `outreach_events`
                    (`message_id`, `lead_id`, `type`, `provider`, `external_id`, `data`, `created_at`)
                 VALUES
                    (:mid, :lid, :type, :provider, :ext, :data, NOW())',
                [
                    'mid'      => $messageId,
                    'lid'      => $leadId,
                    'type'     => $type,
                    'provider' => $provider,
                    'ext'      => $externalId,
                    'data'     => $data !== [] ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
                ]
            );
        } catch (Throwable) {
            // Duplicate key => already processed.
            return false;
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forMessage(int $messageId): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `type`, `provider`, `external_id`, `data`, `created_at`
             FROM `outreach_events` WHERE `message_id` = :mid ORDER BY `created_at` ASC',
            ['mid' => $messageId]
        );
    }
}
