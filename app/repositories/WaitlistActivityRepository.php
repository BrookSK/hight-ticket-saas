<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for waitlist lead activities (timeline). No business logic.
 */
final class WaitlistActivityRepository extends Repository
{
    public function create(int $leadId, ?int $userId, string $type, ?string $description): int
    {
        $this->db->execute(
            'INSERT INTO `waitlist_lead_activities`
                (`lead_id`, `user_id`, `type`, `description`, `created_at`)
             VALUES (:lead_id, :user_id, :type, :description, NOW())',
            [
                'lead_id'     => $leadId,
                'user_id'     => $userId,
                'type'        => $type,
                'description' => $description,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forLead(int $leadId): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `lead_id`, `user_id`, `type`, `description`, `created_at`
             FROM `waitlist_lead_activities`
             WHERE `lead_id` = :lead_id
             ORDER BY `created_at` DESC, `id` DESC',
            ['lead_id' => $leadId]
        );
    }
}
