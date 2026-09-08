<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\CampaignRepository;
use App\Repositories\DiscoveryResultRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Prospecting\CampaignService;

/**
 * Prospecting campaigns controller. Controls flow only; CampaignService holds
 * the rules. Owner-scoped via AccessContext. Discovery runs asynchronously.
 */
final class CampaignController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->campaigns()->countForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());

        $this->render('admin.prospecting.campaigns.index', [
            'title'      => __('prospecting.campaigns.title'),
            'activePath' => '/app/prospecting/campaigns',
            'campaigns'  => $this->campaigns()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $page, self::PER_PAGE),
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'      => $total,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.prospecting.campaigns.form', [
            'title'      => __('prospecting.campaigns.new'),
            'activePath' => '/app/prospecting/campaigns',
            'errors'     => [],
            'old'        => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->service()->create($request->all());

        if ($result['result'] === CampaignService::RESULT_INVALID) {
            $this->render('admin.prospecting.campaigns.form', [
                'title'      => __('prospecting.campaigns.new'),
                'activePath' => '/app/prospecting/campaigns',
                'errors'     => ['name' => __('validation.required', ['attribute' => __('prospecting.campaigns.name')])],
                'old'        => $request->all(),
            ], 'admin', 422);

            return;
        }
        if ($result['result'] === CampaignService::RESULT_LIMIT) {
            $this->render('admin.prospecting.campaigns.form', [
                'title'      => __('prospecting.campaigns.new'),
                'activePath' => '/app/prospecting/campaigns',
                'errors'     => ['name' => __('prospecting.campaigns.limit_reached')],
                'old'        => $request->all(),
            ], 'admin', 429);

            return;
        }

        $this->log()->record('campaign_created', $this->context()->userId(), ['object_type' => 'campaign', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('prospecting.campaigns.created'));
        $this->redirect('/app/prospecting/campaigns/' . ($result['id'] ?? ''));
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $campaign = $this->service()->find((int) ($params['id'] ?? 0));
        if ($campaign === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.prospecting.campaigns.show', [
            'title'      => $campaign['name'],
            'activePath' => '/app/prospecting/campaigns',
            'campaign'   => $campaign,
            'funnel'     => $this->results()->funnelForCampaign((int) $campaign['id']),
        ], 'admin');
    }

    /**
     * Progress polling endpoint (JSON), scoped.
     *
     * @param array<string, string> $params
     */
    public function progress(Request $request, array $params = []): void
    {
        $campaign = $this->service()->find((int) ($params['id'] ?? 0));
        if ($campaign === null) {
            $this->response()->error(__('errors.not_found'), [], 404);

            return;
        }

        $this->response()->success('', [
            'status'     => $campaign['status'],
            'progress'   => (int) $campaign['progress'],
            'step'       => $campaign['current_step'],
            'discovered' => (int) $campaign['count_discovered'],
            'audited'    => (int) $campaign['count_audited'],
            'qualified'  => (int) $campaign['count_qualified'],
            'finished'   => in_array($campaign['status'], ['completed', 'partial', 'failed', 'cancelled'], true),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function run(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->run($id)) {
            $this->log()->record('campaign_started', $this->context()->userId(), ['object_type' => 'campaign', 'object_id' => $id]);
            $this->session()->flash('status', __('prospecting.campaigns.started'));
        }
        $this->redirect('/app/prospecting/campaigns/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function pause(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->pause($id)) {
            $this->session()->flash('status', __('prospecting.campaigns.paused'));
        }
        $this->redirect('/app/prospecting/campaigns/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function cancel(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->cancel($id)) {
            $this->session()->flash('status', __('prospecting.campaigns.cancelled'));
        }
        $this->redirect('/app/prospecting/campaigns/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->delete($id)) {
            $this->log()->record('campaign_deleted', $this->context()->userId(), ['object_type' => 'campaign', 'object_id' => $id]);
            $this->session()->flash('status', __('prospecting.campaigns.deleted'));
        }
        $this->redirect('/app/prospecting/campaigns');
    }

    private function service(): CampaignService
    {
        /** @var CampaignService $s */
        $s = $this->container->get(CampaignService::class);

        return $s;
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

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $l */
        $l = $this->container->get(ActivityLogService::class);

        return $l;
    }
}
