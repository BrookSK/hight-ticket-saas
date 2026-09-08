<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Database-backed job queue for outreach (no Redis required).
 *
 * Atomic claiming, retry with backoff, dead-job handling and stuck-job
 * recovery — all idempotent so a killed worker never corrupts state.
 */
final class OutreachJobRepository extends Repository
{
    private const COLUMNS = '`id`, `type`, `message_id`, `enrollment_id`, `status`, `attempts`,
        `max_attempts`, `available_at`, `locked_by`, `heartbeat_at`, `last_error`, `payload`,
        `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $type, ?int $messageId, ?int $enrollmentId, array $payload = [], int $maxAttempts = 3, ?string $availableAt = null): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_jobs`
                (`type`, `message_id`, `enrollment_id`, `status`, `max_attempts`, `available_at`, `payload`, `created_at`, `updated_at`)
             VALUES
                (:type, :mid, :eid, \'queued\', :maxa, COALESCE(:avail, NOW()), :payload, NOW(), NOW())',
            [
                'type'    => $type,
                'mid'     => $messageId,
                'eid'     => $enrollmentId,
                'maxa'    => $maxAttempts,
                'avail'   => $availableAt,
                'payload' => $payload !== [] ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atomically claim the next available queued job. Returns the row or null.
     *
     * @return array<string, mixed>|null
     */
    public function claimNext(string $workerId): ?array
    {
        $affected = $this->db->execute(
            'UPDATE `outreach_jobs`
                SET `status` = \'processing\', `locked_by` = :worker, `attempts` = `attempts` + 1,
                    `heartbeat_at` = NOW(), `updated_at` = NOW()
              WHERE `status` = \'queued\' AND `available_at` <= NOW()
              ORDER BY `id` ASC LIMIT 1',
            ['worker' => $workerId]
        );

        if ($affected === 0) {
            return null;
        }

        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_jobs`
             WHERE `locked_by` = :worker AND `status` = \'processing\'
             ORDER BY `heartbeat_at` DESC LIMIT 1',
            ['worker' => $workerId]
        );
    }

    public function heartbeat(int $id, string $workerId): void
    {
        $this->db->execute(
            'UPDATE `outreach_jobs` SET `heartbeat_at` = NOW() WHERE `id` = :id AND `locked_by` = :w',
            ['id' => $id, 'w' => $workerId]
        );
    }

    public function markDone(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_jobs` SET `status` = \'done\', `locked_by` = NULL, `updated_at` = NOW() WHERE `id` = :id',
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
                'UPDATE `outreach_jobs` SET `status` = \'dead\', `locked_by` = NULL, `last_error` = :e, `updated_at` = NOW() WHERE `id` = :id',
                ['e' => mb_substr($error, 0, 500), 'id' => $id]
            );

            return;
        }

        $delaySeconds = $attempts * 60;
        $this->db->execute(
            'UPDATE `outreach_jobs`
                SET `status` = \'queued\', `locked_by` = NULL, `last_error` = :e,
                    `available_at` = (NOW() + INTERVAL :delay SECOND), `updated_at` = NOW()
              WHERE `id` = :id',
            ['e' => mb_substr($error, 0, 500), 'delay' => $delaySeconds, 'id' => $id]
        );
    }

    public function requeueStuck(int $stuckTimeoutSeconds): int
    {
        return $this->db->execute(
            'UPDATE `outreach_jobs`
                SET `status` = \'queued\', `locked_by` = NULL, `updated_at` = NOW()
              WHERE `status` = \'processing\'
                AND (`heartbeat_at` IS NULL OR `heartbeat_at` < (NOW() - INTERVAL :secs SECOND))',
            ['secs' => $stuckTimeoutSeconds]
        );
    }
}
