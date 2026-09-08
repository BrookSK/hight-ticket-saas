<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\ContactRepository;
use App\Repositories\OutreachMessageRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\LeadService;
use App\Services\Outreach\OutreachService;
use App\Services\Outreach\SequenceAdminService;
use App\Services\Outreach\SequenceService;
use App\Services\Outreach\SuppressionService;
use App\Services\Outreach\TemplateService;

/**
 * Main outreach controller: prepare contacts, outbox, approvals, opt-out and
 * sequence enrollment. Controls flow only; all rules live in services.
 */
final class OutreachController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Outbox listing.
     *
     * @param array<string, string> $params
     */
    public function outbox(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $filters = [
            'status'    => (string) $request->query('status', ''),
            'channel'   => (string) $request->query('channel', ''),
            'direction' => 'outbound',
        ];
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->messages()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters);

        $this->render('admin.outreach.outbox', [
            'title'      => __('outreach.outbox.title'),
            'activePath' => '/app/outreach/outbox',
            'messages'   => $this->messages()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters, $page, self::PER_PAGE),
            'filters'    => $filters,
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'      => $total,
        ], 'app');
    }

    /**
     * Prepare-contact form for a lead.
     *
     * @param array<string, string> $params
     */
    public function prepare(Request $request, array $params = []): void
    {
        $leadId = (int) ($params['lead'] ?? $request->query('lead_id', 0));
        $lead = $this->leads()->find($leadId);
        if ($lead === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.outreach.prepare', [
            'title'      => __('outreach.prepare.title'),
            'activePath' => '/app/outreach',
            'lead'       => $lead,
            'contacts'   => $this->contacts()->forCompany((int) $lead['company_id']),
            'templates'  => $this->templates()->listForContext(),
            'sequences'  => $this->sequenceAdmin()->listForContext(),
            'variables'  => $this->service()->availableVariables(),
            'channels'   => TemplateService::CHANNELS,
            'aiAvailable' => false,
        ], 'app');
    }

    /**
     * Store a prepared message (draft/pending_approval/scheduled). JSON-aware.
     *
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $leadId = (int) $request->input('lead_id', 0);
        $result = $this->service()->prepareContact($leadId, $request->all());

        if (!($result['ok'] ?? false)) {
            $reason = $result['reason'] ?? 'validation';
            $this->response()->error(__('outreach.errors.' . $reason), $result['errors'] ?? [], 422);

            return;
        }

        // Preview mode returns the rendered text without persisting.
        if (($result['status'] ?? '') === 'preview') {
            $this->response()->success('', ['preview' => $result['preview'] ?? ''], []);

            return;
        }

        $this->log()->record('outreach_prepared', $this->context()->userId(), ['object_type' => 'outreach_message', 'object_id' => $result['id'] ?? null]);
        $this->response()->success(__('outreach.prepare.saved'), ['id' => $result['id'] ?? null, 'status' => $result['status'] ?? '']);
    }

    /**
     * Approve a pending message and queue it for sending.
     *
     * @param array<string, string> $params
     */
    public function approve(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->service()->approveAndQueue($id);
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('outreach.errors.' . ($result['reason'] ?? 'not_pending')));
        } else {
            $this->log()->record('outreach_approved', $this->context()->userId(), ['object_type' => 'outreach_message', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.outbox.approved'));
        }
        $this->redirect('/app/outreach/outbox');
    }

    /**
     * @param array<string, string> $params
     */
    public function cancel(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->service()->cancel($id);
        if (($result['ok'] ?? false)) {
            $this->log()->record('outreach_cancelled', $this->context()->userId(), ['object_type' => 'outreach_message', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.outbox.cancelled'));
        } else {
            $this->session()->flash('status', __('outreach.errors.' . ($result['reason'] ?? 'not_found')));
        }
        $this->redirect('/app/outreach/outbox');
    }

    /**
     * Enroll a lead into a follow-up sequence.
     *
     * @param array<string, string> $params
     */
    public function enroll(Request $request, array $params = []): void
    {
        $leadId = (int) $request->input('lead_id', 0);
        $sequenceId = (int) $request->input('sequence_id', 0);
        $contactId = (int) $request->input('contact_id', 0) ?: null;
        $result = $this->sequences()->enroll($sequenceId, $leadId, $contactId);
        if (($result['ok'] ?? false)) {
            $this->log()->record('outreach_enrolled', $this->context()->userId(), ['object_type' => 'lead', 'object_id' => $leadId]);
            $this->session()->flash('status', __('outreach.sequences.enrolled'));
        } else {
            $this->session()->flash('status', __('outreach.errors.' . ($result['reason'] ?? 'not_found')));
        }
        $this->redirect('/app/leads/' . $leadId);
    }

    // ------------------------------------------------------------ suppression

    /**
     * @param array<string, string> $params
     */
    public function suppressions(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.suppressions', [
            'title'        => __('outreach.suppressions.title'),
            'activePath'   => '/app/outreach/suppressions',
            'suppressions' => $this->suppression()->listForContext(),
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function addSuppression(Request $request, array $params = []): void
    {
        $channel = (string) $request->input('channel', 'whatsapp');
        $value = (string) $request->input('value', '');
        if ($value !== '') {
            $this->suppression()->optOut($channel, $value, 'manual');
            $this->log()->record('outreach_suppression_added', $this->context()->userId(), ['object_type' => 'outreach_suppression']);
            $this->session()->flash('status', __('outreach.suppressions.added'));
        }
        $this->redirect('/app/outreach/suppressions');
    }

    /**
     * @param array<string, string> $params
     */
    public function removeSuppression(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $this->suppression()->remove($id);
        $this->log()->record('outreach_suppression_removed', $this->context()->userId(), ['object_type' => 'outreach_suppression', 'object_id' => $id]);
        $this->session()->flash('status', __('outreach.suppressions.removed'));
        $this->redirect('/app/outreach/suppressions');
    }

    private function service(): OutreachService
    {
        /** @var OutreachService $s */
        $s = $this->container->get(OutreachService::class);

        return $s;
    }

    private function sequences(): SequenceService
    {
        /** @var SequenceService $s */
        $s = $this->container->get(SequenceService::class);

        return $s;
    }

    private function sequenceAdmin(): SequenceAdminService
    {
        /** @var SequenceAdminService $s */
        $s = $this->container->get(SequenceAdminService::class);

        return $s;
    }

    private function suppression(): SuppressionService
    {
        /** @var SuppressionService $s */
        $s = $this->container->get(SuppressionService::class);

        return $s;
    }

    private function leads(): LeadService
    {
        /** @var LeadService $s */
        $s = $this->container->get(LeadService::class);

        return $s;
    }

    private function templates(): TemplateService
    {
        /** @var TemplateService $s */
        $s = $this->container->get(TemplateService::class);

        return $s;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function contacts(): ContactRepository
    {
        /** @var ContactRepository $r */
        $r = $this->container->get(ContactRepository::class);

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

