<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Cache;
use App\Libraries\Csrf;
use App\Libraries\Database;
use App\Libraries\Logger;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Libraries\Session;
use App\Libraries\Translator;
use App\Events\EventDispatcher;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ConfigService;

/**
 * Application kernel.
 *
 * Boots the container, registers core services, prepares the environment
 * (session, error handling, locale) and dispatches the request through the
 * router. This is the single composition root of the application.
 */
final class Kernel
{
    private Container $container;

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->container = new Container();
    }

    /**
     * Boot services and return the container.
     */
    public function boot(): Container
    {
        /** @var array<string, mixed> $appConfig */
        $appConfig = require $this->rootPath . '/config/app.php';
        /** @var array<string, mixed> $dbConfig */
        $dbConfig = require $this->rootPath . '/config/database.php';

        $this->container->instance('config.app', $appConfig);
        $this->container->instance('rootPath', $this->rootPath);

        $this->registerCoreServices($appConfig, $dbConfig);
        $this->prepareEnvironment($appConfig);

        // Make the container globally reachable for helper functions.
        $GLOBALS['__app_container'] = $this->container;

        return $this->container;
    }

    /**
     * Handle the current HTTP request.
     */
    public function handle(Request $request): void
    {
        /** @var Router $router */
        $router = $this->container->get('router');

        $routesFile = $this->rootPath . '/routes/web.php';
        if (is_file($routesFile)) {
            (require $routesFile)($router);
        }

        $apiRoutesFile = $this->rootPath . '/routes/api.php';
        if (is_file($apiRoutesFile)) {
            (require $apiRoutesFile)($router);
        }

        $router->dispatch($request);
    }

    /**
     * @param array<string, mixed> $appConfig
     * @param array<string, mixed> $dbConfig
     */
    private function registerCoreServices(array $appConfig, array $dbConfig): void
    {
        $c = $this->container;
        $rootPath = $this->rootPath;

        $c->singleton('logger', static fn (): Logger => new Logger($rootPath . '/storage/logs'));

        $c->singleton('cache', static fn (): Cache => new Cache($rootPath . '/storage/cache'));

        $c->singleton('database', static fn (): Database => Database::instance($dbConfig));

        $c->singleton('session', static fn (): Session => new Session());

        $c->singleton('csrf', static fn (Container $c): Csrf => new Csrf($c->get('session')));

        $c->singleton('translator', static function () use ($appConfig, $rootPath): Translator {
            return new Translator(
                (string) ($appConfig['default_locale'] ?? 'pt-BR'),
                (string) ($appConfig['fallback_locale'] ?? 'pt-BR'),
                $rootPath . '/app/lang'
            );
        });

        $c->singleton('response', static fn (): Response => new Response());

        $c->singleton('events', static fn (): EventDispatcher => new EventDispatcher());

        $c->singleton('view', static function (Container $c): View {
            return new View($c->get('translator'));
        });

        $c->singleton('router', static fn (Container $c): Router => new Router($c));

        // Repositories.
        $c->singleton(
            SettingRepository::class,
            static fn (Container $c): SettingRepository => new SettingRepository($c->get('database'))
        );

        $c->singleton(
            UserRepository::class,
            static fn (Container $c): UserRepository => new UserRepository($c->get('database'))
        );

        // Services.
        $c->singleton(
            ConfigService::class,
            static fn (Container $c): ConfigService => new ConfigService($c)
        );

        $c->singleton(
            AuthService::class,
            static fn (Container $c): AuthService => new AuthService($c)
        );
    }

    /**
     * @param array<string, mixed> $appConfig
     */
    private function prepareEnvironment(array $appConfig): void
    {
        /** @var Logger $logger */
        $logger = $this->container->get('logger');
        $debug = (bool) ($appConfig['debug'] ?? false);

        (new ErrorHandler($logger, $debug))->register();

        $request = Request::fromGlobals();
        $this->container->instance('request', $request);

        /** @var Session $session */
        $session = $this->container->get('session');
        /** @var array<string, mixed> $sessionConfig */
        $sessionConfig = $appConfig['session'] ?? [];
        $session->start($sessionConfig, $request->isSecure());

        // Share common data with all views.
        /** @var View $view */
        $view = $this->container->get('view');
        /** @var Csrf $csrf */
        $csrf = $this->container->get('csrf');
        $view->share([
            'csrfToken' => $csrf->token(),
            'locale'    => (string) ($appConfig['default_locale'] ?? 'pt-BR'),
        ]);
    }
}
