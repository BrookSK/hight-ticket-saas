<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Outreach\OutreachService;
use App\Services\Outreach\TemplateService;

/**
 * CRUD controller for outreach templates. Controls flow only.
 */
final class OutreachTemplateController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.templates.index', [
            'title'      => __('outreach.templates.title'),
            'activePath' => '/app/outreach/templates',
            'templates'  => $this->service()->listForContext(),
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.templates.form', [
            'title'      => __('outreach.templates.new'),
            'activePath' => '/app/outreach/templates',
            'template'   => [],
            'channels'   => TemplateService::CHANNELS,
            'kinds'      => TemplateService::KINDS,
            'variables'  => $this->outreach()->availableVariables(),
            'errors'     => [],
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->service()->create($request->all());
        if (!($result['ok'] ?? false)) {
            $this->render('admin.outreach.templates.form', [
                'title'      => __('outreach.templates.new'),
                'activePath' => '/app/outreach/templates',
                'template'   => $request->all(),
                'channels'   => TemplateService::CHANNELS,
                'kinds'      => TemplateService::KINDS,
                'variables'  => $this->outreach()->availableVariables(),
                'errors'     => $this->tr($result['errors'] ?? []),
                'invalid'    => $result['invalid'] ?? [],
            ], 'app', 422);

            return;
        }
        $this->log()->record('outreach_template_created', $this->context()->userId(), ['object_type' => 'outreach_template', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('outreach.templates.created'));
        $this->redirect('/app/outreach/templates');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $template = $this->service()->find((int) ($params['id'] ?? 0));
        if ($template === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }
        $this->render('admin.outreach.templates.form', [
            'title'      => __('outreach.templates.edit'),
            'activePath' => '/app/outreach/templates',
            'template'   => $template,
            'channels'   => TemplateService::CHANNELS,
            'kinds'      => TemplateService::KINDS,
            'variables'  => $this->outreach()->availableVariables(),
            'errors'     => [],
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->service()->update($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('outreach.errors.unknown_variable'));
            $this->redirect('/app/outreach/templates/' . $id . '/edit');

            return;
        }
        $this->log()->record('outreach_template_updated', $this->context()->userId(), ['object_type' => 'outreach_template', 'object_id' => $id]);
        $this->session()->flash('status', __('outreach.templates.updated'));
        $this->redirect('/app/outreach/templates');
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->delete($id)) {
            $this->log()->record('outreach_template_deleted', $this->context()->userId(), ['object_type' => 'outreach_template', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.templates.deleted'));
        }
        $this->redirect('/app/outreach/templates');
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

    private function service(): TemplateService
    {
        /** @var TemplateService $s */
        $s = $this->container->get(TemplateService::class);

        return $s;
    }

    private function outreach(): OutreachService
    {
        /** @var OutreachService $s */
        $s = $this->container->get(OutreachService::class);

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

