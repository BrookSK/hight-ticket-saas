<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Services\AuthService;

/**
 * Base middleware enforcing a granular ACL permission.
 *
 * Concrete middlewares extend this and declare the required permission key via
 * permission(). Example:
 *
 *   final class UsersViewMiddleware extends PermissionMiddleware
 *   {
 *       protected function permission(): string { return 'users.view'; }
 *   }
 *
 * This keeps route definitions declarative while permission checks stay in one
 * place. Super Admin always passes (absolute access, handled in AuthService).
 */
abstract class PermissionMiddleware implements MiddlewareInterface
{
    /**
     * The granular permission key required to proceed (e.g. "users.view").
     */
    abstract protected function permission(): string;

    public function handle(Request $request, Container $container, callable $next): void
    {
        /** @var AuthService $auth */
        $auth = $container->get(AuthService::class);

        if ($auth->can($this->permission())) {
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
