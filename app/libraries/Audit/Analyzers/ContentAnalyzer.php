<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * Basic content analyzer (deterministic; no IA).
 *
 * Detects thin content, placeholder text (lorem ipsum) and near-empty pages
 * using simple, evidence-backed signals.
 */
final class ContentAnalyzer implements AnalyzerInterface
{
    private const CAT = 'content';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $page = $context->homepage();
        $doc = $page?->document();
        if ($doc === null) {
            return;
        }

        $textLen = $doc->visibleTextLength();
        $context->addMetric(self::CAT, 'visible_text_length', (string) $textLen, 'chars');

        if ($textLen < 200) {
            $context->addIssue(new Issue(
                'CONT-THIN-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Conteúdo muito reduzido',
                'A página inicial possui pouco texto visível (' . $textLen . ' caracteres).',
                'Páginas com pouco conteúdo tendem a ranquear pior e transmitem menos valor ao visitante.',
                'Amplie o conteúdo com informações relevantes para o público.',
                ['visible_text_length' => $textLen], Issue::CONFIDENCE_MEDIUM
            ));
        }

        $body = strtolower($page->response->body);
        if (str_contains($body, 'lorem ipsum')) {
            $context->addIssue(new Issue(
                'CONT-LOREM-001', self::CAT, Issue::SEVERITY_HIGH,
                'Texto de preenchimento (lorem ipsum)',
                'Foi encontrado texto "lorem ipsum" na página.',
                'Texto de preenchimento indica conteúdo incompleto e passa impressão de site inacabado ao visitante.',
                'Substitua o texto de preenchimento por conteúdo real.',
                ['pattern' => 'lorem ipsum'], Issue::CONFIDENCE_HIGH
            ));
        }
    }
}
