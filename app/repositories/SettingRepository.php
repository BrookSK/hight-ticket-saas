<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for system settings (Configurações Gerais).
 *
 * Settings are stored as key/value rows. Only explicit columns are selected.
 */
final class SettingRepository extends Repository
{
    /**
     * Return all settings as an associative array of key => value.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT `key`, `value` FROM `settings` ORDER BY `key` ASC'
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['key']] = $row['value'] === null ? null : (string) $row['value'];
        }

        return $settings;
    }

    public function get(string $key): ?string
    {
        $row = $this->db->fetch(
            'SELECT `value` FROM `settings` WHERE `key` = :key LIMIT 1',
            ['key' => $key]
        );

        if ($row === null) {
            return null;
        }

        return $row['value'] === null ? null : (string) $row['value'];
    }

    /**
     * Insert or update a setting value.
     */
    public function set(string $key, ?string $value, string $group = 'general'): void
    {
        $this->db->execute(
            'INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
             VALUES (:key, :value, :group, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `value` = :value_update, `updated_at` = NOW()',
            [
                'key'          => $key,
                'value'        => $value,
                'group'        => $group,
                'value_update' => $value,
            ]
        );
    }
}
