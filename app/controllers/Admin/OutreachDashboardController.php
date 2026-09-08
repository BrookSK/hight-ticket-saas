<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\LeadTaskRepository;
use App\Services\AccessContext;
use App\Services\Outreach\OutreachMetricsService;
use App\Services\Outreach\SendPolicyService;

/**
 * Seller dashboard for the outreach area. Controls flow only.
 */
final class OutreachDashboardController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();

        $this->render('admin.outreach.dashboard', [
            'title'      => __('outreach.dashboard.title'),
            'activePath' => '/app/outreach',
            'metrics'    => $this->metrics()->overview(),
            'window'     => $this->policy()->windowCheck(),
            'requiresApproval' => $this->policy()->requiresApproval(),
            'tasks'      => $this->tasks()->pendingForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $ctx->userId()),
        ], 'app');
    }

    private function metrics(): OutreachMetricsService
    {
        /** @var OutreachMetricsService $s */
        $s = $this->container->get(OutreachMetricsService::class);

        return $s;
    }

    private function policy(): SendPolicyService
    {
        /** @var SendPolicyService $s */
        $s = $this->container->get(SendPolicyService::class);

        return $s;
    }

    private function tasks(): LeadTaskRepository
    {
        /** @var LeadTaskRepository $r */
        $r = $this->container->get(LeadTaskRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}

