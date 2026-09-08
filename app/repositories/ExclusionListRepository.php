<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for the exclusion (do-not-prospect) list. Owner-scoped.
 */
final class ExclusionListRepository extends Repository
{
    public function add(string $ownerType, int $ownerId, string $type, string $value, ?string $reason, ?int $createdBy): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO `exclusion_list` (`owner_type`, `owner_id`, `type`, `value`, `reason`, `created_by`, `created_at`)
             VALUES (:ot, :oid, :type, :value, :reason, :cb, NOW())',
            ['ot' => $ownerType, 'oid' => $ownerId, 'type' => $type, 'value' => $value, 'reason' => $reason, 'cb' => $createdBy]
        );
    }

    public function isExcluded(string $ownerType, int $ownerId, string $type, string $value): bool
    {
        $row = $this->db->fetch(
            'SELECT `id` FROM `exclusion_list`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `type` = :type AND `value` = :value LIMIT 1',
            ['ot' => $ownerType, 'oid' => $ownerId, 'type' => $type, 'value' => $value]
        );

        return $row !== null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForOwner(string $ownerType, int $ownerId): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `type`, `value`, `reason`, `created_at` FROM `exclusion_list`
             WHERE `owner_type` = :ot AND `owner_id` = :oid ORDER BY `id` DESC',
            ['ot' => $ownerType, 'oid' => $ownerId]
        );
    }

    public function delete(int $id, string $ownerType, int $ownerId): void
    {
        $this->db->execute(
            'DELETE FROM `exclusion_list` WHERE `id` = :id AND `owner_type` = :ot AND `owner_id` = :oid',
            ['id' => $id, 'ot' => $ownerType, 'oid' => $ownerId]
        );
    }
}
