<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;
use App\Libraries\Http\HttpClient;

/**
 * Broken-link checker (deterministic).
 *
 * Collects links from crawled pages and verifies a bounded sample via HEAD
 * requests (cost control). Records state per link and raises an issue if broken
 * links are found. Uses the SSRF-protected HTTP client. Results are exposed via
 * the context for persistence by the caller.
 */
final class LinkChecker implements AnalyzerInterface
{
    private const CAT = 'best_practices';

    /** @var list<array{source_url:?string,target_url:string,type:string,status_code:?int,state:string}> */
    public array $links = [];

    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxLinksToCheck = 40
    ) {
    }

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $candidates = $this->collectLinks($context);
        $checked = 0;
        $broken = 0;

        foreach ($candidates as $target => $meta) {
            if ($checked >= $this->maxLinksToCheck) {
                $this->links[] = [
                    'source_url'  => $meta['source'],
                    'target_url'  => $target,
                    'type'        => $meta['type'],
                    'status_code' => null,
                    'state'       => 'unverified',
                ];
                continue;
            }

            $response = $this->http->head($target);
            $checked++;

            $state = 'ok';
            $status = $response->statusCode;
            if ($response->error !== null) {
                $state = str_starts_with((string) $response->error, 'blocked:') ? 'blocked' : 'timeout';
                $status = null;
            } elseif ($status >= 400) {
                $state = 'broken';
                $broken++;
            } elseif ($status >= 300) {
                $state = 'redirect';
            }

            $this->links[] = [
                'source_url'  => $meta['source'],
                'target_url'  => $target,
                'type'        => $meta['type'],
                'status_code' => $status,
                'state'       => $state,
            ];
        }

        $context->addMetric(self::CAT, 'links_checked', (string) $checked, 'count');
        $context->addMetric(self::CAT, 'links_broken', (string) $broken, 'count');

        if ($broken > 0) {
            $context->addIssue(new Issue(
                'BP-LINK-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Links quebrados encontrados',
                $broken . ' link(s) retornaram erro (4xx/5xx).',
                'Links quebrados prejudicam a experiência do usuário e a percepção de qualidade do site.',
                'Corrija ou remova os links quebrados listados.',
                ['broken_count' => $broken], Issue::CONFIDENCE_HIGH
            ));
        }
    }

    /**
     * @return array<string, array{source:?string,type:string}>
     */
    private function collectLinks(AuditContext $context): array
    {
        $links = [];
        foreach ($context->pages as $page) {
            $doc = $page->document();
            if ($doc === null) {
                continue;
            }
            foreach ($doc->anchorHrefs() as $href) {
                $href = trim($href);
                if ($href === '' || !preg_match('#^https?://#i', $href)) {
                    continue;
                }
                $host = strtolower((string) parse_url($href, PHP_URL_HOST));
                $type = $host === strtolower($context->host) ? 'internal' : 'external';
                if (!isset($links[$href])) {
                    $links[$href] = ['source' => $page->url, 'type' => $type];
                }
            }
        }

        return $links;
    }
}
