<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\DiscoveryResultRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Prospecting\ExclusionService;
use App\Services\Prospecting\ProspectingConversionService;

/**
 * Human review of discovered opportunities.
 *
 * Lists qualified results, shows a detail with the explained opportunity score,
 * and supports approve→convert (single/batch), discard, ignore (exclusion).
 * Owner-scoped via AccessContext.
 */
final class OpportunityReviewController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $filters = [
            'campaign_id'         => (int) $request->query('campaign_id', '0') ?: null,
            'status'              => (string) $request->query('status', 'qualified'),
            'priority'            => (string) $request->query('priority', ''),
            'website'             => (string) $request->query('website', ''),
            'recommended_service' => (string) $request->query('recommended_service', ''),
            'min_opportunity'     => (int) $request->query('min_opportunity', '0') ?: null,
            'search'              => (string) $request->query('search', ''),
            'order_by'            => (string) $request->query('order_by', 'opportunity_score'),
        ];
        $page = max(1, (int) $request->query('page', '1'));
        $total = $this->results()->count($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters);

        $this->render('admin.prospecting.review.index', [
            'title'      => __('prospecting.review.title'),
            'activePath' => '/app/prospecting/review',
            'results'    => $this->results()->paginate($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters, $page, self::PER_PAGE),
            'filters'    => $filters,
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'      => $total,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $result = $this->results()->findForContext((int) ($params['id'] ?? 0), $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($result === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $factors = [];
        if (!empty($result['score_factors'])) {
            $decoded = json_decode((string) $result['score_factors'], true);
            $factors = is_array($decoded) ? $decoded : [];
        }

        $this->render('admin.prospecting.review.show', [
            'title'      => $result['name'] ?: $result['raw_name'],
            'activePath' => '/app/prospecting/review',
            'result'     => $result,
            'factors'    => $factors,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function convert(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $outcome = $this->conversion()->convert($id);
        if ($outcome === ProspectingConversionService::CONVERTED) {
            $this->log()->record('opportunity_converted', $this->context()->userId(), ['object_type' => 'discovery_result', 'object_id' => $id]);
            $this->session()->flash('status', __('prospecting.review.converted'));
        } elseif ($outcome === ProspectingConversionService::SKIPPED_DUP) {
            $this->session()->flash('status', __('prospecting.review.already_converted'));
        } else {
            $this->session()->flash('status', __('errors.generic'));
        }
        $this->redirect('/app/prospecting/review/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function convertBatch(Request $request, array $params = []): void
    {
        $ids = $request->input('ids', []);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];
        $counts = $this->conversion()->convertBatch($ids);
        $this->log()->record('opportunity_converted_batch', $this->context()->userId(), ['result' => (string) $counts['converted']]);
        $this->session()->flash('status', __('prospecting.review.batch_result', [
            'converted' => $counts['converted'],
            'skipped'   => $counts['skipped'],
        ]));
        $this->redirect('/app/prospecting/review');
    }

    /**
     * @param array<string, string> $params
     */
    public function discard(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $id = (int) ($params['id'] ?? 0);
        $result = $this->results()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($result !== null) {
            $this->results()->setStatus($id, 'discarded', (string) $request->input('reason', 'other'));
            $this->session()->flash('status', __('prospecting.review.discarded'));
        }
        $this->redirect('/app/prospecting/review');
    }

    /**
     * Ignore: discard + add domain/email to the exclusion list.
     *
     * @param array<string, string> $params
     */
    public function ignore(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $id = (int) ($params['id'] ?? 0);
        $result = $this->results()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($result !== null) {
            if (!empty($result['domain'])) {
                $this->exclusion()->add('domain', (string) $result['domain'], 'ignored_from_review');
            } elseif (!empty($result['email'])) {
                $this->exclusion()->add('email', (string) $result['email'], 'ignored_from_review');
            }
            $this->results()->setStatus($id, 'ignored', 'ignored');
            $this->session()->flash('status', __('prospecting.review.ignored'));
        }
        $this->redirect('/app/prospecting/review');
    }

    private function results(): DiscoveryResultRepository
    {
        /** @var DiscoveryResultRepository $r */
        $r = $this->container->get(DiscoveryResultRepository::class);

        return $r;
    }

    private function conversion(): ProspectingConversionService
    {
        /** @var ProspectingConversionService $s */
        $s = $this->container->get(ProspectingConversionService::class);

        return $s;
    }

    private function exclusion(): ExclusionService
    {
        /** @var ExclusionService $s */
        $s = $this->container->get(ExclusionService::class);

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
