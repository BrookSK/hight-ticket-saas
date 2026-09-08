<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for prospecting campaigns. Owner-scoped; no business logic.
 */
final class CampaignRepository extends Repository
{
    private const LIST_COLUMNS = '`id`, `name`, `provider`, `status`, `segment`, `city`, `state`,
        `website_filter`, `max_results`, `max_audits`, `progress`, `count_discovered`,
        `count_audited`, `count_converted`, `created_at`, `finished_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `prospecting_campaigns`
                (`owner_type`, `owner_id`, `created_by`, `name`, `provider`, `status`,
                 `segment`, `keywords`, `city`, `state`, `country`, `website_filter`,
                 `technology_filter`, `min_opportunity_score`, `target_service`,
                 `max_results`, `max_audits`, `auto_audit`, `input_payload`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :name, :provider, :status,
                 :segment, :keywords, :city, :state, :country, :website_filter,
                 :technology_filter, :min_opportunity_score, :target_service,
                 :max_results, :max_audits, :auto_audit, :input_payload, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT * FROM `prospecting_campaigns` WHERE `id` = :id AND `deleted_at` IS NULL';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    public function findRaw(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `prospecting_campaigns` WHERE `id` = :id LIMIT 1', ['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll, int $page, int $perPage): array
    {
        [$where, $params] = $this->ownerWhere($ownerType, $ownerId, $canSeeAll);
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::LIST_COLUMNS . ' FROM `prospecting_campaigns` ' . $where
            . ' ORDER BY `created_at` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public function countForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): int
    {
        [$where, $params] = $this->ownerWhere($ownerType, $ownerId, $canSeeAll);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `prospecting_campaigns` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function countActiveForOwner(string $ownerType, int $ownerId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `prospecting_campaigns`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `deleted_at` IS NULL
               AND `status` IN (\'scheduled\',\'processing\',\'paused\')',
            ['ot' => $ownerType, 'oid' => $ownerId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `prospecting_campaigns` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    public function markStarted(int $id): void
    {
        $this->db->execute(
            'UPDATE `prospecting_campaigns` SET `status` = \'processing\', `started_at` = COALESCE(`started_at`, NOW()), `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function markFinished(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `prospecting_campaigns` SET `status` = :s, `progress` = 100, `finished_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    public function setProgress(int $id, int $progress, ?string $step): void
    {
        $this->db->execute(
            'UPDATE `prospecting_campaigns` SET `progress` = :p, `current_step` = :step, `updated_at` = NOW() WHERE `id` = :id',
            ['p' => max(0, min(100, $progress)), 'step' => $step, 'id' => $id]
        );
    }

    /**
     * Increment a consumption counter (whitelisted column).
     */
    public function incrementCounter(int $id, string $counter, int $by = 1): void
    {
        $allowed = ['count_discovered', 'count_duplicated', 'count_enriched', 'count_audited', 'count_qualified', 'count_converted', 'count_requests'];
        if (!in_array($counter, $allowed, true)) {
            return;
        }
        $this->db->execute(
            'UPDATE `prospecting_campaigns` SET `' . $counter . '` = `' . $counter . '` + :by, `updated_at` = NOW() WHERE `id` = :id',
            ['by' => $by, 'id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `prospecting_campaigns` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    public function totalForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): int
    {
        return $this->countForContext($ownerType, $ownerId, $canSeeAll);
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function ownerWhere(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        if ($canSeeAll) {
            return ['WHERE `deleted_at` IS NULL', []];
        }

        return [
            'WHERE `deleted_at` IS NULL AND `owner_type` = :ot AND `owner_id` = :oid',
            ['ot' => $ownerType, 'oid' => $ownerId],
        ];
    }
}
