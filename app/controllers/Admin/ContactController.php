<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\CompanyService;
use App\Services\ContactService;

/**
 * Contacts controller. Contacts belong to a company; access is validated
 * through the company (owner-scoped) before any mutation.
 */
final class ContactController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $companyId = (int) ($params['company'] ?? 0);
        if ($this->companies()->find($companyId) === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $result = $this->contacts()->create($companyId, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('validation.required', ['attribute' => __('crm.contacts.name')]));
        } else {
            $this->log()->record('contact_created', $this->context()->userId(), ['object_type' => 'contact', 'object_id' => $result['id'] ?? null]);
            $this->session()->flash('status', __('crm.contacts.created'));
        }

        $this->redirect('/app/companies/' . $companyId);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $contact = $this->contacts()->find($id);
        if ($contact === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $result = $this->contacts()->update($id, $request->all());
        if ($result['ok'] ?? false) {
            $this->log()->record('contact_updated', $this->context()->userId(), ['object_type' => 'contact', 'object_id' => $id]);
            $this->session()->flash('status', __('crm.contacts.updated'));
        }

        $this->redirect('/app/companies/' . (int) $contact['company_id']);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $contact = $this->contacts()->find($id);
        if ($contact === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->contacts()->delete($id);
        $this->log()->record('contact_deleted', $this->context()->userId(), ['object_type' => 'contact', 'object_id' => $id]);
        $this->session()->flash('status', __('crm.contacts.deleted'));
        $this->redirect('/app/companies/' . (int) $contact['company_id']);
    }

    private function contacts(): ContactService
    {
        /** @var ContactService $s */
        $s = $this->container->get(ContactService::class);

        return $s;
    }

    private function companies(): CompanyService
    {
        /** @var CompanyService $s */
        $s = $this->container->get(CompanyService::class);

        return $s;
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
