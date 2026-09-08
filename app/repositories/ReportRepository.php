<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for shareable commercial reports and their access logs.
 *
 * Public access is by unguessable token; the token lookup ignores owner scope
 * (it is the credential), but management queries are owner-scoped.
 */
final class ReportRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `created_by`, `lead_id`, `company_id`,
        `audit_id`, `type`, `title`, `token`, `cta_label`, `cta_url`, `hide_internal_score`,
        `snapshot`, `views`, `expires_at`, `revoked_at`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `reports`
                (`owner_type`, `owner_id`, `created_by`, `lead_id`, `company_id`, `audit_id`,
                 `type`, `title`, `token`, `cta_label`, `cta_url`, `hide_internal_score`,
                 `snapshot`, `expires_at`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :lead_id, :company_id, :audit_id,
                 :type, :title, :token, :cta_label, :cta_url, :hide_internal_score,
                 :snapshot, :expires_at, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Public lookup by token (the token is the access credential).
     *
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `reports` WHERE `token` = :t LIMIT 1',
            ['t' => $token]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `reports` WHERE `id` = :id';
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
        $clauses = ['1 = 1'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetchAll(
            'SELECT `id`, `lead_id`, `company_id`, `type`, `title`, `token`, `views`,
                    `expires_at`, `revoked_at`, `created_at`
             FROM `reports` WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `created_at` DESC',
            $params
        );
    }

    public function incrementViews(int $id): void
    {
        $this->db->execute(
            'UPDATE `reports` SET `views` = `views` + 1, `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function revoke(int $id): void
    {
        $this->db->execute(
            'UPDATE `reports` SET `revoked_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function tokenExists(string $token): bool
    {
        return $this->db->fetch('SELECT `id` FROM `reports` WHERE `token` = :t LIMIT 1', ['t' => $token]) !== null;
    }

    public function logAccess(int $reportId, ?string $ip, ?string $userAgent): void
    {
        $this->db->execute(
            'INSERT INTO `report_access_logs` (`report_id`, `ip`, `user_agent`, `created_at`)
             VALUES (:rid, :ip, :ua, NOW())',
            ['rid' => $reportId, 'ip' => $ip, 'ua' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accessLogs(int $reportId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `ip`, `user_agent`, `created_at` FROM `report_access_logs`
             WHERE `report_id` = :rid ORDER BY `created_at` DESC LIMIT ' . (int) $limit,
            ['rid' => $reportId]
        );
    }
}
