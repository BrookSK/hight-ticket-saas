<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Outreach\SequenceAdminService;
use App\Services\Outreach\TemplateService;

/**
 * CRUD controller for follow-up sequences. Controls flow only.
 */
final class OutreachSequenceController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.sequences.index', [
            'title'      => __('outreach.sequences.title'),
            'activePath' => '/app/outreach/sequences',
            'sequences'  => $this->service()->listForContext(),
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.sequences.form', [
            'title'      => __('outreach.sequences.new'),
            'activePath' => '/app/outreach/sequences',
            'sequence'   => [],
            'steps'      => [],
            'templates'  => $this->templates()->listForContext(),
            'channels'   => TemplateService::CHANNELS,
            'errors'     => [],
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $steps = $this->readSteps($request);
        $result = $this->service()->create($request->all(), $steps);
        if (!($result['ok'] ?? false)) {
            $this->render('admin.outreach.sequences.form', [
                'title'      => __('outreach.sequences.new'),
                'activePath' => '/app/outreach/sequences',
                'sequence'   => $request->all(),
                'steps'      => $steps,
                'templates'  => $this->templates()->listForContext(),
                'channels'   => TemplateService::CHANNELS,
                'errors'     => $this->tr($result['errors'] ?? []),
            ], 'app', 422);

            return;
        }
        $this->log()->record('outreach_sequence_created', $this->context()->userId(), ['object_type' => 'outreach_sequence', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('outreach.sequences.created'));
        $this->redirect('/app/outreach/sequences');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $sequence = $this->service()->find($id);
        if ($sequence === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }
        $this->render('admin.outreach.sequences.form', [
            'title'      => __('outreach.sequences.edit'),
            'activePath' => '/app/outreach/sequences',
            'sequence'   => $sequence,
            'steps'      => $this->service()->steps($id),
            'templates'  => $this->templates()->listForContext(),
            'channels'   => TemplateService::CHANNELS,
            'errors'     => [],
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $steps = $this->readSteps($request);
        $result = $this->service()->update($id, $request->all(), $steps);
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('errors.validation'));
            $this->redirect('/app/outreach/sequences/' . $id . '/edit');

            return;
        }
        $this->log()->record('outreach_sequence_updated', $this->context()->userId(), ['object_type' => 'outreach_sequence', 'object_id' => $id]);
        $this->session()->flash('status', __('outreach.sequences.updated'));
        $this->redirect('/app/outreach/sequences');
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->delete($id)) {
            $this->log()->record('outreach_sequence_deleted', $this->context()->userId(), ['object_type' => 'outreach_sequence', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.sequences.deleted'));
        }
        $this->redirect('/app/outreach/sequences');
    }

    /**
     * Read the repeatable steps array from the form.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readSteps(Request $request): array
    {
        $raw = $request->input('steps', []);
        if (!is_array($raw)) {
            return [];
        }
        $steps = [];
        foreach ($raw as $step) {
            if (!is_array($step)) {
                continue;
            }
            $steps[] = [
                'delay_days'    => (int) ($step['delay_days'] ?? 0),
                'channel'       => (string) ($step['channel'] ?? 'whatsapp'),
                'template_id'   => !empty($step['template_id']) ? (int) $step['template_id'] : null,
                'stop_on_reply' => !empty($step['stop_on_reply']),
            ];
        }

        return $steps;
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

    private function service(): SequenceAdminService
    {
        /** @var SequenceAdminService $s */
        $s = $this->container->get(SequenceAdminService::class);

        return $s;
    }

    private function templates(): TemplateService
    {
        /** @var TemplateService $s */
        $s = $this->container->get(TemplateService::class);

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

