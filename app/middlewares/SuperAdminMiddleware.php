<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Services\AuthService;

/**
 * Restricts access to Super Administrators only.
 *
 * Used for critical areas such as Configurações Gerais and impersonation.
 */
final class SuperAdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Container $container, callable $next): void
    {
        /** @var AuthService $auth */
        $auth = $container->get(AuthService::class);

        if ($auth->isSuperAdmin()) {
            $next($request);

            return;
        }

        /** @var Response $response */
        $response = $container->get('response');

        if ($request->expectsJson()) {
            $response->error(__('errors.forbidden'), [], 403);

            return;
        }

        $response->html('<h1>' . e(__('errors.forbidden')) . '</h1>', 403);
    }
}
