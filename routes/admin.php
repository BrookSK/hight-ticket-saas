<?php

declare(strict_types=1);

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LogController;
use App\Controllers\Admin\PlanController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\WaitlistController;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;

/**
 * Admin (Super Admin panel) routes.
 *
 * The whole /app group requires authentication. Each route additionally
 * enforces its granular permission via PermissionMiddleware. State-changing
 * routes go through CsrfMiddleware. Super Admin bypasses permission checks.
 */

return static function (Router $router): void {
    $router->group('/app', static function (Router $router): void {
        // Dashboard.
        $router->get('/', [DashboardController::class, 'index'], [
            PermissionMiddleware::for('dashboard.view'),
        ]);

        // Waitlist management.
        $router->get('/waitlist', [WaitlistController::class, 'index'], [PermissionMiddleware::for('waitlist.view')]);
        $router->get('/waitlist/export', [WaitlistController::class, 'export'], [PermissionMiddleware::for('waitlist.export')]);
        $router->get('/waitlist/{id}', [WaitlistController::class, 'show'], [PermissionMiddleware::for('waitlist.view')]);
        $router->post('/waitlist/{id}/status', [WaitlistController::class, 'updateStatus'], [PermissionMiddleware::for('waitlist.edit'), CsrfMiddleware::class]);
        $router->post('/waitlist/{id}/notes', [WaitlistController::class, 'updateNotes'], [PermissionMiddleware::for('waitlist.edit'), CsrfMiddleware::class]);
        $router->post('/waitlist/{id}/delete', [WaitlistController::class, 'delete'], [PermissionMiddleware::for('waitlist.delete'), CsrfMiddleware::class]);

        // Plans.
        $router->get('/plans', [PlanController::class, 'index'], [PermissionMiddleware::for('plans.view')]);
        $router->get('/plans/create', [PlanController::class, 'create'], [PermissionMiddleware::for('plans.create')]);
        $router->post('/plans', [PlanController::class, 'store'], [PermissionMiddleware::for('plans.create'), CsrfMiddleware::class]);
        $router->get('/plans/{id}/edit', [PlanController::class, 'edit'], [PermissionMiddleware::for('plans.edit')]);
        $router->put('/plans/{id}', [PlanController::class, 'update'], [PermissionMiddleware::for('plans.edit'), CsrfMiddleware::class]);
        $router->post('/plans/{id}/delete', [PlanController::class, 'delete'], [PermissionMiddleware::for('plans.delete'), CsrfMiddleware::class]);

        // Users.
        $router->get('/users', [UserController::class, 'index'], [PermissionMiddleware::for('users.view')]);
        $router->get('/users/create', [UserController::class, 'create'], [PermissionMiddleware::for('users.create')]);
        $router->post('/users', [UserController::class, 'store'], [PermissionMiddleware::for('users.create'), CsrfMiddleware::class]);
        $router->get('/users/{id}/edit', [UserController::class, 'edit'], [PermissionMiddleware::for('users.edit')]);
        $router->put('/users/{id}', [UserController::class, 'update'], [PermissionMiddleware::for('users.edit'), CsrfMiddleware::class]);
        $router->post('/users/{id}/delete', [UserController::class, 'delete'], [PermissionMiddleware::for('users.delete'), CsrfMiddleware::class]);
        $router->post('/users/{id}/impersonate', [UserController::class, 'impersonate'], [CsrfMiddleware::class]);
        $router->post('/impersonate/stop', [UserController::class, 'stopImpersonation'], [CsrfMiddleware::class]);

        // Roles & permissions.
        $router->get('/roles', [RoleController::class, 'index'], [PermissionMiddleware::for('roles.view')]);
        $router->get('/roles/{id}/edit', [RoleController::class, 'edit'], [PermissionMiddleware::for('roles.edit')]);
        $router->put('/roles/{id}', [RoleController::class, 'update'], [PermissionMiddleware::for('roles.edit'), CsrfMiddleware::class]);

        // Settings (Configurações Gerais).
        $router->get('/settings', [SettingsController::class, 'index'], [PermissionMiddleware::for('settings.view')]);
        $router->get('/settings/{group}', [SettingsController::class, 'index'], [PermissionMiddleware::for('settings.view')]);
        $router->put('/settings/{group}', [SettingsController::class, 'update'], [PermissionMiddleware::for('settings.edit'), CsrfMiddleware::class]);

        // Logs.
        $router->get('/logs', [LogController::class, 'index'], [PermissionMiddleware::for('logs.view')]);
    }, [AuthMiddleware::class]);
};
