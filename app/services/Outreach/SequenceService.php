<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\OutreachEnrollmentRepository;
use App\Repositories\OutreachJobRepository;
use App\Repositories\OutreachSequenceRepository;
use App\Services\AccessContext;

/**
 * Drives follow-up sequences.
 *
 * A lead is enrolled in a sequence; the worker advances due enrollments, each
 * step preparing the next message. Sequences STOP automatically on the human
 * conditions defined by the spec: reply, meeting booked, deal won, or opt-out.
 * A step with stop_on_reply also halts the sequence once the lead replies.
 */
final class SequenceService extends Service
{
    public const STOP_REASONS = ['replied', 'meeting', 'won', 'opt_out', 'manual'];

    /**
     * Enroll a lead into a sequence (idempotent: one active enrollment per lead).
     *
     * @return array{ok:bool, id?:int, reason?:string}
     */
    public function enroll(int $sequenceId, int $leadId, ?int $contactId = null): array
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return ['ok' => false, 'reason' => 'no_owner'];
        }

        $sequence = $this->sequences()->findForContext($sequenceId, $ctx->ownerType(), $ownerId, $ctx->canSeeAll());
        if ($sequence === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        if ($this->enrollments()->activeForLead($leadId) !== null) {
            return ['ok' => false, 'reason' => 'already_enrolled'];
        }

        $steps = $this->sequences()->steps($sequenceId);
        if ($steps === []) {
            return ['ok' => false, 'reason' => 'no_steps'];
        }

        $firstDelay = (int) ($steps[0]['delay_days'] ?? 0);
        $nextRun = date('Y-m-d H:i:s', strtotime('+' . $firstDelay . ' days') ?: time());

        $id = $this->enrollments()->create([
            'owner_type'   => $ctx->ownerType(),
            'owner_id'     => $ownerId,
            'sequence_id'  => $sequenceId,
            'lead_id'      => $leadId,
            'contact_id'   => $contactId,
            'current_step' => 0,
            'status'       => 'active',
            'next_run_at'  => $nextRun,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Stop a lead's active sequences for one of the human conditions.
     */
    public function stopForLead(int $leadId, string $reason): int
    {
        $reason = in_array($reason, self::STOP_REASONS, true) ? $reason : 'manual';

        return $this->enrollments()->stopForLead($leadId, $reason);
    }

    /**
     * Advance one enrollment by preparing its current step's message and
     * scheduling the next. Called by the worker for each due enrollment.
     *
     * @param array<string, mixed> $enrollment
     * @return array{ok:bool, action:string, reason?:string}
     */
    public function advance(array $enrollment): array
    {
        $enrollmentId = (int) $enrollment['id'];
        $sequenceId = (int) $enrollment['sequence_id'];
        $leadId = (int) $enrollment['lead_id'];
        $nextStepOrder = (int) $enrollment['current_step'] + 1;

        $step = $this->sequences()->stepAt($sequenceId, $nextStepOrder);
        if ($step === null) {
            // No more steps: complete the sequence.
            $this->enrollments()->complete($enrollmentId);

            return ['ok' => true, 'action' => 'completed'];
        }

        // Prepare the step's message through the normal outreach flow (honours
        // approval + all guardrails). It becomes pending_approval or queued.
        $prepared = $this->outreach()->prepareContact($leadId, [
            'channel'     => (string) ($step['channel'] ?? 'whatsapp'),
            'template_id' => $step['template_id'] ?? null,
            'contact_id'  => $enrollment['contact_id'] ?? null,
        ]);

        // Schedule the following step regardless (it will stop if a condition
        // is met before then). If there is no following step, we complete on the
        // next tick.
        $following = $this->sequences()->stepAt($sequenceId, $nextStepOrder + 1);
        $nextRun = null;
        if ($following !== null) {
            $delay = (int) ($following['delay_days'] ?? 0);
            $nextRun = date('Y-m-d H:i:s', strtotime('+' . $delay . ' days') ?: time());
        }
        $this->enrollments()->advance($enrollmentId, $nextStepOrder, $nextRun);

        if ($following === null) {
            $this->enrollments()->complete($enrollmentId);
        }

        return ['ok' => (bool) ($prepared['ok'] ?? false), 'action' => 'advanced', 'reason' => $prepared['reason'] ?? null];
    }

    private function sequences(): OutreachSequenceRepository
    {
        /** @var OutreachSequenceRepository $r */
        $r = $this->container->get(OutreachSequenceRepository::class);

        return $r;
    }

    private function enrollments(): OutreachEnrollmentRepository
    {
        /** @var OutreachEnrollmentRepository $r */
        $r = $this->container->get(OutreachEnrollmentRepository::class);

        return $r;
    }

    private function outreach(): OutreachService
    {
        /** @var OutreachService $s */
        $s = $this->container->get(OutreachService::class);

        return $s;
    }

    private function jobs(): OutreachJobRepository
    {
        /** @var OutreachJobRepository $r */
        $r = $this->container->get(OutreachJobRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
