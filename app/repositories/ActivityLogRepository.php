<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for the action audit log (activity_logs). No business logic.
 */
final class ActivityLogRepository extends Repository
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `activity_logs`
                (`user_id`, `action`, `object_type`, `object_id`, `result`, `page`,
                 `ip`, `user_agent`, `os`, `browser`, `created_at`)
             VALUES
                (:user_id, :action, :object_type, :object_id, :result, :page,
                 :ip, :user_agent, :os, :browser, NOW())',
            $data
        );
    }

    /**
     * Recent log entries with the acting user name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));

        return $this->db->fetchAll(
            'SELECT l.`id`, l.`user_id`, u.`name` AS `user_name`, l.`action`, l.`object_type`,
                    l.`object_id`, l.`result`, l.`page`, l.`ip`, l.`browser`, l.`os`, l.`created_at`
             FROM `activity_logs` l
             LEFT JOIN `users` u ON u.`id` = l.`user_id`
             ORDER BY l.`id` DESC
             LIMIT ' . $limit
        );
    }
}
