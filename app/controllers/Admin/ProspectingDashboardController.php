<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\CampaignRepository;
use App\Repositories\DiscoveryResultRepository;
use App\Repositories\ExclusionListRepository;
use App\Services\AccessContext;
use App\Services\Prospecting\ExclusionService;

/**
 * Prospecting dashboard + exclusion list management.
 */
final class ProspectingDashboardController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();

        $this->render('admin.prospecting.dashboard', [
            'title'      => __('prospecting.dashboard.title'),
            'activePath' => '/app/prospecting',
            'campaigns'  => $this->campaigns()->totalForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll()),
            'newOpportunities' => $this->results()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), ['status' => 'qualified']),
            'hotOpportunities' => $this->results()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), ['status' => 'qualified', 'priority' => 'high']),
            'converted'  => $this->results()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), ['status' => 'converted']),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function exclusions(Request $request, array $params = []): void
    {
        $this->render('admin.prospecting.exclusions', [
            'title'      => __('prospecting.exclusion.title'),
            'activePath' => '/app/prospecting/exclusions',
            'items'      => $this->exclusionService()->list(),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function addExclusion(Request $request, array $params = []): void
    {
        $this->exclusionService()->add(
            (string) $request->input('type', 'domain'),
            (string) $request->input('value', ''),
            $request->input('reason')
        );
        $this->session()->flash('status', __('prospecting.exclusion.added'));
        $this->redirect('/app/prospecting/exclusions');
    }

    /**
     * @param array<string, string> $params
     */
    public function removeExclusion(Request $request, array $params = []): void
    {
        $this->exclusionService()->remove((int) ($params['id'] ?? 0));
        $this->session()->flash('status', __('prospecting.exclusion.removed'));
        $this->redirect('/app/prospecting/exclusions');
    }

    private function campaigns(): CampaignRepository
    {
        /** @var CampaignRepository $r */
        $r = $this->container->get(CampaignRepository::class);

        return $r;
    }

    private function results(): DiscoveryResultRepository
    {
        /** @var DiscoveryResultRepository $r */
        $r = $this->container->get(DiscoveryResultRepository::class);

        return $r;
    }

    private function exclusionService(): ExclusionService
    {
        /** @var ExclusionService $s */
        $s = $this->container->get(ExclusionService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
