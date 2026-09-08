<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\Outreach\CommercialReportService;
use App\Services\Outreach\ReportLinkService;

/**
 * Public, tokenized commercial report page.
 *
 * Access is by unguessable token only. Expiry/revocation are enforced by
 * ReportLinkService; each valid view is counted and logged. The page renders
 * from the stored snapshot, so it never queries internal tables and never
 * leaks internal scoring when hidden.
 */
final class PublicReportController extends Controller
{
    /**
     * GET /report/{token}
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $token = (string) ($params['token'] ?? '');
        $resolution = $this->links()->resolvePublic($token, $request->ip(), $request->userAgent());

        if (!($resolution['ok'] ?? false)) {
            $reason = (string) ($resolution['reason'] ?? 'not_found');
            $status = $reason === 'not_found' ? 404 : 410; // 410 Gone for expired/revoked
            $this->render('public.report_unavailable', [
                'title'  => __('outreach.public.unavailable_title'),
                'reason' => $reason,
            ], 'public', $status);

            return;
        }

        /** @var array<string, mixed> $report */
        $report = $resolution['report'];
        $snapshot = $this->reports()->decodeSnapshot($report);

        $this->render('public.report', [
            'title'    => (string) ($report['title'] ?? __('outreach.public.default_title')),
            'report'   => $report,
            'snapshot' => $snapshot,
        ], 'public');
    }

    private function links(): ReportLinkService
    {
        /** @var ReportLinkService $s */
        $s = $this->container->get(ReportLinkService::class);

        return $s;
    }

    private function reports(): CommercialReportService
    {
        /** @var CommercialReportService $s */
        $s = $this->container->get(CommercialReportService::class);

        return $s;
    }
}
