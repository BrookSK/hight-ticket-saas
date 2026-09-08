<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PublicController;
use App\Controllers\PublicReportController;
use App\Controllers\SeoController;
use App\Controllers\WaitlistController;
use App\Controllers\WebhookController;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;

/**
 * Web routes.
 *
 * State-changing routes go through CsrfMiddleware; authenticated areas through
 * AuthMiddleware; admin areas add granular PermissionMiddleware (see admin.php).
 * There is intentionally NO migration route (migrations run manually only).
 */

return static function (Router $router): void {
    // Institutional site.
    $router->get('/', [PublicController::class, 'home']);
    $router->get('/recursos', [PublicController::class, 'features']);
    $router->get('/como-funciona', [PublicController::class, 'howItWorks']);
    $router->get('/planos', [PublicController::class, 'plansPage']);
    $router->get('/faq', [PublicController::class, 'faq']);
    $router->get('/contato', [PublicController::class, 'contact']);
    $router->get('/termos-de-uso', [PublicController::class, 'terms']);
    $router->get('/politica-de-privacidade', [PublicController::class, 'privacy']);

    // SEO artifacts.
    $router->get('/robots.txt', [SeoController::class, 'robots']);
    $router->get('/sitemap.xml', [SeoController::class, 'sitemap']);

    // Public commercial report (tokenized share link).
    $router->get('/report/{token}', [PublicReportController::class, 'show']);

    // Provider webhooks (public; secured by shared secret, no session/CSRF).
    $router->post('/webhooks/whatsapp', [WebhookController::class, 'whatsapp']);

    // Waitlist (public capture).
    $router->get('/lista-de-espera', [WaitlistController::class, 'show']);
    $router->post('/lista-de-espera', [WaitlistController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/lista-de-espera/sucesso', [WaitlistController::class, 'success']);

    // Authentication.
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Password recovery.
    $router->get('/recuperar-senha', [AuthController::class, 'showForgot']);
    $router->post('/recuperar-senha', [AuthController::class, 'sendReset'], [CsrfMiddleware::class]);
    $router->get('/redefinir-senha', [AuthController::class, 'showReset']);
    $router->post('/redefinir-senha', [AuthController::class, 'reset'], [CsrfMiddleware::class]);

    // Admin routes are registered separately.
    (require dirname(__DIR__) . '/routes/admin.php')($router);
};
