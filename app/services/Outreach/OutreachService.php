<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\ContactRepository;
use App\Repositories\OutreachJobRepository;
use App\Repositories\OutreachMessageRepository;
use App\Repositories\OutreachTemplateRepository;
use App\Services\AccessContext;

/**
 * Prepares outreach contacts (draft → outbox), applying the human-in-the-loop
 * approval flow and all anti-spam guardrails.
 *
 * Flow for prepareContact():
 *   1. Resolve recipient (contact phone/e-mail) and template.
 *   2. Build grounded template variables (never invented).
 *   3. Render the template (fails on unknown variables) and optionally refine
 *      with AI (falls back to the template when AI is unavailable).
 *   4. Enforce opt-out (hard) — window/rate/cooldown are enforced at send time.
 *   5. Deduplicate, then create the outbox message either as pending_approval
 *      (human-in-the-loop) or queued to send.
 */
final class OutreachService extends Service
{
    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, status?:string, errors?:array<string,string>, reason?:string, preview?:string}
     */
    public function prepareContact(int $leadId, array $input): array
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return ['ok' => false, 'errors' => ['owner' => 'errors.forbidden']];
        }

        $lead = $this->leads()->find($leadId);
        if ($lead === null) {
            return ['ok' => false, 'errors' => ['lead_id' => 'errors.not_found']];
        }

        $channel = ($input['channel'] ?? 'whatsapp') === 'email' ? 'email' : 'whatsapp';

        $contact = null;
        if (!empty($input['contact_id'])) {
            $contact = $this->contacts()->findForContext((int) $input['contact_id'], $ctx->ownerType(), $ownerId, $ctx->canSeeAll());
        }

        $to = $this->resolveRecipient($channel, $contact, $input);
        if ($to === null || $to === '') {
            return ['ok' => false, 'errors' => ['to_address' => 'outreach.errors.no_recipient']];
        }

        // Hard block: never contact a suppressed recipient.
        if ($this->suppression()->isSuppressed($channel, $to)) {
            return ['ok' => false, 'reason' => 'suppressed'];
        }

        $template = null;
        if (!empty($input['template_id'])) {
            $template = $this->templates()->findForContext((int) $input['template_id'], $ctx->ownerType(), $ownerId, $ctx->canSeeAll());
        }

        $rawBody = $template !== null ? (string) $template['body'] : (string) ($input['body'] ?? '');
        $subject = $template !== null ? ($template['subject'] ?? null) : ($input['subject'] ?? null);
        if (trim($rawBody) === '') {
            return ['ok' => false, 'errors' => ['body' => 'validation.required']];
        }

        $vars = $this->buildVariables($lead, $contact, $input);

        // Render — throws on unknown variable so we never send a broken message.
        try {
            $body = $this->renderer()->render($rawBody, $vars);
            $subject = $subject !== null ? $this->renderer()->render((string) $subject, $vars) : null;
        } catch (UnknownTemplateVariableException $e) {
            return ['ok' => false, 'reason' => 'unknown_variables', 'errors' => ['body' => implode(', ', $e->unknown)]];
        }

        // Optional AI refinement (fallback to template on failure/absence).
        if (!empty($input['use_ai'])) {
            $body = $this->ai()->draft($body, ['lead' => $lead, 'variables' => $vars]);
        }

        // Preview-only mode: return the rendered body without persisting.
        if (!empty($input['preview_only'])) {
            return ['ok' => true, 'status' => 'preview', 'preview' => $body];
        }

        // Deduplicate per campaign+recipient+template.
        $dedupeKey = $this->dedupeKey($leadId, $channel, $to, (int) ($input['campaign_id'] ?? 0), (int) ($input['template_id'] ?? 0));
        if ($this->messages()->existsByDedupe($ctx->ownerType(), $ownerId, $dedupeKey)) {
            return ['ok' => false, 'reason' => 'duplicate'];
        }

        $requireApproval = $this->policy()->requiresApproval() && empty($input['force_send']);
        $scheduledAt = $this->nt($input['scheduled_at'] ?? null);
        $status = $requireApproval ? 'pending_approval' : ($scheduledAt !== null ? 'scheduled' : 'draft');

        $messageId = $this->messages()->create([
            'owner_type'      => $ctx->ownerType(),
            'owner_id'        => $ownerId,
            'created_by'      => $ctx->userId(),
            'lead_id'         => $leadId,
            'company_id'      => $lead['company_id'] ?? null,
            'contact_id'      => $contact['id'] ?? null,
            'campaign_id'     => !empty($input['campaign_id']) ? (int) $input['campaign_id'] : null,
            'template_id'     => !empty($input['template_id']) ? (int) $input['template_id'] : null,
            'report_id'       => !empty($input['report_id']) ? (int) $input['report_id'] : null,
            'channel'         => $channel,
            'direction'       => 'outbound',
            'to_address'      => $to,
            'subject'         => $subject,
            'body'            => $body,
            'status'          => $status,
            'scheduled_at'    => $scheduledAt,
            'dedupe_key'      => $dedupeKey,
            'provider_msg_id' => null,
            'provider'        => null,
        ]);

        // When approval is not required, queue it for the worker to dispatch.
        if (!$requireApproval) {
            $this->jobs()->enqueue('send_message', $messageId, null, [], 3, $scheduledAt);
            $this->messages()->setStatus($messageId, 'scheduled');
            $status = 'scheduled';
        }

        return ['ok' => true, 'id' => $messageId, 'status' => $status];
    }

    /**
     * Approve a pending message: enqueue it for sending (optionally scheduled).
     *
     * @return array{ok:bool, reason?:string}
     */
    public function approveAndQueue(int $messageId): array
    {
        $ctx = $this->context();
        $message = $this->messages()->findForContext($messageId, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($message === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        if (!in_array((string) $message['status'], ['pending_approval', 'draft'], true)) {
            return ['ok' => false, 'reason' => 'not_pending'];
        }

        $this->messages()->setStatus($messageId, 'scheduled');
        $this->jobs()->enqueue('send_message', $messageId, null, [], 3, $this->nt($message['scheduled_at'] ?? null));

        return ['ok' => true];
    }

    /**
     * @return array{ok:bool, reason?:string}
     */
    public function cancel(int $messageId): array
    {
        $ctx = $this->context();
        $message = $this->messages()->findForContext($messageId, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($message === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        if (in_array((string) $message['status'], ['sent', 'delivered', 'read'], true)) {
            return ['ok' => false, 'reason' => 'already_sent'];
        }
        $this->messages()->setStatus($messageId, 'cancelled');

        return ['ok' => true];
    }

    /**
     * The variable names available for templates (for the editor/validation).
     *
     * @return array<int, string>
     */
    public function availableVariables(): array
    {
        return ['first_name', 'last_name', 'full_name', 'company', 'domain', 'city', 'report_link', 'sender_name'];
    }

    /**
     * Build grounded template variables from real data only (never invented).
     *
     * @param array<string, mixed> $lead
     * @param array<string, mixed>|null $contact
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function buildVariables(array $lead, ?array $contact, array $input): array
    {
        $first = (string) ($contact['first_name'] ?? '');
        $last = (string) ($contact['last_name'] ?? '');
        $full = trim($first . ' ' . $last);

        return [
            'first_name'  => $first,
            'last_name'   => $last,
            'full_name'   => $full !== '' ? $full : (string) ($lead['company_name'] ?? ''),
            'company'     => (string) ($lead['company_name'] ?? ''),
            'domain'      => (string) ($lead['company_domain'] ?? ''),
            'city'        => (string) ($contact['city'] ?? ($lead['city'] ?? '')),
            'report_link' => (string) ($input['report_link'] ?? ''),
            'sender_name' => (string) ($input['sender_name'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed>|null $contact
     * @param array<string, mixed> $input
     */
    private function resolveRecipient(string $channel, ?array $contact, array $input): ?string
    {
        if (!empty($input['to_address'])) {
            return trim((string) $input['to_address']);
        }
        if ($contact === null) {
            return null;
        }
        if ($channel === 'email') {
            return $this->nt($contact['email'] ?? null);
        }

        return $this->nt($contact['whatsapp'] ?? null) ?? $this->nt($contact['phone'] ?? null);
    }

    private function dedupeKey(int $leadId, string $channel, string $to, int $campaignId, int $templateId): string
    {
        return substr(hash('sha256', $leadId . '|' . $channel . '|' . $to . '|' . $campaignId . '|' . $templateId), 0, 64);
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function leads(): \App\Services\LeadService
    {
        /** @var \App\Services\LeadService $s */
        $s = $this->container->get(\App\Services\LeadService::class);

        return $s;
    }

    private function contacts(): ContactRepository
    {
        /** @var ContactRepository $r */
        $r = $this->container->get(ContactRepository::class);

        return $r;
    }

    private function templates(): OutreachTemplateRepository
    {
        /** @var OutreachTemplateRepository $r */
        $r = $this->container->get(OutreachTemplateRepository::class);

        return $r;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function jobs(): OutreachJobRepository
    {
        /** @var OutreachJobRepository $r */
        $r = $this->container->get(OutreachJobRepository::class);

        return $r;
    }

    private function renderer(): TemplateRenderer
    {
        /** @var TemplateRenderer $s */
        $s = $this->container->get(TemplateRenderer::class);

        return $s;
    }

    private function ai(): AIMessageService
    {
        /** @var AIMessageService $s */
        $s = $this->container->get(AIMessageService::class);

        return $s;
    }

    private function policy(): SendPolicyService
    {
        /** @var SendPolicyService $s */
        $s = $this->container->get(SendPolicyService::class);

        return $s;
    }

    private function suppression(): SuppressionService
    {
        /** @var SuppressionService $s */
        $s = $this->container->get(SuppressionService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
