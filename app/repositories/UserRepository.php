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

    /**
     * All non-deleted users with their role name, for admin listing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithRole(): array
    {
        return $this->db->fetchAll(
            'SELECT u.`id`, u.`name`, u.`email`, u.`phone`, u.`status`, u.`is_super_admin`,
                    u.`role_id`, r.`name` AS `role_name`, u.`last_login_at`, u.`created_at`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`deleted_at` IS NULL
             ORDER BY u.`id` ASC'
        );
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT `id` FROM `users` WHERE `email` = :email AND `deleted_at` IS NULL';
        $params = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> :except_id';
            $params['except_id'] = $exceptId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `users`
                (`name`, `email`, `phone`, `password_hash`, `role_id`, `is_super_admin`,
                 `status`, `created_at`, `updated_at`)
             VALUES
                (:name, :email, :phone, :password_hash, :role_id, :is_super_admin,
                 :status, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a user's core fields (password handled separately).
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->db->execute(
            'UPDATE `users` SET
                `name` = :name, `email` = :email, `phone` = :phone,
                `role_id` = :role_id, `status` = :status, `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE `users` SET `password_hash` = :hash, `updated_at` = NOW() WHERE `id` = :id',
            ['hash' => $passwordHash, 'id' => $id]
        );
    }

    public function updatePasswordByEmail(string $email, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE `users` SET `password_hash` = :hash, `updated_at` = NOW()
             WHERE `email` = :email AND `deleted_at` IS NULL',
            ['hash' => $passwordHash, 'email' => $email]
        );
    }

    public function touchLogin(int $id): void
    {
        $this->db->execute(
            'UPDATE `users` SET `last_login_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `users` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function total(): int
    {
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `users` WHERE `deleted_at` IS NULL');

        return (int) ($row['total'] ?? 0);
    }
}
