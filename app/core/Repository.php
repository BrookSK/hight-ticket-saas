<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Database;

/**
 * Base repository.
 *
 * Repositories are the only place that touches the database. They contain no
 * business logic. Every query uses prepared statements and selects explicit
 * columns (never SELECT *), per the Master Specification.
 */
abstract class Repository
{
    public function __construct(protected readonly Database $db)
    {
    }
}
