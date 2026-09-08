<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;
use Throwable;

/**
 * Data access for opt-out / suppression entries. Owner-scoped.
 *
 * A suppressed phone/email/contact/company must never be contacted again.
 */
final class SuppressionRepository extends Repository
{
    /**
     * Add a suppression (idempotent via UNIQUE key). Returns true if created.
     */
    public function add(string $ownerType, int $ownerId, string $type, string $value, ?string $reason): bool
    {
        try {
            $this->db->execute(
                'INSERT INTO `outreach_suppressions` (`owner_type`, `owner_id`, `type`, `value`, `reason`, `created_at`)
                 VALUES (:ot, :oid, :type, :value, :reason, NOW())',
                ['ot' => $ownerType, 'oid' => $ownerId, 'type' => $type, 'value' => $value, 'reason' => $reason]
            );
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function isSuppressed(string $ownerType, int $ownerId, string $type, string $value): bool
    {
        $row = $this->db->fetch(
            'SELECT `id` FROM `outreach_suppressions`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `type` = :type AND `value` = :value LIMIT 1',
            ['ot' => $ownerType, 'oid' => $ownerId, 'type' => $type, 'value' => $value]
        );

        return $row !== null;
    }

    public function remove(int $id, string $ownerType, int $ownerId): void
    {
        $this->db->execute(
            'DELETE FROM `outreach_suppressions` WHERE `id` = :id AND `owner_type` = :ot AND `owner_id` = :oid',
            ['id' => $id, 'ot' => $ownerType, 'oid' => $ownerId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        $clauses = ['1 = 1'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetchAll(
            'SELECT `id`, `owner_type`, `owner_id`, `type`, `value`, `reason`, `created_at`
             FROM `outreach_suppressions` WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `created_at` DESC',
            $params
        );
    }
}
