<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for commercial plans. No business logic here.
 */
final class PlanRepository extends Repository
{
    private const COLUMNS = '`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`,
        `currency`, `cta_label`, `cta_url`, `features`, `limitations`,
        `is_featured`, `sort_order`, `status`';

    /**
     * Active plans ordered for public display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActiveOrdered(): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `plans`
             WHERE `status` = :status AND `deleted_at` IS NULL
             ORDER BY `sort_order` ASC, `id` ASC',
            ['status' => 'active']
        );
    }

    /**
     * All non-deleted plans for admin management.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allOrdered(): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `plans`
             WHERE `deleted_at` IS NULL ORDER BY `sort_order` ASC, `id` ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `plans`
             WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `plans`
                (`name`, `slug`, `description`, `price_monthly`, `price_yearly`, `currency`,
                 `cta_label`, `cta_url`, `features`, `limitations`, `is_featured`, `sort_order`,
                 `status`, `created_at`, `updated_at`)
             VALUES
                (:name, :slug, :description, :price_monthly, :price_yearly, :currency,
                 :cta_label, :cta_url, :features, :limitations, :is_featured, :sort_order,
                 :status, NOW(), NOW())',
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
            'UPDATE `plans` SET
                `name` = :name, `slug` = :slug, `description` = :description,
                `price_monthly` = :price_monthly, `price_yearly` = :price_yearly, `currency` = :currency,
                `cta_label` = :cta_label, `cta_url` = :cta_url, `features` = :features,
                `limitations` = :limitations, `is_featured` = :is_featured, `sort_order` = :sort_order,
                `status` = :status, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `plans` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT `id` FROM `plans` WHERE `slug` = :slug AND `deleted_at` IS NULL';
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> :except_id';
            $params['except_id'] = $exceptId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }
}
