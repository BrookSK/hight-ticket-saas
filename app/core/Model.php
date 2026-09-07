<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base model.
 *
 * Models are plain data structures representing a database entity. They carry
 * attributes and simple accessors; they do not access the database (that is the
 * repository's job) and hold no business logic.
 */
abstract class Model
{
    /** @var array<string, mixed> */
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new static($attributes);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
