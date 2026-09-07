<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * HTTPS analyzer (deterministic).
 *
 * Checks whether the site is served over HTTPS based on the crawled homepage
 * URL/response. Certificate validity is enforced by the HTTP client (SSL
 * verification), so a successful HTTPS fetch is evidence of a valid certificate.
 */
final class HttpsAnalyzer implements AnalyzerInterface
{
    private const CAT = 'security';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $page = $context->homepage();
        if ($page === null) {
            return;
        }

        $isHttps = str_starts_with(strtolower($page->response->finalUrl), 'https://');

        if ($isHttps && $page->response->ok()) {
            $context->addPositive('https', 'HTTPS ativo com certificado válido');
            $context->addMetric(self::CAT, 'https', 'yes');

            return;
        }

        if (!$isHttps) {
            $context->addMetric(self::CAT, 'https', 'no', null, true);
            $context->addIssue(new Issue(
                'SEC-HTTPS-001', self::CAT, Issue::SEVERITY_CRITICAL,
                'Site sem HTTPS',
                'A página inicial não é servida por HTTPS.',
                'Sem HTTPS, os dados trafegam sem criptografia, o navegador exibe alertas de "não seguro" e o SEO é prejudicado, reduzindo a confiança dos visitantes.',
                'Instale um certificado SSL/TLS e force o redirecionamento de HTTP para HTTPS.',
                ['final_url' => $page->response->finalUrl]
            ));
        }
    }
}
