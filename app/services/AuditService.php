<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Audit\Analyzers\AccessibilityAnalyzer;
use App\Libraries\Audit\Analyzers\ContactAnalyzer;
use App\Libraries\Audit\Analyzers\ContentAnalyzer;
use App\Libraries\Audit\Analyzers\HttpsAnalyzer;
use App\Libraries\Audit\Analyzers\LinkChecker;
use App\Libraries\Audit\Analyzers\PerformanceAnalyzer;
use App\Libraries\Audit\Analyzers\SecurityHeadersAnalyzer;
use App\Libraries\Audit\Analyzers\SeoAnalyzer;
use App\Libraries\Audit\Analyzers\TechnologyDetector;
use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\CrawledPage;
use App\Libraries\Audit\Crawler;
use App\Libraries\Audit\RobotsTxt;
use App\Libraries\Audit\ScoringEngine;
use App\Libraries\Audit\Sitemap;
use App\Libraries\Http\HttpClient;
use App\Libraries\Http\SsrfException;
use App\Libraries\Http\SsrfGuard;
use App\Libraries\Logger;
use App\Repositories\AuditDataRepository;
use App\Repositories\AuditRepository;
use Throwable;

/**
 * Audit orchestration service.
 *
 * Two responsibilities:
 *  - createAudit(): validate URL (SSRF), enforce rate limits/plan, enqueue.
 *  - process(): run the crawl + analyzers + scoring pipeline for one audit,
 *    invoked by the CLI worker (never in a web request). Idempotent: it clears
 *    prior child data before writing, so a re-run cannot corrupt results.
 *
 * Partial results are preserved: if some pages fail but the homepage was
 * analyzed, the audit finishes as "partial" rather than "failed".
 */
final class AuditService extends Service
{
    public const RESULT_OK = 'ok';
    public const RESULT_INVALID_URL = 'invalid_url';
    public const RESULT_RATE_LIMITED = 'rate_limited';

    /**
     * Validate and enqueue a new audit for the current access context.
     *
     * @param array<string, mixed> $input
     * @return array{result:string, auditId?:int, error?:string}
     */
    public function createAudit(array $input): array
    {
        $rawUrl = trim((string) ($input['url'] ?? ''));

        try {
            $validated = $this->guard()->validate($rawUrl);
        } catch (SsrfException $e) {
            return ['result' => self::RESULT_INVALID_URL, 'error' => $e->getMessage()];
        }

        // Anti-abuse: per-user daily limit.
        $userId = $this->context()->userId();
        if ($userId !== null) {
            $dailyLimit = (int) ($this->config()->get('audit_rate_per_user_daily', '50') ?? 50);
            if ($this->audits()->countCreatedTodayByUser($userId) >= $dailyLimit) {
                return ['result' => self::RESULT_RATE_LIMITED];
            }
        }

        $scope = ($input['scope'] ?? 'homepage') === 'full' ? 'full' : 'homepage';
        $maxPagesLimit = (int) ($this->config()->get('audit_max_pages', '50') ?? 50);
        $maxDepthLimit = (int) ($this->config()->get('audit_max_depth', '2') ?? 2);

        $maxPages = $scope === 'full'
            ? max(1, min($maxPagesLimit, (int) ($input['max_pages'] ?? 20)))
            : 1;
        $maxDepth = $scope === 'full'
            ? max(0, min($maxDepthLimit, (int) ($input['max_depth'] ?? 1)))
            : 0;

        $auditId = $this->audits()->create([
            'owner_type'     => $this->context()->ownerType(),
            'owner_id'       => $this->context()->ownerId(),
            'created_by'     => $userId,
            'url'            => $rawUrl,
            'normalized_url' => $validated['url'],
            'host'           => $validated['host'],
            'scope'          => $scope,
            'max_pages'      => $maxPages,
            'max_depth'      => $maxDepth,
            'status'         => 'queued',
        ]);

        return ['result' => self::RESULT_OK, 'auditId' => $auditId];
    }

