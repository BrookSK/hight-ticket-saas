<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for leads (opportunities). Owner-scoped; no business logic.
 *
 * Listing/pipeline join the company (for name) and the latest associated audit
 * score. Score filtering uses that latest score.
 */
final class LeadRepository extends Repository
{
    /** Latest audit score per lead via correlated subquery (explicit columns). */
    private const SELECT = 'l.`id`, l.`company_id`, l.`contact_id`, l.`responsible_user_id`,
        l.`title`, l.`source`, l.`status`, l.`temperature`, l.`service_type`,
        l.`estimated_value`, l.`currency`, l.`probability`, l.`expected_close_date`,
        l.`qualification`, l.`next_step`, l.`next_contact_at`, l.`last_activity_at`,
        l.`created_at`, c.`trade_name` AS `company_name`, c.`domain` AS `company_domain`,
        (SELECT a.`score_overall` FROM `lead_audits` la
            JOIN `audits` a ON a.`id` = la.`audit_id`
            WHERE la.`lead_id` = l.`id` AND a.`deleted_at` IS NULL
            ORDER BY a.`created_at` DESC LIMIT 1) AS `latest_score`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `leads`
                (`owner_type`, `owner_id`, `created_by`, `company_id`, `contact_id`,
                 `responsible_user_id`, `title`, `source`, `status`, `temperature`,
                 `service_type`, `estimated_value`, `currency`, `probability`,
                 `expected_close_date`, `qualification`, `next_step`, `next_contact_at`,
                 `notes`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :company_id, :contact_id,
                 :responsible_user_id, :title, :source, :status, :temperature,
                 :service_type, :estimated_value, :currency, :probability,
                 :expected_close_date, :qualification, :next_step, :next_contact_at,
                 :notes, NOW(), NOW())',
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
            'UPDATE `leads` SET
                `contact_id` = :contact_id, `responsible_user_id` = :responsible_user_id,
                `title` = :title, `source` = :source, `temperature` = :temperature,
                `service_type` = :service_type, `estimated_value` = :estimated_value,
                `currency` = :currency, `probability` = :probability,
                `expected_close_date` = :expected_close_date, `qualification` = :qualification,
                `next_step` = :next_step, `next_contact_at` = :next_contact_at,
                `need` = :need, `problem` = :problem, `budget` = :budget, `authority` = :authority,
                `urgency` = :urgency, `current_solution` = :current_solution,
                `competitor` = :competitor, `objection` = :objection, `notes` = :notes,
                `updated_at` = NOW()
             WHERE `id` = :id',
            $data
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForContext(int $id, string $ownerType, ?int $ownerId, bool $canSeeAll): ?array
    {
        $sql = 'SELECT l.*, c.`trade_name` AS `company_name`, c.`domain` AS `company_domain`
                FROM `leads` l JOIN `companies` c ON c.`id` = l.`company_id`
                WHERE l.`id` = :id AND l.`deleted_at` IS NULL';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND l.`owner_type` = :ot AND l.`owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::SELECT . ' FROM `leads` l
             JOIN `companies` c ON c.`id` = l.`company_id` ' . $where
            . ' ORDER BY l.`created_at` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): int
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `leads` l JOIN `companies` c ON c.`id` = l.`company_id` ' . $where,
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * All active (non-closed logic handled by caller) leads for the pipeline,
     * grouped later by status in the service.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPipeline(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, []);

        return $this->db->fetchAll(
            'SELECT ' . self::SELECT . ' FROM `leads` l
             JOIN `companies` c ON c.`id` = l.`company_id` ' . $where
            . ' ORDER BY l.`updated_at` DESC LIMIT 500',
            $params
        );
    }

    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::SELECT . ' FROM `leads` l
             JOIN `companies` c ON c.`id` = l.`company_id`
             WHERE l.`company_id` = :cid AND l.`deleted_at` IS NULL
             ORDER BY l.`created_at` DESC',
            ['cid' => $companyId]
        );
    }

    public function changeStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `leads` SET `status` = :s, `last_activity_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['s' => $status, 'id' => $id]
        );
    }

    public function touchActivity(int $id): void
    {
        $this->db->execute(
            'UPDATE `leads` SET `last_activity_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function assign(int $id, ?int $userId): void
    {
        $this->db->execute(
            'UPDATE `leads` SET `responsible_user_id` = :u, `updated_at` = NOW() WHERE `id` = :id',
            ['u' => $userId, 'id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $outcome
     */
    public function markWon(int $id, array $outcome): void
    {
        $outcome['id'] = $id;
        $this->db->execute(
            'UPDATE `leads` SET `status` = \'won\', `won_at` = NOW(), `final_value` = :final_value,
                `won_service` = :won_service, `outcome_notes` = :outcome_notes,
                `last_activity_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            $outcome
        );
    }

    public function markLost(int $id, string $reason, ?string $notes): void
    {
        $this->db->execute(
            'UPDATE `leads` SET `status` = \'lost\', `lost_at` = NOW(), `loss_reason` = :reason,
                `outcome_notes` = :notes, `last_activity_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['reason' => $reason, 'notes' => $notes, 'id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `leads` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    public function recordStatusHistory(int $leadId, ?int $userId, ?string $from, string $to): void
    {
        $this->db->execute(
            'INSERT INTO `lead_status_history` (`lead_id`, `user_id`, `from_status`, `to_status`, `created_at`)
             VALUES (:lid, :uid, :from, :to, NOW())',
            ['lid' => $leadId, 'uid' => $userId, 'from' => $from, 'to' => $to]
        );
    }

    public function linkAudit(int $leadId, int $auditId): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO `lead_audits` (`lead_id`, `audit_id`, `created_at`)
             VALUES (:lid, :aid, NOW())',
            ['lid' => $leadId, 'aid' => $auditId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function auditsForLead(int $leadId): array
    {
        return $this->db->fetchAll(
            'SELECT a.`id`, a.`host`, a.`status`, a.`score_overall`, a.`created_at`
             FROM `lead_audits` la JOIN `audits` a ON a.`id` = la.`audit_id`
             WHERE la.`lead_id` = :lid AND a.`deleted_at` IS NULL
             ORDER BY a.`created_at` DESC',
            ['lid' => $leadId]
        );
    }

    // --------------------------------------------------- dashboard aggregates

    /**
     * @return array<string, int>
     */
    public function countByStatus(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, []);
        $rows = $this->db->fetchAll(
            'SELECT `status`, COUNT(*) AS `total` FROM `leads` l ' . str_replace('c.', 'l.', $where)
            . ' GROUP BY `status`',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * Sum of estimated value for open leads and won value.
     *
     * @return array{estimated:float, won:float}
     */
    public function valueSummary(string $ownerType, ?int $ownerId, bool $canSeeAll): array
    {
        $ownerClause = $canSeeAll ? '' : ' AND `owner_type` = :ot AND `owner_id` = :oid';
        $params = $canSeeAll ? [] : ['ot' => $ownerType, 'oid' => $ownerId];

        $est = $this->db->fetch(
            'SELECT COALESCE(SUM(`estimated_value`),0) AS `v` FROM `leads`
             WHERE `deleted_at` IS NULL AND `status` NOT IN (\'won\',\'lost\',\'archived\')' . $ownerClause,
            $params
        );
        $won = $this->db->fetch(
            'SELECT COALESCE(SUM(`final_value`),0) AS `v` FROM `leads`
             WHERE `deleted_at` IS NULL AND `status` = \'won\'' . $ownerClause,
            $params
        );

        return ['estimated' => (float) ($est['v'] ?? 0), 'won' => (float) ($won['v'] ?? 0)];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): array
    {
        $clauses = ['l.`deleted_at` IS NULL'];
        $params = [];

        if (!$canSeeAll) {
            $clauses[] = 'l.`owner_type` = :ot AND l.`owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        foreach (['status' => 'status', 'temperature' => 'temperature', 'service_type' => 'service_type', 'source' => 'source'] as $key => $col) {
            if (!empty($filters[$key])) {
                $clauses[] = 'l.`' . $col . '` = :' . $key;
                $params[$key] = (string) $filters[$key];
            }
        }
        if (!empty($filters['responsible_user_id'])) {
            $clauses[] = 'l.`responsible_user_id` = :ruid';
            $params['ruid'] = (int) $filters['responsible_user_id'];
        }
        if (!empty($filters['search'])) {
            $clauses[] = '(c.`trade_name` LIKE :s OR c.`domain` LIKE :s OR l.`title` LIKE :s)';
            $params['s'] = '%' . (string) $filters['search'] . '%';
        }
        // Score range filter on the latest associated audit.
        if (!empty($filters['score_min']) || !empty($filters['score_max'])) {
            $min = (int) ($filters['score_min'] ?? 0);
            $max = (int) ($filters['score_max'] ?? 100);
            $clauses[] = '(SELECT a.`score_overall` FROM `lead_audits` la
                JOIN `audits` a ON a.`id` = la.`audit_id`
                WHERE la.`lead_id` = l.`id` AND a.`deleted_at` IS NULL
                ORDER BY a.`created_at` DESC LIMIT 1) BETWEEN :score_min AND :score_max';
            $params['score_min'] = $min;
            $params['score_max'] = $max;
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
