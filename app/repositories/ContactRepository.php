<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for contacts. Owner-scoped; no business logic.
 */
final class ContactRepository extends Repository
{
    private const COLUMNS = '`id`, `company_id`, `first_name`, `last_name`, `role_title`,
        `email`, `phone`, `whatsapp`, `linkedin`, `notes`, `is_primary`, `status`,
        `source`, `data_confidence`, `created_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `contacts`
                (`owner_type`, `owner_id`, `company_id`, `first_name`, `last_name`, `role_title`,
                 `email`, `phone`, `whatsapp`, `linkedin`, `notes`, `is_primary`, `status`,
                 `source`, `data_confidence`, `consent`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :company_id, :first_name, :last_name, :role_title,
                 :email, :phone, :whatsapp, :linkedin, :notes, :is_primary, :status,
                 :source, :data_confidence, :consent, NOW(), NOW())',
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
            'UPDATE `contacts` SET
                `first_name` = :first_name, `last_name` = :last_name, `role_title` = :role_title,
                `email` = :email, `phone` = :phone, `whatsapp` = :whatsapp, `linkedin` = :linkedin,
                `notes` = :notes, `status` = :status, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT * FROM `contacts` WHERE `id` = :id AND `deleted_at` IS NULL';
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
    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `contacts`
             WHERE `company_id` = :cid AND `deleted_at` IS NULL
             ORDER BY `is_primary` DESC, `id` ASC',
            ['cid' => $companyId]
        );
    }

    /**
     * Clear the primary flag for all contacts of a company (before setting one).
     */
    public function clearPrimary(int $companyId): void
    {
        $this->db->execute(
            'UPDATE `contacts` SET `is_primary` = 0 WHERE `company_id` = :cid',
            ['cid' => $companyId]
        );
    }

    public function setPrimary(int $id): void
    {
        $this->db->execute('UPDATE `contacts` SET `is_primary` = 1, `updated_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `contacts` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }
}
