<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\ActivityRepository;
use App\Services\AccessContext;
use App\Services\CrmDashboardService;
use App\Services\LeadService;

/**
 * Commercial dashboard controller. Read-only aggregates + pending tasks.
 */
final class CrmDashboardController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();

        $this->render('admin.crm.dashboard', [
            'title'      => __('crm.dashboard.title'),
            'activePath' => '/app/crm',
            'metrics'    => $this->dashboard()->metrics(),
            'tasks'      => $this->activities()->pendingTasks($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), 10),
            'statuses'   => LeadService::STATUSES,
        ], 'admin');
    }

    private function dashboard(): CrmDashboardService
    {
        /** @var CrmDashboardService $s */
        $s = $this->container->get(CrmDashboardService::class);

        return $s;
    }

    private function activities(): ActivityRepository
    {
        /** @var ActivityRepository $r */
        $r = $this->container->get(ActivityRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
