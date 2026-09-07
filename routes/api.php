<?php

declare(strict_types=1);

use App\Core\Router;

/**
 * API routes.
 *
 * All API responses use the standard JSON envelope
 * (success, message, data, errors, meta). Authentication is prepared for
 * tokens in the future. Routes are added per module as the API surface grows.
 *
 * Kept separate from web routes to cleanly distinguish Web vs API controllers.
 */

return static function (Router $router): void {
    $router->group('/api', static function (Router $router): void {
        // Module API routes will be registered here.
    });
};
