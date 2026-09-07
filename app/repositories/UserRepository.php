<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for users. No business logic here.
 *
 * Respects soft delete: active queries filter out rows with deleted_at set.
 */
final class UserRepository extends Repository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `name`, `email`, `password_hash`, `role_id`, `is_super_admin`, `status`
             FROM `users`
             WHERE `email` = :email AND `deleted_at` IS NULL
             LIMIT 1',
            ['email' => $email]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `name`, `email`, `role_id`, `is_super_admin`, `status`
             FROM `users`
             WHERE `id` = :id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * Permission keys granted to a user via its role.
     *
     * @return list<string>
     */
    public function permissionKeysForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.`key` AS `key`
             FROM `users` u
             INNER JOIN `role_permission` rp ON rp.`role_id` = u.`role_id`
             INNER JOIN `permissions` p ON p.`id` = rp.`permission_id`
             WHERE u.`id` = :id AND u.`deleted_at` IS NULL',
            ['id' => $userId]
        );

        return array_map(static fn (array $row): string => (string) $row['key'], $rows);
    }
}
