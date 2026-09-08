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
use App\Services\CompanyService;

/**
 * Companies controller (back-office CRM). Controls flow only; rules live in
 * CompanyService. All access is owner-scoped via AccessContext.
 */
final class CompanyController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $filters = [
            'status' => (string) $request->query('status', ''),
            'search' => (string) $request->query('search', ''),
        ];
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->companies()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters);

        $this->render('admin.companies.index', [
            'title'      => __('crm.companies.title'),
            'activePath' => '/app/companies',
            'companies'  => $this->companies()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters, $page, self::PER_PAGE),
            'filters'    => $filters,
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
        $this->render('admin.companies.form', [
            'title'      => __('crm.companies.new'),
            'activePath' => '/app/companies',
            'company'    => null,
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->service()->create($request->all());
        if (!($result['ok'] ?? false)) {
            $this->render('admin.companies.form', [
                'title'      => __('crm.companies.new'),
                'activePath' => '/app/companies',
                'company'    => $request->all(),
                'errors'     => $this->tr($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('company_created', $this->context()->userId(), ['object_type' => 'company', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('crm.companies.created'));
        $this->redirect('/app/companies/' . ($result['id'] ?? ''));
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $company = $this->service()->find((int) ($params['id'] ?? 0));
        if ($company === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $id = (int) $company['id'];
        $this->render('admin.companies.show', [
            'title'      => $company['trade_name'],
            'activePath' => '/app/companies',
            'company'    => $company,
            'contacts'   => $this->contacts()->forCompany($id),
            'leads'      => $this->leads()->forCompany($id),
            'activities' => $this->activities()->forCompany($id),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $company = $this->service()->find((int) ($params['id'] ?? 0));
        if ($company === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.companies.form', [
            'title'      => __('crm.companies.edit'),
            'activePath' => '/app/companies',
            'company'    => $company,
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
            $company = $request->all();
            $company['id'] = $id;
            $this->render('admin.companies.form', [
                'title'      => __('crm.companies.edit'),
                'activePath' => '/app/companies',
                'company'    => $company,
                'errors'     => $this->tr($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('company_updated', $this->context()->userId(), ['object_type' => 'company', 'object_id' => $id]);
        $this->session()->flash('status', __('crm.companies.updated'));
        $this->redirect('/app/companies/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function archive(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->archive($id)) {
            $this->log()->record('company_archived', $this->context()->userId(), ['object_type' => 'company', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.companies.archived'));
        }
        $this->redirect('/app/companies/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->delete($id)) {
            $this->log()->record('company_deleted', $this->context()->userId(), ['object_type' => 'company', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.companies.deleted'));
        }
        $this->redirect('/app/companies');
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

    private function service(): CompanyService
    {
        /** @var CompanyService $s */
        $s = $this->container->get(CompanyService::class);

        return $s;
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

    private function leads(): LeadRepository
    {
        /** @var LeadRepository $r */
        $r = $this->container->get(LeadRepository::class);

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
