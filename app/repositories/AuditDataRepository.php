<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for audit child records (pages, issues, metrics, links,
 * technologies, resources, contacts, DNS, processed results).
 *
 * Grouped in one repository because all records belong to a single audit and
 * share the same lifecycle. No business logic; explicit columns only.
 */
final class AuditDataRepository extends Repository
{
    // -------------------------------------------------------------- pages

    /**
     * @param array<string, mixed> $data
     */
    public function addPage(array $data): int
    {
        $this->db->execute(
            'INSERT INTO `audit_pages`
                (`audit_id`, `url`, `depth`, `status_code`, `content_type`, `title`,
                 `response_time_ms`, `html_size`, `redirected_to`, `fetch_status`, `error`, `created_at`)
             VALUES
                (:audit_id, :url, :depth, :status_code, :content_type, :title,
                 :response_time_ms, :html_size, :redirected_to, :fetch_status, :error, NOW())',
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pages(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `url`, `depth`, `status_code`, `content_type`, `title`,
                    `response_time_ms`, `html_size`, `redirected_to`, `fetch_status`, `error`
             FROM `audit_pages` WHERE `audit_id` = :id ORDER BY `depth` ASC, `id` ASC',
            ['id' => $auditId]
        );
    }

    // ------------------------------------------------------------- issues

    /**
     * @param array<string, mixed> $data
     */
    public function addIssue(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_issues`
                (`audit_id`, `rule_id`, `category`, `severity`, `confidence`, `title`,
                 `description`, `impact`, `recommendation`, `evidence`, `page_url`, `created_at`)
             VALUES
                (:audit_id, :rule_id, :category, :severity, :confidence, :title,
                 :description, :impact, :recommendation, :evidence, :page_url, NOW())',
            $data
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function issues(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `rule_id`, `category`, `severity`, `confidence`, `title`,
                    `description`, `impact`, `recommendation`, `evidence`, `page_url`
             FROM `audit_issues` WHERE `audit_id` = :id
             ORDER BY FIELD(`severity`, \'critical\',\'high\',\'medium\',\'low\',\'info\'), `id` ASC',
            ['id' => $auditId]
        );
    }

    /**
     * Count issues grouped by severity.
     *
     * @return array<string, int>
     */
    public function issueCountsBySeverity(int $auditId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT `severity`, COUNT(*) AS `total` FROM `audit_issues`
             WHERE `audit_id` = :id GROUP BY `severity`',
            ['id' => $auditId]
        );

        $counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];
        foreach ($rows as $row) {
            $counts[(string) $row['severity']] = (int) $row['total'];
        }

        return $counts;
    }

    // ------------------------------------------------------------ metrics

    /**
     * @param array<string, mixed> $data
     */
    public function addMetric(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_metrics` (`audit_id`, `category`, `key`, `value`, `unit`, `available`, `created_at`)
             VALUES (:audit_id, :category, :key, :value, :unit, :available, NOW())',
            $data
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function metrics(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `category`, `key`, `value`, `unit`, `available` FROM `audit_metrics`
             WHERE `audit_id` = :id ORDER BY `category`, `id`',
            ['id' => $auditId]
        );
    }

    // -------------------------------------------------------------- links

    /**
     * @param array<string, mixed> $data
     */
    public function addLink(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_links` (`audit_id`, `source_url`, `target_url`, `type`, `status_code`, `state`, `created_at`)
             VALUES (:audit_id, :source_url, :target_url, :type, :status_code, :state, NOW())',
            $data
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function brokenLinks(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `source_url`, `target_url`, `type`, `status_code`, `state` FROM `audit_links`
             WHERE `audit_id` = :id AND `state` IN (\'broken\',\'timeout\') ORDER BY `id`',
            ['id' => $auditId]
        );
    }

    // -------------------------------------------------------- technologies

    /**
     * @param array<string, mixed> $data
     */
    public function addTechnology(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_technologies` (`audit_id`, `name`, `category`, `version`, `confidence`, `evidence`, `created_at`)
             VALUES (:audit_id, :name, :category, :version, :confidence, :evidence, NOW())',
            $data
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function technologies(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `name`, `category`, `version`, `confidence`, `evidence` FROM `audit_technologies`
             WHERE `audit_id` = :id ORDER BY `category`, `name`',
            ['id' => $auditId]
        );
    }

    // ------------------------------------------------------------ contacts

    /**
     * @param array<string, mixed> $data
     */
    public function addContact(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_contacts` (`audit_id`, `type`, `value`, `created_at`)
             VALUES (:audit_id, :type, :value, NOW())',
            $data
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contacts(int $auditId): array
    {
        return $this->db->fetchAll(
            'SELECT `type`, `value` FROM `audit_contacts` WHERE `audit_id` = :id ORDER BY `type`',
            ['id' => $auditId]
        );
    }

    // ----------------------------------------------------------- resources

    /**
     * @param array<string, mixed> $data
     */
    public function addResource(array $data): void
    {
        $this->db->execute(
            'INSERT INTO `audit_resources` (`audit_id`, `type`, `url`, `size`, `attributes`, `created_at`)
             VALUES (:audit_id, :type, :url, :size, :attributes, NOW())',
            $data
        );
    }

    // ------------------------------------------------------- processed data

    /**
     * Store or replace a processed result block (JSON) under a key.
     */
    public function putResult(int $auditId, string $key, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->execute(
            'INSERT INTO `audit_results` (`audit_id`, `key`, `data`, `created_at`)
             VALUES (:audit_id, :key, :data, NOW())
             ON DUPLICATE KEY UPDATE `data` = :data_update',
            ['audit_id' => $auditId, 'key' => $key, 'data' => $json, 'data_update' => $json]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResult(int $auditId, string $key): ?array
    {
        $row = $this->db->fetch(
            'SELECT `data` FROM `audit_results` WHERE `audit_id` = :id AND `key` = :key LIMIT 1',
            ['id' => $auditId, 'key' => $key]
        );

        if ($row === null || !is_string($row['data'])) {
            return null;
        }

        $decoded = json_decode($row['data'], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Remove all child records for an audit (idempotent reprocessing).
     */
    public function clearForAudit(int $auditId): void
    {
        foreach (['audit_pages', 'audit_issues', 'audit_metrics', 'audit_links', 'audit_technologies', 'audit_resources', 'audit_contacts', 'audit_dns', 'audit_results'] as $table) {
            $this->db->execute('DELETE FROM `' . $table . '` WHERE `audit_id` = :id', ['id' => $auditId]);
        }
    }
}
