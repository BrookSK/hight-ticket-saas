<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for conversation threads. Owner-scoped.
 *
 * Individual messages live in `outreach_messages` (direction inbound/outbound);
 * this repository tracks the thread state (status, intent, needs_human).
 */
final class ConversationRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `lead_id`, `contact_id`, `channel`,
        `peer_address`, `status`, `intent`, `last_message_at`, `needs_human`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `conversation_threads`
                (`owner_type`, `owner_id`, `lead_id`, `contact_id`, `channel`, `peer_address`,
                 `status`, `intent`, `last_message_at`, `needs_human`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :lead_id, :contact_id, :channel, :peer_address,
                 :status, :intent, :last_message_at, :needs_human, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForLeadChannel(int $leadId, string $channel): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `conversation_threads`
             WHERE `lead_id` = :lid AND `channel` = :ch ORDER BY `id` DESC LIMIT 1',
            ['lid' => $leadId, 'ch' => $channel]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByPeer(string $ownerType, int $ownerId, string $channel, string $peer): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `conversation_threads`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `channel` = :ch AND `peer_address` = :peer
             ORDER BY `id` DESC LIMIT 1',
            ['ot' => $ownerType, 'oid' => $ownerId, 'ch' => $channel, 'peer' => $peer]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `conversation_threads` WHERE `id` = :id';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters = []): array
    {
        $clauses = ['1 = 1'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        if (!empty($filters['status'])) {
            $clauses[] = '`status` = :st';
            $params['st'] = (string) $filters['status'];
        }
        if (!empty($filters['needs_human'])) {
            $clauses[] = '`needs_human` = 1';
        }

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `conversation_threads`
             WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `last_message_at` DESC, `id` DESC LIMIT 200',
            $params
        );
    }

    public function touch(int $id, string $status, ?string $intent, bool $needsHuman): void
    {
        $this->db->execute(
            'UPDATE `conversation_threads` SET `status` = :s, `intent` = :intent,
                `needs_human` = :nh, `last_message_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'intent' => $intent, 'nh' => $needsHuman ? 1 : 0, 'id' => $id]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `conversation_threads` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }
}
