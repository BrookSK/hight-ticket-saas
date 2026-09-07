<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base service.
 *
 * Services hold ALL business logic. Cross-module communication happens only
 * through services. They orchestrate repositories, other services, events and
 * integrations, but never render views or read superglobals directly.
 */
abstract class Service
{
    public function __construct(protected readonly Container $container)
    {
    }
}
