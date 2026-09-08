<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for follow-up sequences and their steps. Owner-scoped.
 */
final class OutreachSequenceRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `name`, `channel`, `is_active`,
        `created_by`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_sequences`
                (`owner_type`, `owner_id`, `name`, `channel`, `is_active`, `created_by`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :name, :channel, :is_active, :created_by, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->db->execute(
            'UPDATE `outreach_sequences` SET `name` = :name, `channel` = :channel,
                `is_active` = :is_active, `updated_at` = NOW() WHERE `id` = :id',
            $data
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `outreach_sequences`
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
            'SELECT ' . self::COLUMNS . ' FROM `outreach_sequences`
             WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `name` ASC',
            $params
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_sequences` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    // ----------------------------------------------------------------- steps

    /**
     * @param array<string, mixed> $data
     */
    public function addStep(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_sequence_steps`
                (`sequence_id`, `step_order`, `delay_days`, `channel`, `template_id`, `stop_on_reply`, `created_at`)
             VALUES
                (:sequence_id, :step_order, :delay_days, :channel, :template_id, :stop_on_reply, NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function deleteSteps(int $sequenceId): void
    {
        $this->db->execute(
            'DELETE FROM `outreach_sequence_steps` WHERE `sequence_id` = :sid',
            ['sid' => $sequenceId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(int $sequenceId): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `sequence_id`, `step_order`, `delay_days`, `channel`, `template_id`, `stop_on_reply`
             FROM `outreach_sequence_steps` WHERE `sequence_id` = :sid ORDER BY `step_order` ASC',
            ['sid' => $sequenceId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function stepAt(int $sequenceId, int $order): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `sequence_id`, `step_order`, `delay_days`, `channel`, `template_id`, `stop_on_reply`
             FROM `outreach_sequence_steps` WHERE `sequence_id` = :sid AND `step_order` = :ord LIMIT 1',
            ['sid' => $sequenceId, 'ord' => $order]
        );
    }
}
