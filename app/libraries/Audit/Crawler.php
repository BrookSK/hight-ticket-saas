<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

use App\Libraries\Http\HttpClient;

/**
 * Modular, polite crawler.
 *
 * Fetches the start URL and (in full scope) discovers internal links up to a
 * page and depth limit. Respects robots.txt, restricts to the target host,
 * normalizes/deduplicates URLs, applies a delay between requests and records
 * HTTP status for every page. Non-destructive: only GET/HEAD requests.
 *
 * Emits a per-page callback so the caller (worker) can persist progress and
 * heartbeat incrementally — the crawler never holds a long web request.
 */
final class Crawler
{
    /** @var array<string, bool> Visited normalized URLs. */
    private array $visited = [];

    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxPages = 1,
        private readonly int $maxDepth = 0,
        private readonly int $delayMs = 300
    ) {
    }

    /**
     * Crawl starting at $startUrl within $host.
     *
     * @param callable(CrawledPage): void $onPage Called for each fetched page.
     */
    public function crawl(string $startUrl, string $host, ?RobotsTxt $robots = null, ?callable $onPage = null): void
    {
        $queue = [[$startUrl, 0]];
        $fetched = 0;

        while ($queue !== [] && $fetched < $this->maxPages) {
            [$url, $depth] = array_shift($queue);
            $normalized = $this->normalize($url);

            if ($normalized === null || isset($this->visited[$normalized])) {
                continue;
            }
            $this->visited[$normalized] = true;

            // Respect robots.txt for internal paths.
            if ($robots !== null) {
                $path = parse_url($normalized, PHP_URL_PATH) ?: '/';
                if (!$robots->isAllowed($path)) {
                    continue;
                }
            }

            $response = $this->http->get($normalized);
            $page = new CrawledPage($normalized, $depth, $response);
            $fetched++;

            if ($onPage !== null) {
                $onPage($page);
            }

            // Discover more links only in full scope and within depth limit.
            if ($depth < $this->maxDepth && $page->isHtml()) {
                $document = $page->document();
                if ($document !== null) {
                    foreach ($this->extractInternalLinks($document, $normalized, $host) as $link) {
                        if (!isset($this->visited[$link])) {
                            $queue[] = [$link, $depth + 1];
                        }
                    }
                }
            }

            if ($this->delayMs > 0 && $queue !== [] && $fetched < $this->maxPages) {
                usleep($this->delayMs * 1000);
            }
        }
    }

    /**
     * @return list<string> normalized, same-host links.
     */
    private function extractInternalLinks(HtmlDocument $document, string $baseUrl, string $host): array
    {
        $links = [];
        foreach ($document->anchorHrefs() as $href) {
            $href = trim($href);
            if ($href === '' || $this->shouldIgnore($href)) {
                continue;
            }

            $absolute = $this->toAbsolute($href, $baseUrl);
            if ($absolute === null) {
                continue;
            }

            $normalized = $this->normalize($absolute);
            if ($normalized === null) {
                continue;
            }

            // Restrict to the same host.
            if (strtolower((string) parse_url($normalized, PHP_URL_HOST)) !== strtolower($host)) {
                continue;
            }

            $links[$normalized] = true;
        }

        return array_keys($links);
    }

    private function shouldIgnore(string $href): bool
    {
        $lower = strtolower($href);

        return str_starts_with($lower, '#')
            || str_starts_with($lower, 'mailto:')
            || str_starts_with($lower, 'tel:')
            || str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, 'data:')
            || str_starts_with($lower, 'whatsapp:');
    }

    private function toAbsolute(string $href, string $baseUrl): ?string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        if (!isset($base['scheme'], $base['host'])) {
            return null;
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $port . $href;
        }

        $path = $base['path'] ?? '/';
        $dir = substr($path, 0, strrpos($path, '/') !== false ? strrpos($path, '/') + 1 : 0);
        if ($dir === '') {
            $dir = '/';
        }

        return $scheme . '://' . $host . $port . $dir . $href;
    }

    /**
     * Normalize a URL: drop fragment, lowercase host, remove trailing slash on
     * non-root paths. Returns null for non-http(s).
     */
    private function normalize(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $port . $path . $query;
    }
}
