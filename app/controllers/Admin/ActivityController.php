<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\ActivityService;

/**
 * Activities/tasks controller. Redirects back to the referring lead/company.
 */
final class ActivityController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->service()->create($request->all());
        $this->session()->flash('status', ($result['ok'] ?? false) ? __('crm.activities.created') : __('errors.validation'));
        $this->redirectBack($request);
    }

    /**
     * @param array<string, string> $params
     */
    public function complete(Request $request, array $params = []): void
    {
        $this->service()->completeTask((int) ($params['id'] ?? 0));
        $this->session()->flash('status', __('crm.activities.completed'));
        $this->redirectBack($request);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $this->service()->delete((int) ($params['id'] ?? 0));
        $this->session()->flash('status', __('crm.activities.deleted'));
        $this->redirectBack($request);
    }

    private function redirectBack(Request $request): void
    {
        $leadId = (int) $request->input('lead_id', 0);
        $companyId = (int) $request->input('company_id', 0);
        if ($leadId > 0) {
            $this->redirect('/app/leads/' . $leadId);
        } elseif ($companyId > 0) {
            $this->redirect('/app/companies/' . $companyId);
        } else {
            $this->redirect('/app/leads');
        }
    }

    private function service(): ActivityService
    {
        /** @var ActivityService $s */
        $s = $this->container->get(ActivityService::class);

        return $s;
    }
}
