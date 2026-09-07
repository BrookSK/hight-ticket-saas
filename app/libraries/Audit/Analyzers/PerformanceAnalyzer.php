<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * Performance analyzer (deterministic, based on collected data only).
 *
 * Uses measurable signals from the homepage fetch: response time, HTML size,
 * number of CSS/JS/image resources, compression and cache headers. Core Web
 * Vitals (LCP/CLS/INP) are marked "not available" — never fabricated — with the
 * architecture ready to plug PageSpeed Insights later.
 */
final class PerformanceAnalyzer implements AnalyzerInterface
{
    private const CAT = 'performance';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $page = $context->homepage();
        $doc = $page?->document();
        if ($page === null) {
            return;
        }

        // Response time.
        $rt = $page->response->responseTimeMs;
        $context->addMetric(self::CAT, 'response_time', (string) round($rt), 'ms');
        if ($rt > 3000) {
            $context->addIssue(new Issue(
                'PERF-TTFB-001', self::CAT, Issue::SEVERITY_HIGH,
                'Tempo de resposta elevado',
                'O site respondeu em ' . round($rt) . ' ms na página inicial.',
                'Tempos de carregamento altos prejudicam a experiência dos visitantes e aumentam a taxa de abandono, além de afetar o SEO.',
                'Otimize o servidor, ative cache e reduza o processamento no carregamento inicial.',
                ['response_time_ms' => round($rt)], Issue::CONFIDENCE_HIGH
            ));
        } elseif ($rt < 800) {
            $context->addPositive('perf_fast', 'Bom tempo de resposta do servidor');
        }

        // HTML size.
        $htmlSize = strlen($page->response->body);
        $context->addMetric(self::CAT, 'html_size', (string) $htmlSize, 'bytes');
        if ($htmlSize > 500000) {
            $context->addIssue(new Issue(
                'PERF-HTML-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'HTML muito grande',
                'O HTML da página inicial tem ' . round($htmlSize / 1024) . ' KB.',
                'Documentos HTML grandes atrasam a renderização inicial.',
                'Reduza o HTML: remova conteúdo desnecessário e evite inline excessivo.',
                ['html_size_kb' => round($htmlSize / 1024)], Issue::CONFIDENCE_MEDIUM
            ));
        }

        // Compression.
        $encoding = $context->homepageHeaders['content-encoding'] ?? '';
        if ($encoding === '') {
            $context->addIssue(new Issue(
                'PERF-COMP-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Compressão não detectada',
                'A resposta não indicou compressão (gzip/br).',
                'Sem compressão, os arquivos trafegam maiores, aumentando o tempo de carregamento.',
                'Ative compressão gzip ou brotli no servidor.',
                [], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $context->addMetric(self::CAT, 'compression', $encoding);
            $context->addPositive('perf_compression', 'Compressão ativa (' . $encoding . ')');
        }

        // Cache headers.
        $cache = $context->homepageHeaders['cache-control'] ?? ($context->homepageHeaders['etag'] ?? '');
        if ($cache === '') {
            $context->addIssue(new Issue(
                'PERF-CACHE-001', self::CAT, Issue::SEVERITY_LOW,
                'Cabeçalhos de cache ausentes',
                'A resposta não define Cache-Control/ETag observáveis.',
                'Cache adequado reduz requisições repetidas e acelera visitas recorrentes.',
                'Configure Cache-Control e ETag para recursos estáticos.',
                [], Issue::CONFIDENCE_MEDIUM
            ));
        }

        // Resource counts (from DOM).
        if ($doc !== null) {
            $cssCount = count($doc->stylesheetHrefs());
            $jsCount = count($doc->scriptSrcs());
            $imgCount = count($doc->images());
            $context->addMetric(self::CAT, 'css_files', (string) $cssCount, 'count');
            $context->addMetric(self::CAT, 'js_files', (string) $jsCount, 'count');
            $context->addMetric(self::CAT, 'images', (string) $imgCount, 'count');

            if ($jsCount > 25) {
                $context->addIssue(new Issue(
                    'PERF-JS-001', self::CAT, Issue::SEVERITY_MEDIUM,
                    'Muitos arquivos JavaScript',
                    'A página carrega ' . $jsCount . ' scripts externos.',
                    'Muitos scripts aumentam o tempo de carregamento e o consumo de dados.',
                    'Combine/reduza scripts e carregue de forma assíncrona quando possível.',
                    ['js_files' => $jsCount], Issue::CONFIDENCE_MEDIUM
                ));
            }
        }

        // Core Web Vitals: not available in this phase (never fabricated).
        foreach (['lcp', 'cls', 'inp'] as $vital) {
            $context->addMetric(self::CAT, $vital, null, null, false);
        }
    }
}
