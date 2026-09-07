<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Services\AuthService;

/**
 * Ensures the request comes from an authenticated user.
 *
 * Unauthenticated requests are redirected to the login page (web) or receive a
 * 401 JSON envelope (API).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Container $container, callable $next): void
    {
        /** @var AuthService $auth */
        $auth = $container->get(AuthService::class);

        if ($auth->check()) {
            $next($request);

            return;
        }

        /** @var Response $response */
        $response = $container->get('response');

        if ($request->expectsJson()) {
            $response->error(__('errors.unauthorized'), [], 401);

            return;
        }

        $response->redirect('/login');
    }
}
