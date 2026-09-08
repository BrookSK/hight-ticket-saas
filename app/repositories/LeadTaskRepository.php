<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for lead tasks / follow-up reminders. Owner-scoped.
 */
final class LeadTaskRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `lead_id`, `user_id`, `title`,
        `due_at`, `status`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `lead_tasks`
                (`owner_type`, `owner_id`, `lead_id`, `user_id`, `title`, `due_at`, `status`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :lead_id, :user_id, :title, :due_at, :status, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function complete(int $id): void
    {
        $this->db->execute(
            'UPDATE `lead_tasks` SET `status` = \'done\', `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function cancel(int $id): void
    {
        $this->db->execute(
            'UPDATE `lead_tasks` SET `status` = \'cancelled\', `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forLead(int $leadId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `lead_tasks`
             WHERE `lead_id` = :lid ORDER BY `due_at` IS NULL, `due_at` ASC, `created_at` DESC',
            ['lid' => $leadId]
        );
    }

    /**
     * Pending tasks for a seller dashboard (owner-scoped, optionally by user).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingForContext(string $ownerType, ?int $ownerId, bool $canSeeAll, ?int $userId = null): array
    {
        $clauses = ['`status` = \'pending\''];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        if ($userId !== null) {
            $clauses[] = '`user_id` = :uid';
            $params['uid'] = $userId;
        }

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `lead_tasks`
             WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `due_at` IS NULL, `due_at` ASC LIMIT 100',
            $params
        );
    }
}
