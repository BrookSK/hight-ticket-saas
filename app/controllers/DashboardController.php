<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AuthService;

/**
 * Admin dashboard controller.
 *
 * Controls flow only: renders the authenticated dashboard.
 */
final class DashboardController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);
        $user = $auth->user();

        $this->render('dashboard.index', [
            'title'    => __('common.nav.dashboard'),
            'userName' => $user['name'] ?? null,
        ], 'app');
    }
}
