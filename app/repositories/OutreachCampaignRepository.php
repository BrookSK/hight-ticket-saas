<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for outreach campaigns. Owner-scoped; no business logic.
 */
final class OutreachCampaignRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `created_by`, `name`, `objective`,
        `channel`, `template_id`, `sequence_id`, `status`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_campaigns`
                (`owner_type`, `owner_id`, `created_by`, `name`, `objective`, `channel`,
                 `template_id`, `sequence_id`, `status`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :name, :objective, :channel,
                 :template_id, :sequence_id, :status, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function changeStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `outreach_campaigns` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `outreach_campaigns`
                WHERE `id` = :id AND `deleted_at` IS NULL';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        $clauses = ['`deleted_at` IS NULL'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_campaigns`
             WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `created_at` DESC',
            $params
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_campaigns` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }
}
