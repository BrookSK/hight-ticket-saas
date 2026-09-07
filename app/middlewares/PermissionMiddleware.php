<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Services\AuthService;

/**
 * Middleware enforcing a granular ACL permission.
 *
 * Instantiate with the required permission key and pass the instance in the
 * route's middleware list, e.g.:
 *
 *   new PermissionMiddleware('waitlist.view')
 *
 * The Router accepts both class-string and middleware instances. Super Admin
 * always passes (absolute access, handled in AuthService::can).
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $permission)
    {
    }

    /**
     * Convenience factory for readable route definitions.
     */
    public static function for(string $permission): self
    {
        return new self($permission);
    }

    public function handle(Request $request, Container $container, callable $next): void
    {
        /** @var AuthService $auth */
        $auth = $container->get(AuthService::class);

        if ($auth->can($this->permission)) {
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
