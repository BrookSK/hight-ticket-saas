<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for waitlist leads. No business logic here.
 *
 * Supports filtering, search, ordering and pagination for the admin listing.
 * Respects soft delete (deleted_at IS NULL) on read queries.
 */
final class WaitlistLeadRepository extends Repository
{
    private const COLUMNS = '`id`, `name`, `email`, `phone`, `company`, `sites_quantity`,
        `main_service`, `message`, `status`, `source`, `source_url`,
        `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`,
        `consent`, `notes`, `last_contact_at`, `created_at`, `updated_at`';

    /** Sortable columns whitelist to avoid SQL injection via order-by. */
    private const SORTABLE = ['created_at', 'name', 'email', 'status', 'company', 'last_contact_at'];

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `email`, `status` FROM `waitlist_leads`
             WHERE `email` = :email AND `deleted_at` IS NULL LIMIT 1',
            ['email' => $email]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM `waitlist_leads`
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
            'INSERT INTO `waitlist_leads`
                (`name`, `email`, `phone`, `company`, `sites_quantity`, `main_service`,
                 `message`, `status`, `source`, `source_url`,
                 `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`,
                 `consent`, `created_at`, `updated_at`)
             VALUES
                (:name, :email, :phone, :company, :sites_quantity, :main_service,
                 :message, :status, :source, :source_url,
                 :utm_source, :utm_medium, :utm_campaign, :utm_content, :utm_term,
                 :consent, NOW(), NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE `waitlist_leads` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id',
            ['status' => $status, 'id' => $id]
        );
    }

    public function updateNotes(int $id, ?string $notes): void
    {
        $this->db->execute(
            'UPDATE `waitlist_leads` SET `notes` = :notes, `updated_at` = NOW() WHERE `id` = :id',
            ['notes' => $notes, 'id' => $id]
        );
    }

    public function touchContact(int $id): void
    {
        $this->db->execute(
            'UPDATE `waitlist_leads` SET `last_contact_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE `waitlist_leads` SET `deleted_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }

    /**
     * Paginated listing with optional filters, search and ordering.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function paginate(array $filters, int $page, int $perPage, string $orderBy, string $direction): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $orderBy = in_array($orderBy, self::SORTABLE, true) ? $orderBy : 'created_at';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $offset = max(0, ($page - 1) * $perPage);

        $sql = 'SELECT ' . self::COLUMNS . ' FROM `waitlist_leads` '
            . $where
            . ' ORDER BY `' . $orderBy . '` ' . $direction
            . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(array $filters): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `waitlist_leads` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Full result set for export (filtered, no pagination).
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function allForExport(array $filters): array
    {
        [$where, $params] = $this->buildFilters($filters);

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `waitlist_leads` ' . $where . ' ORDER BY `created_at` DESC',
            $params
        );
    }

    // -------------------------------------------------------- dashboard metrics

    public function total(): int
    {
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `waitlist_leads` WHERE `deleted_at` IS NULL');

        return (int) ($row['total'] ?? 0);
    }

    public function countByStatus(string $status): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `waitlist_leads`
             WHERE `status` = :status AND `deleted_at` IS NULL',
            ['status' => $status]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function countSince(int $days): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS `total` FROM `waitlist_leads`
             WHERE `deleted_at` IS NULL AND `created_at` >= (NOW() - INTERVAL :days DAY)',
            ['days' => $days]
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function statusBreakdown(): array
    {
        return $this->db->fetchAll(
            'SELECT `status`, COUNT(*) AS `total` FROM `waitlist_leads`
             WHERE `deleted_at` IS NULL GROUP BY `status` ORDER BY `total` DESC'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topSources(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        return $this->db->fetchAll(
            'SELECT `source`, COUNT(*) AS `total` FROM `waitlist_leads`
             WHERE `deleted_at` IS NULL GROUP BY `source` ORDER BY `total` DESC LIMIT ' . $limit
        );
    }

    /**
     * Build a WHERE clause from filters. Always excludes soft-deleted rows.
     *
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $clauses = ['`deleted_at` IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $clauses[] = '`status` = :status';
            $params['status'] = (string) $filters['status'];
        }

        if (!empty($filters['source'])) {
            $clauses[] = '`source` = :source';
            $params['source'] = (string) $filters['source'];
        }

        if (!empty($filters['main_service'])) {
            $clauses[] = '`main_service` = :main_service';
            $params['main_service'] = (string) $filters['main_service'];
        }

        if (!empty($filters['sites_quantity'])) {
            $clauses[] = '`sites_quantity` = :sites_quantity';
            $params['sites_quantity'] = (string) $filters['sites_quantity'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[] = '`created_at` >= :date_from';
            $params['date_from'] = (string) $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $clauses[] = '`created_at` <= :date_to';
            $params['date_to'] = (string) $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $clauses[] = '(`name` LIKE :search OR `email` LIKE :search OR `company` LIKE :search)';
            $params['search'] = '%' . (string) $filters['search'] . '%';
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }
}
