<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for outreach message templates. Owner-scoped; no business logic.
 */
final class OutreachTemplateRepository extends Repository
{
    private const COLUMNS = '`id`, `owner_type`, `owner_id`, `name`, `channel`, `kind`,
        `subject`, `body`, `is_active`, `created_by`, `created_at`, `updated_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `outreach_templates`
                (`owner_type`, `owner_id`, `name`, `channel`, `kind`, `subject`, `body`,
                 `is_active`, `created_by`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :name, :channel, :kind, :subject, :body,
                 :is_active, :created_by, NOW(), NOW())',
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
            'UPDATE `outreach_templates` SET
                `name` = :name, `channel` = :channel, `kind` = :kind, `subject` = :subject,
                `body` = :body, `is_active` = :is_active, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM `outreach_templates`
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
    public function listForContext(string $ownerType, ?int $ownerId, bool $canSeeAll, ?string $channel = null): array
    {
        $clauses = ['`deleted_at` IS NULL'];
        $params = [];
        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        if ($channel !== null && $channel !== '') {
            $clauses[] = '`channel` = :ch';
            $params['ch'] = $channel;
        }

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `outreach_templates`
             WHERE ' . implode(' AND ', $clauses) . ' ORDER BY `name` ASC',
            $params
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `outreach_templates` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }
}
