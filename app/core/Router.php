<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Request;
use App\Libraries\Response;
use App\Middlewares\MiddlewareInterface;

/**
 * HTTP router.
 *
 * Maps method + path patterns to controller actions and runs the middleware
 * pipeline before the controller. Enforces the layered flow:
 *   Request -> Router -> Middleware -> Controller.
 *
 * Route patterns support named parameters, e.g. "/users/{id}".
 */
final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:array{0:class-string,1:string},middlewares:list<class-string>}> */
    private array $routes = [];

    /** @var list<class-string> Middlewares applied while defining a group. */
    private array $groupMiddlewares = [];

    private string $groupPrefix = '';

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middlewares
     */
    public function get(string $pattern, array $handler, array $middlewares = []): void
    {
        $this->add('GET', $pattern, $handler, $middlewares);
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middlewares
     */
    public function post(string $pattern, array $handler, array $middlewares = []): void
    {
        $this->add('POST', $pattern, $handler, $middlewares);
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middlewares
     */
    public function put(string $pattern, array $handler, array $middlewares = []): void
    {
        $this->add('PUT', $pattern, $handler, $middlewares);
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middlewares
     */
    public function delete(string $pattern, array $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middlewares);
    }

    /**
     * Group routes under a shared prefix and/or middleware set.
     *
     * @param list<class-string> $middlewares
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->groupPrefix = $previousPrefix . '/' . trim($prefix, '/');
        $this->groupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param list<class-string> $middlewares
     */
    private function add(string $method, string $pattern, array $handler, array $middlewares): void
    {
        $fullPattern = $this->groupPrefix . '/' . trim($pattern, '/');
        $fullPattern = '/' . trim($fullPattern, '/');

        $this->routes[] = [
            'method'      => $method,
            'pattern'     => $fullPattern === '//' ? '/' : $fullPattern,
            'handler'     => $handler,
            'middlewares' => array_merge($this->groupMiddlewares, $middlewares),
        ];
    }

    /**
     * Match and dispatch the request.
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            $this->runPipeline($route, $request, $params);

            return;
        }

        $this->notFound($request);
    }

    /**
     * @return array<string, string>|null Matched parameters, or null on no match.
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * @param array{method:string,pattern:string,handler:array{0:class-string,1:string},middlewares:list<class-string>} $route
     * @param array<string, string> $params
     */
    private function runPipeline(array $route, Request $request, array $params): void
    {
        $container = $this->container;

        // The final step: instantiate the controller and call the action.
        $destination = static function (Request $request) use ($route, $params, $container): void {
            [$controllerClass, $action] = $route['handler'];
            /** @var object $controller */
            $controller = new $controllerClass($container);
            $controller->{$action}($request, $params);
        };

        // Build the middleware chain in reverse so it executes in order.
        $pipeline = array_reduce(
            array_reverse($route['middlewares']),
            static function (callable $next, string $middlewareClass) use ($container): callable {
                return static function (Request $request) use ($middlewareClass, $container, $next): void {
                    /** @var MiddlewareInterface $middleware */
                    $middleware = new $middlewareClass();
                    $middleware->handle($request, $container, $next);
                };
            },
            $destination
        );

        $pipeline($request);
    }

    private function notFound(Request $request): void
    {
        /** @var Response $response */
        $response = $this->container->get('response');

        if ($request->expectsJson()) {
            $response->error('Resource not found.', [], 404);

            return;
        }

        $response->html('<h1>404</h1>', 404);
    }
}
