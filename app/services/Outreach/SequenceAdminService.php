<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\OutreachSequenceRepository;
use App\Services\AccessContext;

/**
 * CRUD for follow-up sequences and their steps.
 *
 * Steps are replaced atomically on save (delete + re-add) so the ordering and
 * delays always reflect exactly what the user configured.
 */
final class SequenceAdminService extends Service
{
    /**
     * @param array<string, mixed> $input
     * @param array<int, array<string, mixed>> $steps
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(array $input, array $steps): array
    {
        if (trim((string) ($input['name'] ?? '')) === '') {
            return ['ok' => false, 'errors' => ['name' => 'validation.required']];
        }

        $ctx = $this->context();
        $channel = in_array($input['channel'] ?? '', TemplateService::CHANNELS, true) ? (string) $input['channel'] : 'whatsapp';
        $id = $this->repository()->create([
            'owner_type' => $ctx->ownerType(),
            'owner_id'   => $ctx->ownerId(),
            'name'       => trim((string) $input['name']),
            'channel'    => $channel,
            'is_active'  => empty($input['is_active']) ? 0 : 1,
            'created_by' => $ctx->userId(),
        ]);

        $this->saveSteps($id, $steps);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, array<string, mixed>> $steps
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input, array $steps): array
    {
        if ($this->find($id) === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }
        if (trim((string) ($input['name'] ?? '')) === '') {
            return ['ok' => false, 'errors' => ['name' => 'validation.required']];
        }

        $channel = in_array($input['channel'] ?? '', TemplateService::CHANNELS, true) ? (string) $input['channel'] : 'whatsapp';
        $this->repository()->update($id, [
            'name'      => trim((string) $input['name']),
            'channel'   => $channel,
            'is_active' => empty($input['is_active']) ? 0 : 1,
        ]);
        $this->saveSteps($id, $steps);

        return ['ok' => true];
    }

    public function delete(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->repository()->deleteSteps($id);
        $this->repository()->softDelete($id);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(): array
    {
        $ctx = $this->context();

        return $this->repository()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->repository()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(int $sequenceId): array
    {
        return $this->repository()->steps($sequenceId);
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     */
    private function saveSteps(int $sequenceId, array $steps): void
    {
        $this->repository()->deleteSteps($sequenceId);
        $order = 1;
        foreach ($steps as $step) {
            $this->repository()->addStep([
                'sequence_id'   => $sequenceId,
                'step_order'    => $order,
                'delay_days'    => max(0, (int) ($step['delay_days'] ?? 0)),
                'channel'       => in_array($step['channel'] ?? '', TemplateService::CHANNELS, true) ? (string) $step['channel'] : 'whatsapp',
                'template_id'   => !empty($step['template_id']) ? (int) $step['template_id'] : null,
                'stop_on_reply' => empty($step['stop_on_reply']) ? 0 : 1,
            ]);
            $order++;
        }
    }

    private function repository(): OutreachSequenceRepository
    {
        /** @var OutreachSequenceRepository $r */
        $r = $this->container->get(OutreachSequenceRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
