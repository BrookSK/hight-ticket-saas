<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Outreach\CommercialReportService;
use App\Services\Outreach\ReportLinkService;

/**
 * Shareable commercial reports management. Controls flow only.
 */
final class ReportController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.outreach.reports.index', [
            'title'      => __('outreach.reports.title'),
            'activePath' => '/app/outreach/reports',
            'reports'    => $this->service()->listForContext(),
        ], 'app');
    }

    /**
     * Create a report from a lead and return its shareable link.
     *
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $leadId = (int) $request->input('lead_id', 0);
        $result = $this->service()->createFromLead($leadId, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->session()->flash('status', __('errors.validation'));
            $this->redirect('/app/leads/' . $leadId);

            return;
        }
        $this->log()->record('report_created', $this->context()->userId(), ['object_type' => 'report', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('outreach.reports.created'));
        $this->session()->flash('report_link', $this->links()->publicUrl((string) ($result['token'] ?? '')));
        $this->redirect('/app/leads/' . $leadId);
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $report = $this->service()->find($id);
        if ($report === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }
        $this->render('admin.outreach.reports.show', [
            'title'      => __('outreach.reports.title'),
            'activePath' => '/app/outreach/reports',
            'report'     => $report,
            'snapshot'   => $this->service()->decodeSnapshot($report),
            'publicUrl'  => $this->links()->publicUrl((string) $report['token']),
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function revoke(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->links()->revoke($id)) {
            $this->log()->record('report_revoked', $this->context()->userId(), ['object_type' => 'report', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.reports.revoked'));
        }
        $this->redirect('/app/outreach/reports');
    }

    private function service(): CommercialReportService
    {
        /** @var CommercialReportService $s */
        $s = $this->container->get(CommercialReportService::class);

        return $s;
    }

    private function links(): ReportLinkService
    {
        /** @var ReportLinkService $s */
        $s = $this->container->get(ReportLinkService::class);

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

