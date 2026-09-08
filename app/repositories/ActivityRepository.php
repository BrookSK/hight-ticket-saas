<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for CRM activities (notes, tasks, timeline events). Owner-scoped.
 */
final class ActivityRepository extends Repository
{
    private const COLUMNS = 'a.`id`, a.`company_id`, a.`contact_id`, a.`lead_id`, a.`user_id`,
        a.`type`, a.`title`, a.`description`, a.`scheduled_at`, a.`completed_at`,
        a.`status`, a.`is_system`, a.`created_at`, u.`name` AS `user_name`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `activities`
                (`owner_type`, `owner_id`, `company_id`, `contact_id`, `lead_id`, `user_id`,
                 `type`, `title`, `description`, `scheduled_at`, `completed_at`, `status`,
                 `is_system`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :company_id, :contact_id, :lead_id, :user_id,
                 :type, :title, :description, :scheduled_at, :completed_at, :status,
                 :is_system, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT * FROM `activities` WHERE `id` = :id AND `deleted_at` IS NULL';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Timeline for a lead (most recent first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forLead(int $leadId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `activities` a
             LEFT JOIN `users` u ON u.`id` = a.`user_id`
             WHERE a.`lead_id` = :lid AND a.`deleted_at` IS NULL
             ORDER BY a.`created_at` DESC, a.`id` DESC',
            ['lid' => $leadId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `activities` a
             LEFT JOIN `users` u ON u.`id` = a.`user_id`
             WHERE a.`company_id` = :cid AND a.`deleted_at` IS NULL
             ORDER BY a.`created_at` DESC, a.`id` DESC',
            ['cid' => $companyId]
        );
    }

    /**
     * Pending tasks (owner-scoped), ordered by due date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingTasks(string $ownerType, ?int $ownerId, bool $canSeeAll, int $limit = 20): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `activities` a
                LEFT JOIN `users` u ON u.`id` = a.`user_id`
                WHERE a.`deleted_at` IS NULL AND a.`type` = \'task\'
                  AND a.`status` IN (\'pending\',\'in_progress\')';
        $params = [];
        if (!$canSeeAll) {
            $sql .= ' AND a.`owner_type` = :ot AND a.`owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        $sql .= ' ORDER BY a.`scheduled_at` IS NULL, a.`scheduled_at` ASC LIMIT ' . max(1, min(100, $limit));

        return $this->db->fetchAll($sql, $params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $completed = $status === 'done' ? ', `completed_at` = NOW()' : '';
        $this->db->execute(
            'UPDATE `activities` SET `status` = :s' . $completed . ', `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `activities` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }
}
