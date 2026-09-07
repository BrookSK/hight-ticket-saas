<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;

/**
 * Web routes.
 *
 * Each route maps to a controller action. State-changing routes go through
 * CsrfMiddleware; authenticated areas go through AuthMiddleware. Granular
 * permission middlewares are added per module as they are built.
 *
 * There is intentionally NO migration route (migrations run manually only).
 */

return static function (Router $router): void {
    // Institutional.
    $router->get('/', [HomeController::class, 'index']);

    // Authentication.
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->get('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

    // Authenticated back-office.
    $router->group('/app', static function (Router $router): void {
        $router->get('/', [DashboardController::class, 'index']);
    }, [AuthMiddleware::class]);
};
