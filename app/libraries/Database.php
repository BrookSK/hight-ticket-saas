<?php

declare(strict_types=1);

namespace App\Libraries;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Thin PDO wrapper enforcing prepared statements.
 *
 * Repositories use this class exclusively for database access. Connection
 * settings come from config/database.php — the only file-based configuration
 * permitted by the Master Specification.
 */
final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    /**
     * @param array<string, mixed> $config
     */
    private function __construct(array $config)
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 3306),
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                $config['options'] ?? []
            );
        } catch (Throwable $e) {
            // Never expose credentials or driver details to the caller.
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function instance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Run a prepared statement with bound parameters.
     *
     * @param array<string|int, mixed> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Fetch a single row or null.
     *
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Fetch all rows.
     *
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * Execute a write statement and return affected rows.
     *
     * @param array<string|int, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
