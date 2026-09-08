<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\AuditDataRepository;
use App\Repositories\AuditRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\AuditService;
use App\Services\ConfigService;
use App\Services\PdfService;

/**
 * Audit module controller (back-office).
 *
 * Controls flow only. All access is scoped through AccessContext so a user can
 * only see their own audits (Super Admin sees all). Heavy processing happens in
 * the CLI worker — this controller only creates/queues and reads results.
 */
final class AuditController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->audits()->countForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());

        $this->render('admin.audits.index', [
            'title'      => __('audit.title'),
            'activePath' => '/app/audits',
            'audits'     => $this->audits()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $page, self::PER_PAGE),
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'      => $total,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.audits.create', [
            'title'      => __('audit.new'),
            'activePath' => '/app/audits',
            'errors'     => [],
            'old'        => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $input = [
            'url'       => (string) $request->input('url', ''),
            'scope'     => (string) $request->input('scope', 'homepage'),
            'max_pages' => (int) $request->input('max_pages', 20),
            'max_depth' => (int) $request->input('max_depth', 1),
        ];

        $result = $this->service()->createAudit($input);

        if ($result['result'] === AuditService::RESULT_INVALID_URL) {
            $this->render('admin.audits.create', [
                'title'      => __('audit.new'),
                'activePath' => '/app/audits',
                'errors'     => ['url' => __('audit.errors.invalid_url')],
                'old'        => $input,
            ], 'admin', 422);

            return;
        }

        if ($result['result'] === AuditService::RESULT_RATE_LIMITED) {
            $this->render('admin.audits.create', [
                'title'      => __('audit.new'),
                'activePath' => '/app/audits',
                'errors'     => ['url' => __('audit.errors.rate_limited')],
                'old'        => $input,
            ], 'admin', 429);

            return;
        }

        $this->log()->record('audit_created', $this->context()->userId(), [
            'object_type' => 'audit',
            'object_id'   => $result['auditId'] ?? null,
        ]);
        $this->session()->flash('status', __('audit.queued_message'));
        $this->redirect('/app/audits/' . ($result['auditId'] ?? ''));
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $audit = $this->authorizeAudit((int) ($params['id'] ?? 0));
        if ($audit === null) {
            return;
        }

        $id = (int) $audit['id'];

        $this->render('admin.audits.show', [
            'title'         => __('audit.title'),
            'activePath'    => '/app/audits',
            'audit'         => $audit,
            'issues'        => $this->data()->issues($id),
            'severityCounts' => $this->data()->issueCountsBySeverity($id),
            'metrics'       => $this->data()->metrics($id),
            'technologies'  => $this->data()->technologies($id),
            'contacts'      => $this->data()->contacts($id),
            'pages'         => $this->data()->pages($id),
            'summary'       => $this->data()->getResult($id, 'summary'),
        ], 'admin');
    }

    /**
     * Progress polling endpoint (JSON). Scoped to the access context.
     *
     * @param array<string, string> $params
     */
    public function progress(Request $request, array $params = []): void
    {
        $audit = $this->authorizeAudit((int) ($params['id'] ?? 0), json: true);
        if ($audit === null) {
            return;
        }

        $this->response()->success('', [
            'status'       => $audit['status'],
            'progress'     => (int) $audit['progress'],
            'current_step' => $audit['current_step'],
            'pages'        => (int) $audit['pages_crawled'],
            'score'        => $audit['score_overall'] !== null ? (int) $audit['score_overall'] : null,
            'finished'     => in_array($audit['status'], ['completed', 'partial', 'failed', 'cancelled'], true),
        ]);
    }

    /**
     * Render the report (HTML print-ready) or stream a PDF when ?pdf=1 and the
     * PDF engine is available. Report structure is a single source of truth.
     *
     * @param array<string, string> $params
     */
    public function report(Request $request, array $params = []): void
    {
        $audit = $this->authorizeAudit((int) ($params['id'] ?? 0));
        if ($audit === null) {
            return;
        }

        if (!in_array($audit['status'], ['completed', 'partial'], true)) {
            $this->response()->html('<h1>' . e(__('audit.report_not_ready')) . '</h1>', 409);

            return;
        }

        $id = (int) $audit['id'];
        $config = $this->config();
        $wantsPdf = (string) $request->query('pdf', '') === '1';

        /** @var PdfService $pdf */
        $pdf = $this->container->get(PdfService::class);

        $data = [
            'audit'          => $audit,
            'issues'         => $this->data()->issues($id),
            'severityCounts' => $this->data()->issueCountsBySeverity($id),
            'technologies'   => $this->data()->technologies($id),
            'summary'        => $this->data()->getResult($id, 'summary'),
            'brandName'      => (string) ($config->get('system_name', 'LRV Web') ?? 'LRV Web'),
            'brandLogo'      => $config->get('site_logo'),
            'brandContact'   => $config->get('site_email'),
            'pdfMode'        => $wantsPdf && $pdf->isPdfEngineAvailable(),
        ];

        $html = $this->view()->render('audits.report', $data);
        $filename = 'auditoria-' . preg_replace('/[^a-z0-9]+/i', '-', (string) $audit['host']) . '-' . $id . '.pdf';

        if ($wantsPdf && $pdf->stream($html, $filename)) {
            $this->log()->record('audit_exported', $this->context()->userId(), [
                'object_type' => 'audit', 'object_id' => $id,
            ]);

            return;
        }

        // Fallback / default: print-ready HTML.
        $this->response()->html($html);
    }

    /**
     * Transform an audit into a commercial lead (creates/links company + lead).
     *
     * @param array<string, string> $params
     */
    public function transformToLead(Request $request, array $params = []): void
    {
        $audit = $this->authorizeAudit((int) ($params['id'] ?? 0));
        if ($audit === null) {
            return;
        }

        /** @var \App\Services\LeadService $leads */
        $leads = $this->container->get(\App\Services\LeadService::class);
        $result = $leads->createFromAudit($audit);

        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('audit.transform_failed'));
            $this->redirect('/app/audits/' . (int) $audit['id']);

            return;
        }

        $this->log()->record('lead_created_from_audit', $this->context()->userId(), [
            'object_type' => 'lead', 'object_id' => $result['id'] ?? null,
        ]);
        $this->session()->flash('status', __('audit.transform_success'));
        $this->redirect('/app/leads/' . ($result['id'] ?? ''));
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $audit = $this->authorizeAudit((int) ($params['id'] ?? 0));
        if ($audit === null) {
            return;
        }

        $this->audits()->softDelete((int) $audit['id']);
        $this->log()->record('audit_deleted', $this->context()->userId(), [
            'object_type' => 'audit',
            'object_id'   => (int) $audit['id'],
        ]);
        $this->session()->flash('status', __('audit.deleted'));
        $this->redirect('/app/audits');
    }

    /**
     * Authorize access to an audit within the current context.
     * Returns the audit row, or null after sending a 403/404 response.
     *
     * @return array<string, mixed>|null
     */
    private function authorizeAudit(int $id, bool $json = false): ?array
    {
        $ctx = $this->context();
        $audit = $this->audits()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());

        if ($audit === null) {
            if ($json) {
                $this->response()->error(__('errors.not_found'), [], 404);
            } else {
                $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);
            }

            return null;
        }

        return $audit;
    }

    private function audits(): AuditRepository
    {
        /** @var AuditRepository $r */
        $r = $this->container->get(AuditRepository::class);

        return $r;
    }

    private function data(): AuditDataRepository
    {
        /** @var AuditDataRepository $r */
        $r = $this->container->get(AuditDataRepository::class);

        return $r;
    }

    private function service(): AuditService
    {
        /** @var AuditService $s */
        $s = $this->container->get(AuditService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $c */
        $c = $this->container->get(ConfigService::class);

        return $c;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $l */
        $l = $this->container->get(ActivityLogService::class);

        return $l;
    }
}
