<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for companies. Owner-scoped; no business logic.
 */
final class CompanyRepository extends Repository
{
    private const COLUMNS = '`id`, `legal_name`, `trade_name`, `cnpj`, `website`, `domain`,
        `phone`, `whatsapp`, `email`, `address`, `city`, `state`, `country`, `zip_code`,
        `segment`, `description`, `instagram`, `facebook`, `linkedin`, `youtube`,
        `status`, `source`, `last_activity_at`, `created_at`';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `companies`
                (`owner_type`, `owner_id`, `created_by`, `legal_name`, `trade_name`, `cnpj`,
                 `website`, `domain`, `phone`, `whatsapp`, `email`, `address`, `city`, `state`,
                 `country`, `zip_code`, `segment`, `description`, `instagram`, `facebook`,
                 `linkedin`, `youtube`, `status`, `source`, `created_at`, `updated_at`)
             VALUES
                (:owner_type, :owner_id, :created_by, :legal_name, :trade_name, :cnpj,
                 :website, :domain, :phone, :whatsapp, :email, :address, :city, :state,
                 :country, :zip_code, :segment, :description, :instagram, :facebook,
                 :linkedin, :youtube, :status, :source, NOW(), NOW())',
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
            'UPDATE `companies` SET
                `legal_name` = :legal_name, `trade_name` = :trade_name, `cnpj` = :cnpj,
                `website` = :website, `domain` = :domain, `phone` = :phone, `whatsapp` = :whatsapp,
                `email` = :email, `address` = :address, `city` = :city, `state` = :state,
                `country` = :country, `zip_code` = :zip_code, `segment` = :segment,
                `description` = :description, `instagram` = :instagram, `facebook` = :facebook,
                `linkedin` = :linkedin, `youtube` = :youtube, `status` = :status,
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
        $sql = 'SELECT * FROM `companies` WHERE `id` = :id AND `deleted_at` IS NULL';
        $params = ['id' => $id];
        if (!$canSeeAll) {
            $sql .= ' AND `owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Possible duplicates by domain, cnpj, email or phone (owner-scoped).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPossibleDuplicates(string $ownerType, int $ownerId, ?string $domain, ?string $cnpj, ?string $email, ?string $phone): array
    {
        $clauses = [];
        $params = ['ot' => $ownerType, 'oid' => $ownerId];
        if ($domain !== null && $domain !== '') { $clauses[] = '`domain` = :domain'; $params['domain'] = $domain; }
        if ($cnpj !== null && $cnpj !== '') { $clauses[] = '`cnpj` = :cnpj'; $params['cnpj'] = $cnpj; }
        if ($email !== null && $email !== '') { $clauses[] = '`email` = :email'; $params['email'] = $email; }
        if ($phone !== null && $phone !== '') { $clauses[] = '`phone` = :phone'; $params['phone'] = $phone; }

        if ($clauses === []) {
            return [];
        }

        return $this->db->fetchAll(
            'SELECT `id`, `trade_name`, `domain`, `cnpj`, `email` FROM `companies`
             WHERE `owner_type` = :ot AND `owner_id` = :oid AND `deleted_at` IS NULL
               AND (' . implode(' OR ', $clauses) . ') LIMIT 5',
            $params
        );
    }

    /**
     * Paginated, filtered listing with the primary contact and latest score.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $offset = max(0, ($page - 1) * $perPage);

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM `companies` ' . $where
            . ' ORDER BY `created_at` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): int
    {
        [$where, $params] = $this->buildWhere($ownerType, $ownerId, $canSeeAll, $filters);
        $row = $this->db->fetch('SELECT COUNT(*) AS `total` FROM `companies` ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute('UPDATE `companies` SET `status` = :s, `updated_at` = NOW() WHERE `id` = :id', ['s' => $status, 'id' => $id]);
    }

    public function touchActivity(int $id): void
    {
        $this->db->execute('UPDATE `companies` SET `last_activity_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    public function softDelete(int $id): void
    {
        $this->db->execute('UPDATE `companies` SET `deleted_at` = NOW() WHERE `id` = :id', ['id' => $id]);
    }

    public function totalForContext(string $ownerType, ?int $ownerId, bool $canSeeAll): int
    {
        return $this->count($ownerType, $ownerId, $canSeeAll, []);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(string $ownerType, ?int $ownerId, bool $canSeeAll, array $filters): array
    {
        $clauses = ['`deleted_at` IS NULL'];
        $params = [];

        if (!$canSeeAll) {
            $clauses[] = '`owner_type` = :ot AND `owner_id` = :oid';
            $params['ot'] = $ownerType;
            $params['oid'] = $ownerId;
        }
        if (!empty($filters['status'])) {
            $clauses[] = '`status` = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (!empty($filters['search'])) {
            $clauses[] = '(`trade_name` LIKE :s OR `legal_name` LIKE :s OR `domain` LIKE :s OR `email` LIKE :s OR `cnpj` LIKE :s)';
            $params['s'] = '%' . (string) $filters['search'] . '%';
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
