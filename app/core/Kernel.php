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
use App\Libraries\Http\HttpClient;
use App\Libraries\Http\SsrfGuard;
use App\Libraries\Prospecting\ProviderRegistry;
use App\Repositories\ActivityLogRepository;
use App\Repositories\ActivityRepository;
use App\Repositories\AuditDataRepository;
use App\Repositories\AuditRepository;
use App\Repositories\CampaignRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContactRepository;
use App\Repositories\DiscoveryResultRepository;
use App\Repositories\ExclusionListRepository;
use App\Repositories\LeadRepository;
use App\Repositories\OpportunityRuleRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\ProspectingJobRepository;
use App\Repositories\PlanRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Repositories\WaitlistActivityRepository;
use App\Repositories\WaitlistLeadRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\ActivityService;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\CompanyService;
use App\Services\ContactService;
use App\Services\CrmDashboardService;
use App\Services\LeadService;
use App\Services\ConfigService;
use App\Services\EmailTemplateService;
use App\Services\PdfService;
use App\Services\MailService;
use App\Services\PlanService;
use App\Services\Prospecting\CampaignService;
use App\Services\Prospecting\DiscoveryService;
use App\Services\Prospecting\EnrichmentService;
use App\Services\Prospecting\ExclusionService;
use App\Services\Prospecting\ProspectingConversionService;
use App\Services\Prospecting\ProspectingScoringService;
use App\Services\RateLimiter;
use App\Services\UserService;
use App\Services\WaitlistService;

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

        $c->singleton(
            WaitlistLeadRepository::class,
            static fn (Container $c): WaitlistLeadRepository => new WaitlistLeadRepository($c->get('database'))
        );

        $c->singleton(
            WaitlistActivityRepository::class,
            static fn (Container $c): WaitlistActivityRepository => new WaitlistActivityRepository($c->get('database'))
        );

        $c->singleton(
            PlanRepository::class,
            static fn (Container $c): PlanRepository => new PlanRepository($c->get('database'))
        );

        $c->singleton(
            PasswordResetRepository::class,
            static fn (Container $c): PasswordResetRepository => new PasswordResetRepository($c->get('database'))
        );

        $c->singleton(
            RoleRepository::class,
            static fn (Container $c): RoleRepository => new RoleRepository($c->get('database'))
        );

        $c->singleton(
            ActivityLogRepository::class,
            static fn (Container $c): ActivityLogRepository => new ActivityLogRepository($c->get('database'))
        );

        $c->singleton(
            AuditRepository::class,
            static fn (Container $c): AuditRepository => new AuditRepository($c->get('database'))
        );

        $c->singleton(
            AuditDataRepository::class,
            static fn (Container $c): AuditDataRepository => new AuditDataRepository($c->get('database'))
        );

        $c->singleton(
            CompanyRepository::class,
            static fn (Container $c): CompanyRepository => new CompanyRepository($c->get('database'))
        );

        $c->singleton(
            ContactRepository::class,
            static fn (Container $c): ContactRepository => new ContactRepository($c->get('database'))
        );

        $c->singleton(
            LeadRepository::class,
            static fn (Container $c): LeadRepository => new LeadRepository($c->get('database'))
        );

        $c->singleton(
            ActivityRepository::class,
            static fn (Container $c): ActivityRepository => new ActivityRepository($c->get('database'))
        );

        // Prospecting (Fase 4) repositories.
        $c->singleton(
            CampaignRepository::class,
            static fn (Container $c): CampaignRepository => new CampaignRepository($c->get('database'))
        );
        $c->singleton(
            DiscoveryResultRepository::class,
            static fn (Container $c): DiscoveryResultRepository => new DiscoveryResultRepository($c->get('database'))
        );
        $c->singleton(
            ProspectingJobRepository::class,
            static fn (Container $c): ProspectingJobRepository => new ProspectingJobRepository($c->get('database'))
        );
        $c->singleton(
            ExclusionListRepository::class,
            static fn (Container $c): ExclusionListRepository => new ExclusionListRepository($c->get('database'))
        );
        $c->singleton(
            OpportunityRuleRepository::class,
            static fn (Container $c): OpportunityRuleRepository => new OpportunityRuleRepository($c->get('database'))
        );

        // Discovery provider registry.
        $c->singleton('providerRegistry', static fn (): ProviderRegistry => new ProviderRegistry());
        $c->singleton(ProviderRegistry::class, static fn (Container $c): ProviderRegistry => $c->get('providerRegistry'));

        // SSRF guard (reusable by any outbound-request feature).
        $c->singleton('ssrfGuard', static fn (): SsrfGuard => new SsrfGuard());

        // HTTP client, configured from database-backed audit settings.
        $c->singleton('httpClient', static function (Container $c): HttpClient {
            /** @var ConfigService $config */
            $config = $c->get(ConfigService::class);
            $timeout = (int) ($config->get('audit_request_timeout', '15') ?? 15);
            $maxBytes = (int) ($config->get('audit_max_response_bytes', '3145728') ?? 3145728);
            $userAgent = (string) ($config->get('audit_user_agent', 'LRVWebAuditBot/1.0') ?? 'LRVWebAuditBot/1.0');

            return new HttpClient(
                $c->get('ssrfGuard'),
                $userAgent,
                min(10, $timeout),
                max(5, $timeout),
                $maxBytes,
                5
            );
        });

        // Services.
        $c->singleton(
            ConfigService::class,
            static fn (Container $c): ConfigService => new ConfigService($c)
        );

        $c->singleton(
            AuthService::class,
            static fn (Container $c): AuthService => new AuthService($c)
        );

        $c->singleton(
            AccessContext::class,
            static fn (Container $c): AccessContext => new AccessContext($c)
        );

        $c->singleton(
            AuditService::class,
            static fn (Container $c): AuditService => new AuditService($c)
        );

        $c->singleton(
            PdfService::class,
            static fn (Container $c): PdfService => new PdfService($c)
        );

        $c->singleton(
            CompanyService::class,
            static fn (Container $c): CompanyService => new CompanyService($c)
        );

        $c->singleton(
            ContactService::class,
            static fn (Container $c): ContactService => new ContactService($c)
        );

        $c->singleton(
            LeadService::class,
            static fn (Container $c): LeadService => new LeadService($c)
        );

        $c->singleton(
            ActivityService::class,
            static fn (Container $c): ActivityService => new ActivityService($c)
        );

        $c->singleton(
            CrmDashboardService::class,
            static fn (Container $c): CrmDashboardService => new CrmDashboardService($c)
        );

        // Prospecting (Fase 4) services.
        $c->singleton(EnrichmentService::class, static fn (Container $c): EnrichmentService => new EnrichmentService($c));
        $c->singleton(ProspectingScoringService::class, static fn (Container $c): ProspectingScoringService => new ProspectingScoringService($c));
        $c->singleton(CampaignService::class, static fn (Container $c): CampaignService => new CampaignService($c));
        $c->singleton(DiscoveryService::class, static fn (Container $c): DiscoveryService => new DiscoveryService($c));
        $c->singleton(ProspectingConversionService::class, static fn (Container $c): ProspectingConversionService => new ProspectingConversionService($c));
        $c->singleton(ExclusionService::class, static fn (Container $c): ExclusionService => new ExclusionService($c));

        $c->singleton(
            RateLimiter::class,
            static fn (Container $c): RateLimiter => new RateLimiter($c)
        );

        $c->singleton(
            ActivityLogService::class,
            static fn (Container $c): ActivityLogService => new ActivityLogService($c)
        );

        $c->singleton(
            EmailTemplateService::class,
            static fn (Container $c): EmailTemplateService => new EmailTemplateService($c)
        );

        $c->singleton(
            MailService::class,
            static fn (Container $c): MailService => new MailService($c)
        );

        $c->singleton(
            WaitlistService::class,
            static fn (Container $c): WaitlistService => new WaitlistService($c)
        );

        $c->singleton(
            PlanService::class,
            static fn (Container $c): PlanService => new PlanService($c)
        );

        $c->singleton(
            UserService::class,
            static fn (Container $c): UserService => new UserService($c)
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
