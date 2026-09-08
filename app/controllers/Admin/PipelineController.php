<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\LeadRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\LeadService;

/**
 * Pipeline (Kanban) controller. Renders leads grouped by pipeline stage and
 * handles moving a card to another stage (drag-and-drop desktop / select mobile).
 */
final class PipelineController extends Controller
{
    /** Pipeline columns (open stages, excluding archived). */
    private const COLUMNS = [
        'new', 'contact_started', 'contact_made', 'qualified',
        'proposal_sent', 'negotiation', 'won', 'lost',
    ];

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $leads = $this->leads()->forPipeline($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());

        $columns = array_fill_keys(self::COLUMNS, []);
        foreach ($leads as $lead) {
            $status = (string) $lead['status'];
            if (isset($columns[$status])) {
                $columns[$status][] = $lead;
            }
        }

        $this->render('admin.pipeline.index', [
            'title'      => __('crm.pipeline.title'),
            'activePath' => '/app/pipeline',
            'columns'    => $columns,
            'columnKeys' => self::COLUMNS,
        ], 'admin');
    }

    /**
     * Move a lead to another stage. Accepts JSON (drag-and-drop) or form.
     *
     * @param array<string, string> $params
     */
    public function move(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $status = (string) $request->input('status', '');

        $ok = $this->service()->changeStatus($id, $status);
        if ($ok) {
            $this->log()->record('pipeline_moved', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
        }

        if ($request->expectsJson()) {
            if ($ok) {
                $this->response()->success(__('crm.pipeline.moved'));
            } else {
                $this->response()->error(__('errors.generic'), [], 422);
            }

            return;
        }

        $this->session()->flash('status', $ok ? __('crm.pipeline.moved') : __('errors.generic'));
        $this->redirect('/app/pipeline');
    }

    private function service(): LeadService
    {
        /** @var LeadService $s */
        $s = $this->container->get(LeadService::class);

        return $s;
    }

    private function leads(): LeadRepository
    {
        /** @var LeadRepository $r */
        $r = $this->container->get(LeadRepository::class);

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
