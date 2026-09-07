<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\WaitlistLeadRepository;
use App\Services\AuthService;
use App\Services\WaitlistService;

/**
 * Super Admin dashboard.
 *
 * Controls flow only: gathers waitlist metrics via the repository and renders
 * the dashboard. No business logic or SQL here.
 */
final class DashboardController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $leads = $this->leads();

        $byStatus = [];
        foreach (WaitlistService::STATUSES as $status) {
            $byStatus[$status] = $leads->countByStatus($status);
        }

        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);
        $user = $auth->user();

        $this->render('admin.dashboard', [
            'title'      => __('admin.dashboard.title'),
            'userName'   => $user['name'] ?? null,
            'total'      => $leads->total(),
            'new'        => $leads->countByStatus('new'),
            'contacted'  => $leads->countByStatus('contacted'),
            'last7'      => $leads->countSince(7),
            'last30'     => $leads->countSince(30),
            'byStatus'   => $byStatus,
            'topSources' => $leads->topSources(5),
        ], 'admin');
    }

    private function leads(): WaitlistLeadRepository
    {
        /** @var WaitlistLeadRepository $repository */
        $repository = $this->container->get(WaitlistLeadRepository::class);

        return $repository;
    }
}
