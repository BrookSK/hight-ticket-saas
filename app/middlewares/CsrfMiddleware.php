<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Container;
use App\Libraries\Csrf;
use App\Libraries\Request;
use App\Libraries\Response;

/**
 * Verifies the CSRF token on state-changing requests (POST/PUT/PATCH/DELETE).
 *
 * Safe methods (GET/HEAD/OPTIONS) pass through. The token may arrive as the
 * "_token" field or the "X-CSRF-Token" header.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Container $container, callable $next): void
    {
        $method = $request->method();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $next($request);

            return;
        }

        /** @var Csrf $csrf */
        $csrf = $container->get('csrf');

        $token = $request->input('_token');
        if (!is_string($token)) {
            $token = $request->header('X-CSRF-Token');
        }

        if ($csrf->validate(is_string($token) ? $token : null)) {
            $next($request);

            return;
        }

        /** @var Response $response */
        $response = $container->get('response');

        if ($request->expectsJson()) {
            $response->error(__('errors.csrf'), [], 419);

            return;
        }

        $response->html('<h1>' . e(__('errors.csrf')) . '</h1>', 419);
    }
}
