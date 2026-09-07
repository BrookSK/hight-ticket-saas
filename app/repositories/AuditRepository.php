<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for audits. No business logic.
 *
 * Ownership-scoped queries take ownerType/ownerId (and a canSeeAll flag for
 * Super Admin) so isolation is enforced at the data layer. Includes worker
 * coordination methods (claim/heartbeat/stuck detection) for idempotent
 * asynchronous processing.
 */
final class AuditRepository extends Repository
{
    private const LIST_COLUMNS = '`id`, `url`, `normalized_url`, `host`, `scope`, `status`,
        `progress`, `pages_crawled`, `score_overall`, `created_at`, `finished_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `audits`
                (`owner_type`, `owner_id`, `created_by`, `url`, `normalized_url`, `host`,
                 `scope`, `max_pages`, `max_depth`, `status`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :url, :normalized_url, :host,
                 :scope, :max_pages, :max_depth, :status, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find an audit enforcing ownership (unless canSeeAll).
     *
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT * FROM `audits` WHERE `id` = :id AND `deleted_at` IS NULL';
        $params = ['id' => $id];

        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :owner_type AND `owner_id` = :owner_id';
            $params['owner_type'] = $ownerType;
            $params['owner_id'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Owner-scoped listing (paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll, int $page, int $perPage): array
    {
        [$where, $params] = $this->ownerWhere($ownerType, $ownerId, $canSeeAll);
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::LIST_COLUMNS . ' FROM `audits` ' . $where
            . ' ORDER BY `created_at` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public function countForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): int
    {
        [$where, $params] = $this->ownerWhere($ownerType, $ownerId, $canSeeAll);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `audits` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function countCreatedTodayByUser(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `audits`
             WHERE `created_by` = :uid AND `created_at` >= CURDATE()',
            ['uid' => $userId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `audits` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    // ------------------------------------------------------ worker coordination

    /**
     * Atomically claim the next queued audit for a worker (idempotent).
     * Returns the claimed audit id or null if none available.
     */
    public function claimNextQueued(string $workerId): ?int
    {
        // Atomic claim: only one worker can flip a queued row to processing.
        $affected = $this->db->execute(
            'UPDATE `audits`
                SET `status` = :processing, `locked_by` = :worker,
                    `heartbeat_at` = NOW(), `started_at` = COALESCE(`started_at`, NOW()),
                    `updated_at` = NOW()
              WHERE `status` = :queued AND `deleted_at` IS NULL
              ORDER BY `created_at` ASC
              LIMIT 1',
            ['processing' => 'processing', 'worker' => $workerId, 'queued' => 'queued']
        );

        if ($affected === 0) {
            return null;
        }

        $row = $this->db->fetch(
            'SELECT `id` FROM `audits`
             WHERE `locked_by` = :worker AND `status` = :processing
             ORDER BY `heartbeat_at` DESC LIMIT 1',
            ['worker' => $workerId, 'processing' => 'processing']
        );

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * Requeue audits stuck in processing (no heartbeat within timeout).
     * Idempotent recovery so a killed worker never leaves audits locked forever.
     */
    public function requeueStuck(int $stuckTimeoutSeconds): int
    {
        return $this->db->execute(
            'UPDATE `audits`
                SET `status` = :queued, `locked_by` = NULL, `updated_at` = NOW()
              WHERE `status` = :processing
                AND `deleted_at` IS NULL
                AND (`heartbeat_at` IS NULL OR `heartbeat_at` < (NOW() - INTERVAL :secs SECOND))',
            ['queued' => 'queued', 'processing' => 'processing', 'secs' => $stuckTimeoutSeconds]
        );
    }

    public function heartbeat(int $id, string $workerId, int $progress, ?string $step): void
    {
        $this->db->execute(
            'UPDATE `audits`
                SET `heartbeat_at` = NOW(), `progress` = :progress, `current_step` = :step,
                    `updated_at` = NOW()
              WHERE `id` = :id AND `locked_by` = :worker',
            ['progress' => max(0, min(100, $progress)), 'step' => $step, 'id' => $id, 'worker' => $workerId]
        );
    }

    public function incrementPagesCrawled(int $id): void
    {
        $this->db->execute(
            'UPDATE `audits` SET `pages_crawled` = `pages_crawled` + 1, `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    /**
     * @param array<string, int|null> $scores
     */
    public function finish(int $id, string $status, array $scores, ?string $error = null): void
    {
        $this->db->execute(
            'UPDATE `audits` SET
                `status` = :status, `progress` = 100, `current_step` = NULL, `locked_by` = NULL,
                `score_overall` = :overall, `score_performance` = :performance, `score_seo` = :seo,
                `score_security` = :security, `score_accessibility` = :accessibility,
                `score_technology` = :technology, `score_content` = :content,
                `score_best_practices` = :best_practices,
                `error_message` = :error, `finished_at` = NOW(), `updated_at` = NOW()
             WHERE `id` = :id',
            [
                'status'         => $status,
                'overall'        => $scores['overall'] ?? null,
                'performance'    => $scores['performance'] ?? null,
                'seo'            => $scores['seo'] ?? null,
                'security'       => $scores['security'] ?? null,
                'accessibility'  => $scores['accessibility'] ?? null,
                'technology'     => $scores['technology'] ?? null,
                'content'        => $scores['content'] ?? null,
                'best_practices' => $scores['best_practices'] ?? null,
                'error'          => $error,
                'id'             => $id,
            ]
        );
    }

    public function markFailed(int $id, string $error): void
    {
        $this->db->execute(
            'UPDATE `audits` SET `status` = :failed, `locked_by` = NULL, `error_message` = :error,
                `finished_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['failed' => 'failed', 'error' => mb_substr($error, 0, 500), 'id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRaw(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `audits` WHERE `id` = :id LIMIT 1', ['id' => $id]);
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
            'WHERE `deleted_at` IS NULL AND `owner_type` = :owner_type AND `owner_id` = :owner_id',
            ['owner_type' => $ownerType, 'owner_id' => $ownerId],
        ];
    }
}
