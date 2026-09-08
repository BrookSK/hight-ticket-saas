<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\ActivityRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ContactRepository;
use App\Repositories\LeadRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\LeadService;

/**
 * Leads (opportunities) controller. Controls flow only; LeadService holds the
 * rules. All access is owner-scoped via AccessContext.
 */
final class LeadController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $filters = $this->readFilters($request);
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->leads()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters);

        $this->render('admin.leads.index', [
            'title'      => __('crm.leads.title'),
            'activePath' => '/app/leads',
            'leads'      => $this->leads()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters, $page, self::PER_PAGE),
            'filters'    => $filters,
            'statuses'   => LeadService::STATUSES,
            'temperatures' => LeadService::TEMPERATURES,
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
        $ctx = $this->context();
        $this->render('admin.leads.form', [
            'title'       => __('crm.leads.new'),
            'activePath'  => '/app/leads',
            'lead'        => ['company_id' => (int) $request->query('company_id', '0')],
            'companies'   => $this->companies()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), [], 1, 200),
            'errors'      => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        // Validate the chosen company belongs to the context.
        $companyId = (int) $request->input('company_id', 0);
        if ($this->companyOwned($companyId) === false) {
            $this->response()->html('<h1>' . e(__('errors.forbidden')) . '</h1>', 403);

            return;
        }

        $result = $this->service()->create($request->all());
        if (!($result['ok'] ?? false)) {
            $ctx = $this->context();
            $this->render('admin.leads.form', [
                'title'      => __('crm.leads.new'),
                'activePath' => '/app/leads',
                'lead'       => $request->all(),
                'companies'  => $this->companies()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), [], 1, 200),
                'errors'     => $this->tr($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('lead_created', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('crm.leads.created'));
        $this->redirect('/app/leads/' . ($result['id'] ?? ''));
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $lead = $this->service()->find((int) ($params['id'] ?? 0));
        if ($lead === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $id = (int) $lead['id'];
        $this->render('admin.leads.show', [
            'title'        => __('crm.leads.title'),
            'activePath'   => '/app/leads',
            'lead'         => $lead,
            'contacts'     => $this->contacts()->forCompany((int) $lead['company_id']),
            'audits'       => $this->leads()->auditsForLead($id),
            'activities'   => $this->activities()->forLead($id),
            'statuses'     => LeadService::STATUSES,
            'temperatures' => LeadService::TEMPERATURES,
            'qualifications' => LeadService::QUALIFICATIONS,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $lead = $this->service()->find((int) ($params['id'] ?? 0));
        if ($lead === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.leads.form', [
            'title'      => __('crm.leads.edit'),
            'activePath' => '/app/leads',
            'lead'       => $lead,
            'companies'  => [],
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->service()->update($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('errors.validation'));
        } else {
            $this->log()->record('lead_updated', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.updated'));
        }
        $this->redirect('/app/leads/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function changeStatus(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $status = (string) $request->input('status', '');
        if ($this->service()->changeStatus($id, $status)) {
            $this->log()->record('lead_status_changed', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.status_updated'));
        }
        $this->redirect('/app/leads/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function assign(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $userId = (int) $request->input('responsible_user_id', 0) ?: null;
        if ($this->service()->assign($id, $userId)) {
            $this->log()->record('lead_assigned', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.assigned'));
        }
        $this->redirect('/app/leads/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function win(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->markWon($id, [
            'final_value'   => $request->input('final_value'),
            'won_service'   => $request->input('won_service'),
            'outcome_notes' => $request->input('outcome_notes'),
        ])) {
            $this->log()->record('lead_won', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.won_message'));
        }
        $this->redirect('/app/leads/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function lose(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $reason = (string) $request->input('loss_reason', 'other');
        if ($this->service()->markLost($id, $reason, $request->input('outcome_notes'))) {
            $this->log()->record('lead_lost', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.lost_message'));
        }
        $this->redirect('/app/leads/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->delete($id)) {
            $this->log()->record('lead_deleted', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.leads.deleted'));
        }
        $this->redirect('/app/leads');
    }

    /**
     * @return array<string, mixed>
     */
    private function readFilters(Request $request): array
    {
        $filters = [
            'status'       => (string) $request->query('status', ''),
            'temperature'  => (string) $request->query('temperature', ''),
            'service_type' => (string) $request->query('service_type', ''),
            'source'       => (string) $request->query('source', ''),
            'search'       => (string) $request->query('search', ''),
        ];
        $scoreBand = (string) $request->query('score_band', '');
        if ($scoreBand !== '' && preg_match('/^(\d+)-(\d+)$/', $scoreBand, $m)) {
            $filters['score_min'] = (int) $m[1];
            $filters['score_max'] = (int) $m[2];
            $filters['score_band'] = $scoreBand;
        }

        return $filters;
    }

    private function companyOwned(int $companyId): bool
    {
        /** @var \App\Services\CompanyService $companies */
        $companies = $this->container->get(\App\Services\CompanyService::class);

        return $companies->find($companyId) !== null;
    }

    /**
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function tr(array $errors): array
    {
        $out = [];
        foreach ($errors as $f => $k) {
            $out[$f] = __($k, ['attribute' => $f]);
        }

        return $out;
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

    private function companies(): CompanyRepository
    {
        /** @var CompanyRepository $r */
        $r = $this->container->get(CompanyRepository::class);

        return $r;
    }

    private function contacts(): ContactRepository
    {
        /** @var ContactRepository $r */
        $r = $this->container->get(ContactRepository::class);

        return $r;
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

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $l */
        $l = $this->container->get(ActivityLogService::class);

        return $l;
    }
}