    /**
     * Process a single audit (called by the worker). Assumes the audit row was
     * already claimed (status=processing, locked_by=workerId).
     */
    public function process(int $auditId, string $workerId): void
    {
        $audit = $this->audits()->findRaw($auditId);
        if ($audit === null) {
            return;
        }

        $log = $this->logger();
        $log->info('Auditoria iniciada.', ['audit_id' => $auditId, 'worker' => $workerId]);

        try {
            // Idempotency: clear any partial data from a previous interrupted run.
            $this->data()->clearForAudit($auditId);

            $context = new AuditContext((string) $audit['normalized_url'], (string) $audit['host']);

            $this->beat($auditId, $workerId, 5, 'validating_domain');
            $this->collectRobotsAndSitemap($context);

            $this->beat($auditId, $workerId, 15, 'crawling');
            $failedPages = $this->runCrawler($auditId, $workerId, $context, $audit);

            if ($context->homepage() === null || !$context->homepage()->response->ok()) {
                // Could not access the site at all.
                $this->audits()->markFailed($auditId, 'site_unreachable');
                $log->warning('Auditoria falhou: site inacessível.', ['audit_id' => $auditId]);

                return;
            }

            // Capture homepage headers for header-based analyzers.
            $context->homepageHeaders = $context->homepage()->response->headers;

            $this->beat($auditId, $workerId, 60, 'analyzing');
            $linkChecker = $this->runAnalyzers($context);

            $this->beat($auditId, $workerId, 85, 'scoring');
            $scores = (new ScoringEngine())->compute($context->issues);

            $this->beat($auditId, $workerId, 92, 'persisting');
            $this->persist($auditId, $context, $linkChecker, $scores);

            $status = $failedPages > 0 && $context->pages !== [] ? 'partial' : 'completed';
            $this->audits()->finish($auditId, $status, $scores, $failedPages > 0 ? 'some_pages_failed' : null);

            $log->info('Auditoria concluída.', [
                'audit_id' => $auditId,
                'status'   => $status,
                'pages'    => count($context->pages),
                'issues'   => count($context->issues),
                'overall'  => $scores['overall'],
            ]);
        } catch (Throwable $e) {
            // Never leave the audit stuck; mark as failed with a stable code.
            $this->audits()->markFailed($auditId, 'processing_error');
            $log->error('Auditoria falhou com exceção.', [
                'audit_id' => $auditId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function collectRobotsAndSitemap(AuditContext $context): void
    {
        $base = 'https://' . $context->host;

        // robots.txt
        $robotsResponse = $this->http()->get($base . '/robots.txt');
        $robots = new RobotsTxt('lrvwebauditbot');
        if ($robotsResponse->ok() && $robotsResponse->body !== '') {
            $robots->parse($robotsResponse->body);
        }
        $context->robots = $robots;

        // sitemap.xml (from robots or default location)
        $sitemapUrls = $robots->sitemaps();
        if ($sitemapUrls === []) {
            $sitemapUrls = [$base . '/sitemap.xml'];
        }

        $parser = new Sitemap();
        foreach (array_slice($sitemapUrls, 0, 2) as $sitemapUrl) {
            $resp = $this->http()->get($sitemapUrl);
            if ($resp->ok() && $resp->body !== '') {
                $parsed = $parser->parse($resp->body);
                if ($parsed['valid']) {
                    $context->sitemap = ['urls' => $parsed['urls'], 'valid' => true];
                    break;
                }
            }
        }
    }

    /**
     * Run the crawler, persisting each page as it is fetched. Returns the number
     * of failed page fetches (for partial-status detection).
     *
     * @param array<string, mixed> $audit
     */
    private function runCrawler(int $auditId, string $workerId, AuditContext $context, array $audit): int
    {
        $delay = (int) ($this->config()->get('audit_request_delay_ms', '300') ?? 300);
        $crawler = new Crawler(
            $this->http(),
            (int) $audit['max_pages'],
            (int) $audit['max_depth'],
            $delay
        );

        $failed = 0;
        $crawler->crawl(
            (string) $audit['normalized_url'],
            (string) $audit['host'],
            $context->robots,
            function (CrawledPage $page) use (&$failed, $context, $auditId, $workerId): void {
                $context->pages[] = $page;

                $ok = $page->response->error === null && $page->response->statusCode > 0;
                if (!$ok || $page->response->statusCode >= 400) {
                    $failed++;
                }

                $this->data()->addPage([
                    'audit_id'         => $auditId,
                    'url'              => $page->url,
                    'depth'            => $page->depth,
                    'status_code'      => $page->response->statusCode ?: null,
                    'content_type'     => $page->response->contentType() ?: null,
                    'title'            => $page->document()?->title(),
                    'response_time_ms' => (int) round($page->response->responseTimeMs),
                    'html_size'        => strlen($page->response->body),
                    'redirected_to'    => null,
                    'fetch_status'     => $this->fetchStatus($page),
                    'error'            => $page->response->error,
                ]);
                $this->audits()->incrementPagesCrawled($auditId);
                // Heartbeat within the crawl loop keeps the audit alive.
                $this->audits()->heartbeat($auditId, $workerId, min(55, 15 + count($context->pages)), 'crawling');
            }
        );

        return $failed;
    }

    private function fetchStatus(CrawledPage $page): string
    {
        if ($page->response->error !== null) {
            return str_starts_with((string) $page->response->error, 'blocked:') ? 'blocked' : 'error';
        }
        if ($page->response->statusCode === 0) {
            return 'timeout';
        }

        return 'ok';
    }

    /**
     * Run all deterministic analyzers over the collected context.
     */
    private function runAnalyzers(AuditContext $context): LinkChecker
    {
        $linkChecker = new LinkChecker($this->http(), 40);

        $analyzers = [
            new HttpsAnalyzer(),
            new SecurityHeadersAnalyzer(),
            new SeoAnalyzer(),
            new PerformanceAnalyzer(),
            new AccessibilityAnalyzer(),
            new ContentAnalyzer(),
            new TechnologyDetector(),
            new ContactAnalyzer(),
            $linkChecker,
        ];

        foreach ($analyzers as $analyzer) {
            try {
                $analyzer->analyze($context);
            } catch (Throwable $e) {
                // One analyzer failing must not abort the whole audit.
                $this->logger()->warning('Analisador falhou.', [
                    'analyzer' => $analyzer::class,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $linkChecker;
    }

    /**
     * Persist issues, metrics, technologies, contacts, links and a summary.
     *
     * @param array<string, int|null> $scores
     */
    private function persist(int $auditId, AuditContext $context, LinkChecker $linkChecker, array $scores): void
    {
        foreach ($context->issues as $issue) {
            $this->data()->addIssue($issue->toRow($auditId));
        }
        foreach ($context->metrics as $m) {
            $this->data()->addMetric([
                'audit_id'  => $auditId,
                'category'  => $m['category'],
                'key'       => $m['key'],
                'value'     => $m['value'],
                'unit'      => $m['unit'],
                'available' => $m['available'] ? 1 : 0,
            ]);
        }
        foreach ($context->technologies as $t) {
            $this->data()->addTechnology([
                'audit_id'   => $auditId,
                'name'       => $t['name'],
                'category'   => $t['category'],
                'version'    => $t['version'],
                'confidence' => $t['confidence'],
                'evidence'   => $t['evidence'],
            ]);
        }
        foreach ($context->contacts as $contact) {
            $this->data()->addContact([
                'audit_id' => $auditId,
                'type'     => $contact['type'],
                'value'    => mb_substr($contact['value'], 0, 255),
            ]);
        }
        foreach ($linkChecker->links as $link) {
            $this->data()->addLink([
                'audit_id'    => $auditId,
                'source_url'  => $link['source_url'],
                'target_url'  => mb_substr($link['target_url'], 0, 500),
                'type'        => $link['type'],
                'status_code' => $link['status_code'],
                'state'       => $link['state'],
            ]);
        }

        // Processed summary block (separate from raw data), reusable downstream.
        $this->data()->putResult($auditId, 'summary', [
            'scores'    => $scores,
            'positives' => array_values($context->positives),
            'counts'    => [
                'pages'  => count($context->pages),
                'issues' => count($context->issues),
            ],
            'generated_at' => date('c'),
        ]);
    }

    private function beat(int $auditId, string $workerId, int $progress, string $step): void
    {
        $this->audits()->heartbeat($auditId, $workerId, $progress, $step);
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

    private function http(): HttpClient
    {
        /** @var HttpClient $h */
        $h = $this->container->get('httpClient');

        return $h;
    }

    private function guard(): SsrfGuard
    {
        /** @var SsrfGuard $g */
        $g = $this->container->get('ssrfGuard');

        return $g;
    }

    private function logger(): Logger
    {
        /** @var Logger $l */
        $l = $this->container->get('logger');

        return $l;
    }
}
