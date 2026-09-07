<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for roles and their permissions. No business logic here.
 */
final class RoleRepository extends Repository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `name`, `slug`, `description`, `is_system`
             FROM `roles` WHERE `deleted_at` IS NULL ORDER BY `id` ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `name`, `slug`, `description`, `is_system`
             FROM `roles` WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allPermissions(): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `key`, `group`, `description` FROM `permissions`
             ORDER BY `group` ASC, `key` ASC'
        );
    }

    /**
     * Permission ids granted to a role.
     *
     * @return list<int>
     */
    public function permissionIdsForRole(int $roleId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT `permission_id` FROM `role_permission` WHERE `role_id` = :role_id',
            ['role_id' => $roleId]
        );

        return array_map(static fn (array $row): int => (int) $row['permission_id'], $rows);
    }

    /**
     * Replace the permission set for a role.
     *
     * @param list<int> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->execute('DELETE FROM `role_permission` WHERE `role_id` = :role_id', ['role_id' => $roleId]);

        foreach ($permissionIds as $permissionId) {
            $this->db->execute(
                'INSERT INTO `role_permission` (`role_id`, `permission_id`, `created_at`)
                 VALUES (:role_id, :permission_id, NOW())',
                ['role_id' => $roleId, 'permission_id' => (int) $permissionId]
            );
        }
    }
}
