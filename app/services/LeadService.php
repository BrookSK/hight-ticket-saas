<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\AuditRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\LeadRepository;

/**
 * Lead (opportunity) business logic.
 *
 * Handles creation, updates, pipeline status changes (with history + automatic
 * timeline), assignment, win/loss outcomes and creating a lead from an audit.
 * All operations are owner-scoped through AccessContext.
 */
final class LeadService extends Service
{
    public const STATUSES = [
        'new', 'contact_started', 'contact_made', 'qualified',
        'proposal_sent', 'negotiation', 'won', 'lost', 'archived',
    ];
    public const TEMPERATURES = ['cold', 'warm', 'hot'];
    public const QUALIFICATIONS = ['unqualified', 'qualifying', 'qualified'];

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(array $input): array
    {
        $companyId = (int) ($input['company_id'] ?? 0);
        if ($companyId <= 0) {
            return ['ok' => false, 'errors' => ['company_id' => 'validation.required']];
        }

        $ctx = $this->context();
        $id = $this->leads()->create([
            'owner_type'          => $ctx->ownerType(),
            'owner_id'            => $ctx->ownerId(),
            'created_by'          => $ctx->userId(),
            'company_id'          => $companyId,
            'contact_id'          => $this->nullableInt($input['contact_id'] ?? null),
            'responsible_user_id' => $ctx->userId(),
            'title'               => $this->nt($input['title'] ?? null),
            'source'              => (string) ($input['source'] ?? 'manual'),
            'status'              => 'new',
            'temperature'         => in_array($input['temperature'] ?? '', self::TEMPERATURES, true) ? (string) $input['temperature'] : 'cold',
            'service_type'        => $this->nt($input['service_type'] ?? null),
            'estimated_value'     => $this->nullablePrice($input['estimated_value'] ?? null),
            'currency'            => strtoupper((string) ($input['currency'] ?? 'BRL')) ?: 'BRL',
            'probability'         => $this->nullableInt($input['probability'] ?? null),
            'expected_close_date' => $this->nt($input['expected_close_date'] ?? null),
            'qualification'       => in_array($input['qualification'] ?? '', self::QUALIFICATIONS, true) ? (string) $input['qualification'] : 'unqualified',
            'next_step'           => $this->nt($input['next_step'] ?? null),
            'next_contact_at'     => $this->nt($input['next_contact_at'] ?? null),
            'notes'               => $this->nt($input['notes'] ?? null),
        ]);

        $this->leads()->recordStatusHistory($id, $ctx->userId(), null, 'new');
        $this->companies()->touchActivity($companyId);
        $this->activity()->logSystem(__('crm.timeline.lead_created'), $id, $companyId);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Create a lead from an audit: find/create company by domain, link audit.
     *
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function createFromAudit(array $audit): array
    {
        $companyId = $this->companyService()->findOrCreateByDomain(
            (string) ($audit['normalized_url'] ?? $audit['url'] ?? ''),
            (string) ($audit['host'] ?? 'Empresa')
        );
        if ($companyId <= 0) {
            return ['ok' => false, 'errors' => ['company_id' => 'errors.generic']];
        }

        $result = $this->create([
            'company_id' => $companyId,
            'title'      => (string) ($audit['host'] ?? ''),
            'source'     => 'audit',
        ]);
        if (!($result['ok'] ?? false)) {
            return $result;
        }

        $leadId = (int) $result['id'];
        $this->leads()->linkAudit($leadId, (int) $audit['id']);
        $this->activity()->logSystem(__('crm.timeline.lead_from_audit'), $leadId, $companyId);

        return ['ok' => true, 'id' => $leadId];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input): array
    {
        $lead = $this->find($id);
        if ($lead === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }

        $this->leads()->update($id, [
            'contact_id'          => $this->nullableInt($input['contact_id'] ?? null),
            'responsible_user_id' => $this->nullableInt($input['responsible_user_id'] ?? $lead['responsible_user_id']),
            'title'               => $this->nt($input['title'] ?? null),
            'source'              => (string) ($input['source'] ?? $lead['source']),
            'temperature'         => in_array($input['temperature'] ?? '', self::TEMPERATURES, true) ? (string) $input['temperature'] : $lead['temperature'],
            'service_type'        => $this->nt($input['service_type'] ?? null),
            'estimated_value'     => $this->nullablePrice($input['estimated_value'] ?? null),
            'currency'            => strtoupper((string) ($input['currency'] ?? 'BRL')) ?: 'BRL',
            'probability'         => $this->nullableInt($input['probability'] ?? null),
            'expected_close_date' => $this->nt($input['expected_close_date'] ?? null),
            'qualification'       => in_array($input['qualification'] ?? '', self::QUALIFICATIONS, true) ? (string) $input['qualification'] : $lead['qualification'],
            'next_step'           => $this->nt($input['next_step'] ?? null),
            'next_contact_at'     => $this->nt($input['next_contact_at'] ?? null),
            'need'                => $this->nt($input['need'] ?? null),
            'problem'             => $this->nt($input['problem'] ?? null),
            'budget'              => $this->nt($input['budget'] ?? null),
            'authority'           => $this->nt($input['authority'] ?? null),
            'urgency'             => $this->nt($input['urgency'] ?? null),
            'current_solution'    => $this->nt($input['current_solution'] ?? null),
            'competitor'          => $this->nt($input['competitor'] ?? null),
            'objection'           => $this->nt($input['objection'] ?? null),
            'notes'               => $this->nt($input['notes'] ?? null),
        ]);

        return ['ok' => true];
    }

    /**
     * Change pipeline status with history + timeline. Returns success.
     */
    public function changeStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $lead = $this->find($id);
        if ($lead === null || $lead['status'] === $status) {
            return $lead !== null; // no-op if same status
        }

        $ctx = $this->context();
        $this->leads()->changeStatus($id, $status);
        $this->leads()->recordStatusHistory($id, $ctx->userId(), (string) $lead['status'], $status);
        $this->activity()->logSystem(
            __('crm.timeline.status_changed', ['status' => __('crm.status.' . $status)]),
            $id,
            (int) $lead['company_id']
        );

        return true;
    }

