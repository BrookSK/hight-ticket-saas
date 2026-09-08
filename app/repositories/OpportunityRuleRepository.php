<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for opportunity scoring rules (configurable weights).
 *
 * Rules can be global (owner_id NULL) or owner-specific; owner-specific rules
 * override the global default for the same rule_key.
 */
final class OpportunityRuleRepository extends Repository
{
    /**
     * Effective rules for an owner: owner-specific override global defaults.
     *
     * @return array<int, array<string, mixed>>
     */
    public function effectiveRules(string $ownerType, int $ownerId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT `rule_key`, `name`, `description`, `weight`, `confidence`, `recommended_service`,
                    `is_active`, `owner_id`
             FROM `opportunity_rules`
             WHERE `is_active` = 1
               AND (`owner_id` IS NULL OR (`owner_type` = :ot AND `owner_id` = :oid))
             ORDER BY `owner_id` IS NULL DESC', // global first, then owner overrides
            ['ot' => $ownerType, 'oid' => $ownerId]
        );

        // Owner-specific overrides global for the same rule_key.
        $byKey = [];
        foreach ($rows as $row) {
            $key = (string) $row['rule_key'];
            if (!isset($byKey[$key]) || $row['owner_id'] !== null) {
                $byKey[$key] = $row;
            }
        }

        return array_values($byKey);
    }

    /**
     * All rules for admin management (global defaults).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allDefaults(): array
    {
        return $this->db->fetchAll(
            'SELECT `id`, `rule_key`, `name`, `description`, `weight`, `confidence`, `recommended_service`, `is_active`
             FROM `opportunity_rules` WHERE `owner_id` IS NULL ORDER BY `weight` DESC'
        );
    }
}
