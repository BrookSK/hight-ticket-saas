<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Minimal service container.
 *
 * Holds shared framework services (database, translator, session, logger, etc.)
 * and resolves them lazily. Keeps low coupling: consumers depend on the
 * container to fetch collaborators rather than instantiating them directly.
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Register a shared (singleton) factory.
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Register an already-created instance.
     */
    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    /**
     * Resolve a service by id.
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException(sprintf('Service "%s" is not registered.', $id));
        }

        $instance = ($this->bindings[$id])($this);
        $this->instances[$id] = $instance;

        return $instance;
    }
}
