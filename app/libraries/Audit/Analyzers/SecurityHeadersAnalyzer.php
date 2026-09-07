<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * Passive security-headers analyzer (deterministic, non-intrusive).
 *
 * Inspects the homepage response headers for recommended security headers.
 * Purely passive: no attacks, probing or exploitation.
 */
final class SecurityHeadersAnalyzer implements AnalyzerInterface
{
    private const CAT = 'security';

    /** @var array<string, array{severity:string,label:string}> */
    private const HEADERS = [
        'strict-transport-security' => ['severity' => Issue::SEVERITY_MEDIUM, 'label' => 'HSTS (Strict-Transport-Security)'],
        'content-security-policy'   => ['severity' => Issue::SEVERITY_MEDIUM, 'label' => 'Content-Security-Policy'],
        'x-content-type-options'    => ['severity' => Issue::SEVERITY_LOW, 'label' => 'X-Content-Type-Options'],
        'x-frame-options'           => ['severity' => Issue::SEVERITY_LOW, 'label' => 'X-Frame-Options'],
        'referrer-policy'           => ['severity' => Issue::SEVERITY_LOW, 'label' => 'Referrer-Policy'],
        'permissions-policy'        => ['severity' => Issue::SEVERITY_INFO, 'label' => 'Permissions-Policy'],
    ];

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $headers = $context->homepageHeaders;
        if ($headers === []) {
            return;
        }

        $present = 0;
        foreach (self::HEADERS as $key => $meta) {
            if (isset($headers[$key])) {
                $present++;
                continue;
            }

            $context->addIssue(new Issue(
                'SEC-HDR-' . strtoupper(substr(md5($key), 0, 6)),
                self::CAT,
                $meta['severity'],
                'Cabeçalho de segurança ausente: ' . $meta['label'],
                'A resposta não inclui o cabeçalho ' . $meta['label'] . '.',
                'Cabeçalhos de segurança ajudam a proteger os visitantes contra ataques comuns (clickjacking, sniffing de conteúdo, etc.).',
                'Configure o cabeçalho ' . $meta['label'] . ' no servidor web.',
                ['header' => $key],
                Issue::CONFIDENCE_HIGH
            ));
        }

        $context->addMetric(self::CAT, 'security_headers_present', $present . '/' . count(self::HEADERS), 'count');
        if ($present === count(self::HEADERS)) {
            $context->addPositive('security_headers', 'Cabeçalhos de segurança bem configurados');
        }

        // Server header disclosure (informational).
        if (isset($headers['server']) && $headers['server'] !== '') {
            $context->addMetric(self::CAT, 'server', $headers['server']);
        }
    }
}
