<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for outreach messages (the outbox and inbound log).
 *
 * Owner-scoped. Supports dedupe lookups, rate-limit/cooldown counting and
 * delivery-status transitions. No business logic here.
 */
final class OutreachMessageRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `created_by`, `lead_id`, `company_id`,
        `contact_id`, `campaign_id`, `template_id`, `report_id`, `channel`, `direction`,
        `to_address`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`, `delivered_at`,
        `read_at`, `provider`, `provider_msg_id`, `error`, `dedupe_key`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_messages`
                (`owner_type`, `owner_id`, `created_by`, `lead_id`, `company_id`, `contact_id`,
                 `campaign_id`, `template_id`, `report_id`, `channel`, `direction`, `to_address`,
                 `subject`, `body`, `status`, `scheduled_at`, `dedupe_key`, `provider_msg_id`,
                 `provider`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :lead_id, :company_id, :contact_id,
                 :campaign_id, :template_id, :report_id, :channel, :direction, :to_address,
                 :subject, :body, :status, :scheduled_at, :dedupe_key, :provider_msg_id,
                 :provider, NOW(), NOW())',
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
            'SELECT ' . self::COLUMNS . ' FROM `outreach_messages` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `outreach_messages` WHERE `id` = :id';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Existing message with the same dedupe key (prevents duplicate sends).
     */
    public function existsByDedupe(string $ownerType, int $ownerId, string $dedupeKey): bool
    {
        $row = $this->db->fetch(
            'SELECT `id` FROM `outreach_messages`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `dedupe_key` = :dk
               AND `status` NOT IN (\'failed\',\'cancelled\') LIMIT 1',
            ['ot' => $ownerType, 'oid' => $ownerId, 'dk' => $dedupeKey]
        );

        return $row !== null;
    }

    /**
     * Last outbound message to a recipient (for cooldown enforcement).
     *
     * @return array<string, mixed>|null
     */
    public function lastOutboundTo(string $ownerType, int $ownerId, string $toAddress): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `sent_at`, `created_at`, `status` FROM `outreach_messages`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `to_address` = :addr
               AND `direction` = \'outbound\' AND `status` NOT IN (\'failed\',\'cancelled\')
             ORDER BY `created_at` DESC LIMIT 1',
            ['ot' => $ownerType, 'oid' => $ownerId, 'addr' => $toAddress]
        );
    }

    /**
     * Count outbound messages sent within a rolling window (rate limiting).
     */
    public function countSentSince(string $ownerType, int $ownerId, int $seconds): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `outreach_messages`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `direction` = \'outbound\'
               AND `sent_at` IS NOT NULL AND `sent_at` >= (NOW() - INTERVAL :secs SECOND)',
            ['ot' => $ownerType, 'oid' => $ownerId, 'secs' => $seconds]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function markSending(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = \'sending\', `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function markSent(int $id, string $provider, ?string $providerMsgId): void
    {
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = \'sent\', `sent_at` = NOW(),
                `provider` = :p, `provider_msg_id` = :pid, `error` = NULL, `updated_at` = NOW()
             WHERE `id` = :id',
            ['p' => $provider, 'pid' => $providerMsgId, 'id' => $id]
        );
    }

    public function markFailed(int $id, string $error): void
    {
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = \'failed\', `error` = :e, `updated_at` = NOW() WHERE `id` = :id',
            ['e' => mb_substr($error, 0, 500), 'id' => $id]
        );
    }

    public function updateStatusByProviderId(string $provider, string $providerMsgId, string $status, string $timestampColumn): void
    {
        $allowed = ['delivered_at', 'read_at', 'sent_at'];
        $column = in_array($timestampColumn, $allowed, true) ? $timestampColumn : 'updated_at';
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = :s, `' . $column . '` = NOW(), `updated_at` = NOW()
             WHERE `provider` = :p AND `provider_msg_id` = :pid LIMIT 1',
            ['s' => $status, 'p' => $provider, 'pid' => $providerMsgId]
        );
    }

    public function approve(int $id, bool $scheduled): void
    {
        $status = $scheduled ? 'scheduled' : 'pending_approval';
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `outreach_messages` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_messages` ' . $where
            . ' ORDER BY `created_at` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): int
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `outreach_messages` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Thread messages for a lead (conversation timeline).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forLead(int $leadId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_messages`
             WHERE `lead_id` = :lid ORDER BY `created_at` ASC',
            ['lid' => $leadId]
        );
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, ['direction' => 'outbound']);
        $rows = $this->db->fetchAll(
            'SELECT `status`, COUNT(*) AS `total` FROM `outreach_messages` ' . $where . ' GROUP BY `status`',
            $params
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): array
    {
        $clauses = ['1 = 1'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        foreach (['status' => 'status', 'channel' => 'channel', 'direction' => 'direction'] as $key => $col) {
            if (!empty($filters[$key])) {
                $clauses[] = '`' . $col . '` = :' . $key;
                $params[$key] = (string) $filters[$key];
            }
        }
        if (!empty($filters['campaign_id'])) {
            $clauses[] = '`campaign_id` = :cid';
            $params['cid'] = (int) $filters['campaign_id'];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
