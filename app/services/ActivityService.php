<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\ActivityRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\LeadRepository;

/**
 * Activity/timeline business logic.
 *
 * Creates user activities (notes, tasks, calls, meetings) and automatic system
 * timeline events. Every write is owner-scoped through the AccessContext.
 */
final class ActivityService extends Service
{
    public const TASK_STATUSES = ['pending', 'in_progress', 'done', 'cancelled'];
    public const TYPES = ['note', 'call', 'message', 'email', 'meeting', 'task', 'other'];

    /**
     * Record an automatic timeline event (is_system=1).
     */
    public function logSystem(string $title, ?int $leadId = null, ?int $companyId = null): void
    {
        $ctx = $this->context();
        $this->repository()->create([
            'owner_type'   => $ctx->ownerType(),
            'owner_id'     => $ctx->ownerId(),
            'company_id'   => $companyId,
            'contact_id'   => null,
            'lead_id'      => $leadId,
            'user_id'      => $ctx->userId(),
            'type'         => 'system',
            'title'        => $title,
            'description'  => null,
            'scheduled_at' => null,
            'completed_at' => null,
            'status'       => 'done',
            'is_system'    => 1,
        ]);
        $this->touchTargets($leadId, $companyId);
    }

    /**
     * Create a user activity/task.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(array $input): array
    {
        $type = in_array($input['type'] ?? '', self::TYPES, true) ? (string) $input['type'] : 'note';
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '' && $type !== 'note') {
            return ['ok' => false, 'errors' => ['title' => 'validation.required']];
        }

        $ctx = $this->context();
        $isTask = $type === 'task';
        $status = $isTask
            ? (in_array($input['status'] ?? '', self::TASK_STATUSES, true) ? (string) $input['status'] : 'pending')
            : 'done';

        $id = $this->repository()->create([
            'owner_type'   => $ctx->ownerType(),
            'owner_id'     => $ctx->ownerId(),
            'company_id'   => $this->nullableInt($input['company_id'] ?? null),
            'contact_id'   => $this->nullableInt($input['contact_id'] ?? null),
            'lead_id'      => $this->nullableInt($input['lead_id'] ?? null),
            'user_id'      => $ctx->userId(),
            'type'         => $type,
            'title'        => $title !== '' ? $title : null,
            'description'  => $this->nullableTrim($input['description'] ?? null),
            'scheduled_at' => $this->nullableTrim($input['scheduled_at'] ?? null),
            'completed_at' => $status === 'done' && !$isTask ? date('Y-m-d H:i:s') : null,
            'status'       => $status,
            'is_system'    => 0,
        ]);

        $this->touchTargets($this->nullableInt($input['lead_id'] ?? null), $this->nullableInt($input['company_id'] ?? null));

        return ['ok' => true, 'id' => $id];
    }

    public function completeTask(int $id): bool
    {
        $activity = $this->repository()->findForContext($id, $this->context()->ownerType(), $this->context()->ownerId(), $this->context()->canSeeAll());
        if ($activity === null) {
            return false;
        }
        $this->repository()->updateStatus($id, 'done');
        if (!empty($activity['lead_id'])) {
            $this->logSystem(__('crm.timeline.task_completed', ['title' => (string) ($activity['title'] ?? '')]), (int) $activity['lead_id'], $this->nullableInt($activity['company_id'] ?? null));
        }

        return true;
    }

    public function delete(int $id): bool
    {
        $activity = $this->repository()->findForContext($id, $this->context()->ownerType(), $this->context()->ownerId(), $this->context()->canSeeAll());
        if ($activity === null) {
            return false;
        }
        $this->repository()->softDelete($id);

        return true;
    }

    private function touchTargets(?int $leadId, ?int $companyId): void
    {
        if ($companyId !== null) {
            /** @var CompanyRepository $companies */
            $companies = $this->container->get(CompanyRepository::class);
            $companies->touchActivity($companyId);
        }
        if ($leadId !== null) {
            /** @var LeadRepository $leads */
            $leads = $this->container->get(LeadRepository::class);
            $leads->touchActivity($leadId);
        }
    }

    private function nullableInt(mixed $v): ?int
    {
        return $v === null || $v === '' || $v === '0' || $v === 0 ? null : (int) $v;
    }

    private function nullableTrim(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function repository(): ActivityRepository
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
