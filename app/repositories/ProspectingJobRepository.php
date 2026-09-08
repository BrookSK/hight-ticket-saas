<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Database-backed job queue for prospecting. No Redis required.
 *
 * Provides atomic claiming, retry with backoff, dead-job handling and
 * stuck-job recovery — all idempotent so a killed worker never corrupts state.
 */
final class ProspectingJobRepository extends Repository
{
    /**
     * Enqueue a job.
     *
     * @param array<string, mixed> $payload
     */
    public function enqueue(int $campaignId, string $type, ?int $resultId, array $payload = [], int $maxAttempts = 3): int
    {
        $this->db->execute(
            'INSERT INTO `prospecting_jobs`
                (`campaign_id`, `result_id`, `type`, `status`, `max_attempts`, `available_at`, `payload`, `created_at`, `updated_at`)
             VALUES
                (:cid, :rid, :type, \'queued\', :maxa, NOW(), :payload, NOW(), NOW())',
            [
                'cid'     => $campaignId,
                'rid'     => $resultId,
                'type'    => $type,
                'maxa'    => $maxAttempts,
                'payload' => $payload !== [] ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atomically claim the next available queued job (idempotent).
     * Returns the claimed job row or null.
     *
     * @return array<string, mixed>|null
     */
    public function claimNext(string $workerId): ?array
    {
        $affected = $this->db->execute(
            'UPDATE `prospecting_jobs`
                SET `status` = \'processing\', `locked_by` = :worker, `attempts` = `attempts` + 1,
                    `heartbeat_at` = NOW(), `started_at` = COALESCE(`started_at`, NOW()), `updated_at` = NOW()
              WHERE `status` = \'queued\' AND `available_at` <= NOW()
              ORDER BY `id` ASC
              LIMIT 1',
            ['worker' => $workerId]
        );

        if ($affected === 0) {
            return null;
        }

        return $this->db->fetch(
            'SELECT * FROM `prospecting_jobs` WHERE `locked_by` = :worker AND `status` = \'processing\'
             ORDER BY `heartbeat_at` DESC LIMIT 1',
            ['worker' => $workerId]
        );
    }

    public function heartbeat(int $id, string $workerId): void
    {
        $this->db->execute(
            'UPDATE `prospecting_jobs` SET `heartbeat_at` = NOW() WHERE `id` = :id AND `locked_by` = :w',
            ['id' => $id, 'w' => $workerId]
        );
    }

    public function markDone(int $id): void
    {
        $this->db->execute(
            'UPDATE `prospecting_jobs` SET `status` = \'done\', `locked_by` = NULL, `finished_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    /**
     * Fail a job: retry with backoff if attempts remain, else mark dead.
     */
    public function fail(int $id, string $error, int $attempts, int $maxAttempts): void
    {
        if ($attempts >= $maxAttempts) {
            $this->db->execute(
                'UPDATE `prospecting_jobs` SET `status` = \'dead\', `locked_by` = NULL, `last_error` = :e, `finished_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
                ['e' => mb_substr($error, 0, 500), 'id' => $id]
            );

            return;
        }

        // Exponential-ish backoff: attempts * 60 seconds.
        $delaySeconds = $attempts * 60;
        $this->db->execute(
            'UPDATE `prospecting_jobs`
                SET `status` = \'queued\', `locked_by` = NULL, `last_error` = :e,
                    `available_at` = (NOW() + INTERVAL :delay SECOND), `updated_at` = NOW()
              WHERE `id` = :id',
            ['e' => mb_substr($error, 0, 500), 'delay' => $delaySeconds, 'id' => $id]
        );
    }

    /**
     * Requeue jobs stuck in processing (dead worker recovery).
     */
    public function requeueStuck(int $stuckTimeoutSeconds): int
    {
        return $this->db->execute(
            'UPDATE `prospecting_jobs`
                SET `status` = \'queued\', `locked_by` = NULL, `updated_at` = NOW()
              WHERE `status` = \'processing\'
                AND (`heartbeat_at` IS NULL OR `heartbeat_at` < (NOW() - INTERVAL :secs SECOND))',
            ['secs' => $stuckTimeoutSeconds]
        );
    }

    /**
     * Cancel queued jobs for a campaign (on pause/cancel).
     */
    public function cancelQueuedForCampaign(int $campaignId): int
    {
        return $this->db->execute(
            'UPDATE `prospecting_jobs` SET `status` = \'dead\', `last_error` = \'cancelled\', `updated_at` = NOW()
             WHERE `campaign_id` = :cid AND `status` = \'queued\'',
            ['cid' => $campaignId]
        );
    }

    public function pendingCountForCampaign(int $campaignId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `prospecting_jobs`
             WHERE `campaign_id` = :cid AND `status` IN (\'queued\',\'processing\')',
            ['cid' => $campaignId]
        );

        return (int) ($row['total'] ?? 0);
    }
}
