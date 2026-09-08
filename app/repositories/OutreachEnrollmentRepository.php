<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for sequence enrollments (active follow-up state per lead).
 */
final class OutreachEnrollmentRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `sequence_id`, `lead_id`, `contact_id`,
        `current_step`, `status`, `stop_reason`, `next_run_at`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_enrollments`
                (`owner_type`, `owner_id`, `sequence_id`, `lead_id`, `contact_id`,
                 `current_step`, `status`, `next_run_at`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :sequence_id, :lead_id, :contact_id,
                 :current_step, :status, :next_run_at, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_enrollments` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * Active enrollment for a lead, if any.
     *
     * @return array<string, mixed>|null
     */
    public function activeForLead(int $leadId): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_enrollments`
             WHERE `lead_id` = :lid AND `status` = \'active\' ORDER BY `id` DESC LIMIT 1',
            ['lid' => $leadId]
        );
    }

    /**
     * Due active enrollments ready to advance (worker query).
     *
     * @return array<int, array<string, mixed>>
     */
    public function dueEnrollments(int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_enrollments`
             WHERE `status` = \'active\' AND `next_run_at` IS NOT NULL AND `next_run_at` <= NOW()
             ORDER BY `next_run_at` ASC LIMIT ' . (int) $limit,
            []
        );
    }

    public function advance(int $id, int $nextStep, ?string $nextRunAt): void
    {
        $this->db->execute(
            'UPDATE `outreach_enrollments` SET `current_step` = :step, `next_run_at` = :next,
                `updated_at` = NOW() WHERE `id` = :id',
            ['step' => $nextStep, 'next' => $nextRunAt, 'id' => $id]
        );
    }

    public function stop(int $id, string $reason): void
    {
        $this->db->execute(
            'UPDATE `outreach_enrollments` SET `status` = \'stopped\', `stop_reason` = :r,
                `next_run_at` = NULL, `updated_at` = NOW() WHERE `id` = :id',
            ['r' => $reason, 'id' => $id]
        );
    }

    public function complete(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_enrollments` SET `status` = \'completed\', `next_run_at` = NULL,
                `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    /**
     * Stop all active enrollments for a lead (e.g. on reply/won/opt-out).
     */
    public function stopForLead(int $leadId, string $reason): int
    {
        return $this->db->execute(
            'UPDATE `outreach_enrollments` SET `status` = \'stopped\', `stop_reason` = :r,
                `next_run_at` = NULL, `updated_at` = NOW()
             WHERE `lead_id` = :lid AND `status` = \'active\'',
            ['r' => $reason, 'lid' => $leadId]
        );
    }
}
