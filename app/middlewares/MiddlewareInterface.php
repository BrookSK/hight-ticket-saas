<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Request;

/**
 * Contract for middlewares.
 *
 * A middleware inspects the request and either allows it to continue (by
 * calling $next) or short-circuits the pipeline (e.g. redirect, deny).
 * Returning without calling $next stops the chain.
 */
interface MiddlewareInterface
{
    /**
     * @param callable(Request): void $next
     */
    public function handle(Request $request, Container $container, callable $next): void;
}