    public function assign(int $id, ?int $userId): bool
    {
        $lead = $this->find($id);
        if ($lead === null) {
            return false;
        }
        $this->leads()->assign($id, $userId);
        $this->activity()->logSystem(__('crm.timeline.responsible_changed'), $id, (int) $lead['company_id']);

        return true;
    }

    /**
     * @param array<string, mixed> $outcome
     */
    public function markWon(int $id, array $outcome): bool
    {
        $lead = $this->find($id);
        if ($lead === null) {
            return false;
        }
        $ctx = $this->context();
        $this->leads()->markWon($id, [
            'final_value'   => $this->nullablePrice($outcome['final_value'] ?? null),
            'won_service'   => $this->nt($outcome['won_service'] ?? null),
            'outcome_notes' => $this->nt($outcome['outcome_notes'] ?? null),
        ]);
        $this->leads()->recordStatusHistory($id, $ctx->userId(), (string) $lead['status'], 'won');
        $this->activity()->logSystem(__('crm.timeline.won'), $id, (int) $lead['company_id']);

        return true;
    }

    public function markLost(int $id, string $reason, ?string $notes): bool
    {
        $lead = $this->find($id);
        if ($lead === null) {
            return false;
        }
        $ctx = $this->context();
        $this->leads()->markLost($id, $reason, $notes);
        $this->leads()->recordStatusHistory($id, $ctx->userId(), (string) $lead['status'], 'lost');
        $this->activity()->logSystem(__('crm.timeline.lost'), $id, (int) $lead['company_id']);

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->leads()->softDelete($id);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->leads()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    private function nullableInt(mixed $v): ?int
    {
        return $v === null || $v === '' || $v === '0' || $v === 0 ? null : (int) $v;
    }

    private function nullablePrice(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = str_replace(['.', ','], ['', '.'], (string) $v);

        return is_numeric($n) ? (float) $n : null;
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
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

    private function companyService(): CompanyService
    {
        /** @var CompanyService $s */
        $s = $this->container->get(CompanyService::class);

        return $s;
    }

    private function activity(): ActivityService
    {
        /** @var ActivityService $s */
        $s = $this->container->get(ActivityService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
