<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for discovery results. Owner-scoped; no business logic.
 *
 * Discovery is idempotent via (campaign_id, dedupe_hash): re-running a campaign
 * will not create duplicate rows.
 */
final class DiscoveryResultRepository extends Repository
{
    private const COLUMNS = '`id`, `campaign_id`, `provider`, `external_id`, `raw_name`, `raw_website`,
        `raw_phone`, `name`, `domain`, `website`, `website_state`, `phone`, `whatsapp`, `email`,
        `city`, `state`, `category`, `audit_id`, `company_id`, `lead_id`, `site_score`,
        `opportunity_score`, `opportunity_confidence`, `recommended_service`, `priority`,
        `dedupe_status`, `status`, `created_at`';

    public function existsByHash(int $campaignId, string $hash): bool
    {
        $row = $this->db->fetch(
            'SELECT `id` FROM `discovery_results` WHERE `campaign_id` = :cid AND `dedupe_hash` = :h LIMIT 1',
            ['cid' => $campaignId, 'h' => $hash]
        );

        return $row !== null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `discovery_results`
                (`owner_type`, `owner_id`, `campaign_id`, `provider`, `external_id`, `dedupe_hash`,
                 `raw_name`, `raw_address`, `raw_phone`, `raw_website`, `raw_category`, `raw_data`,
                 `status`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :campaign_id, :provider, :external_id, :dedupe_hash,
                 :raw_name, :raw_address, :raw_phone, :raw_website, :raw_category, :raw_data,
                 :status, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function findRaw(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `discovery_results` WHERE `id` = :id LIMIT 1', ['id' => $id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT * FROM `discovery_results` WHERE `id` = :id';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Apply normalized/enriched fields.
     *
     * @param array<string, mixed> $data
     */
    public function applyEnrichment(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->db->execute(
            'UPDATE `discovery_results` SET
                `name` = :name, `domain` = :domain, `website` = :website, `website_state` = :website_state,
                `phone` = :phone, `whatsapp` = :whatsapp, `email` = :email, `city` = :city, `state` = :state,
                `category` = :category, `enrichment` = :enrichment, `dedupe_status` = :dedupe_status,
                `company_id` = :company_id, `status` = :status, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    public function setAudit(int $id, int $auditId): void
    {
        $this->db->execute(
            'UPDATE `discovery_results` SET `audit_id` = :aid, `status` = \'audited\', `updated_at` = NOW() WHERE `id` = :id',
            ['aid' => $auditId, 'id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyScore(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->db->execute(
            'UPDATE `discovery_results` SET
                `site_score` = :site_score, `opportunity_score` = :opportunity_score,
                `opportunity_confidence` = :opportunity_confidence, `recommended_service` = :recommended_service,
                `priority` = :priority, `score_factors` = :score_factors, `status` = :status, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    public function setStatus(int $id, string $status, ?string $reason = null): void
    {
        $this->db->execute(
            'UPDATE `discovery_results` SET `status` = :s, `discard_reason` = :r, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'r' => $reason, 'id' => $id]
        );
    }

    public function setError(int $id, string $error): void
    {
        $this->db->execute(
            'UPDATE `discovery_results` SET `status` = \'failed\', `error` = :e, `updated_at` = NOW() WHERE `id` = :id',
            ['e' => mb_substr($error, 0, 500), 'id' => $id]
        );
    }

    public function linkLead(int $id, int $leadId): void
    {
        $this->db->execute(
            'UPDATE `discovery_results` SET `lead_id` = :lid, `status` = \'converted\', `updated_at` = NOW() WHERE `id` = :id',
            ['lid' => $leadId, 'id' => $id]
        );
    }

    /**
     * Owner-scoped, filtered listing (for the review screen).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $order = ($filters['order_by'] ?? 'opportunity_score') === 'created_at' ? '`created_at`' : '`opportunity_score`';
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `discovery_results` ' . $where
            . ' ORDER BY ' . $order . ' DESC, `id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): int
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `discovery_results` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Funnel counts by status for a campaign.
     *
     * @return array<string, int>
     */
    public function funnelForCampaign(int $campaignId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT `status`, COUNT(*) AS `total` FROM `discovery_results` WHERE `campaign_id` = :cid GROUP BY `status`',
            ['cid' => $campaignId]
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
        $clauses = ['1=1'];
        $params = [];

        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        if (!empty($filters['campaign_id'])) {
            $clauses[] = '`campaign_id` = :cid';
            $params['cid'] = (int) $filters['campaign_id'];
        }
        if (!empty($filters['status'])) {
            $clauses[] = '`status` = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $clauses[] = '`priority` = :priority';
            $params['priority'] = (string) $filters['priority'];
        }
        if (isset($filters['website']) && $filters['website'] !== '') {
            if ($filters['website'] === 'without') {
                $clauses[] = '(`website_state` = \'none\' OR `website_state` IS NULL)';
            } elseif ($filters['website'] === 'with') {
                $clauses[] = '`website_state` = \'ok\'';
            }
        }
        if (!empty($filters['recommended_service'])) {
            $clauses[] = '`recommended_service` = :svc';
            $params['svc'] = (string) $filters['recommended_service'];
        }
        if (!empty($filters['min_opportunity'])) {
            $clauses[] = '`opportunity_score` >= :minopp';
            $params['minopp'] = (int) $filters['min_opportunity'];
        }
        if (!empty($filters['search'])) {
            $clauses[] = '(`name` LIKE :s OR `raw_name` LIKE :s OR `domain` LIKE :s OR `phone` LIKE :s OR `email` LIKE :s)';
            $params['s'] = '%' . (string) $filters['search'] . '%';
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
