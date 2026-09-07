<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * Basic accessibility analyzer (deterministic).
 *
 * Checks image ALT text, presence of a lang attribute, viewport meta and links
 * without discernible text. Full WCAG conformance requires manual testing with
 * assistive technologies; this covers automatable basics only.
 */
final class AccessibilityAnalyzer implements AnalyzerInterface
{
    private const CAT = 'accessibility';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $doc = $context->homepage()?->document();
        if ($doc === null) {
            return;
        }

        $images = $doc->images();
        $missingAlt = 0;
        foreach ($images as $img) {
            if ($img['alt'] === null || trim((string) $img['alt']) === '') {
                $missingAlt++;
            }
        }

        if ($missingAlt > 0) {
            $context->addIssue(new Issue(
                'A11Y-ALT-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Imagens sem texto alternativo (ALT)',
                $missingAlt . ' de ' . count($images) . ' imagens não possuem atributo ALT.',
                'O texto alternativo é essencial para leitores de tela e também ajuda no SEO de imagens.',
                'Adicione ALT descritivo às imagens de conteúdo (ALT vazio apenas para decorativas).',
                ['missing_alt' => $missingAlt, 'total_images' => count($images)], Issue::CONFIDENCE_HIGH
            ));
        } elseif ($images !== []) {
            $context->addPositive('a11y_alt', 'Todas as imagens possuem ALT');
        }

        // Links without text.
        $emptyLinks = 0;
        foreach ($doc->anchors() as $a) {
            if ($a['text'] === '' && $a['href'] !== '') {
                $emptyLinks++;
            }
        }
        if ($emptyLinks > 0) {
            $context->addIssue(new Issue(
                'A11Y-LINK-001', self::CAT, Issue::SEVERITY_LOW,
                'Links sem texto',
                $emptyLinks . ' links não possuem texto legível.',
                'Links sem texto (ou apenas com ícones) dificultam a navegação por leitores de tela.',
                'Adicione texto visível ou aria-label aos links.',
                ['empty_links' => $emptyLinks], Issue::CONFIDENCE_MEDIUM
            ));
        }

        // lang attribute.
        $hasLang = $doc->xpath()->query('//html[@lang]')->length > 0;
        if (!$hasLang) {
            $context->addIssue(new Issue(
                'A11Y-LANG-001', self::CAT, Issue::SEVERITY_LOW,
                'Atributo lang ausente',
                'O elemento <html> não define o atributo lang.',
                'O atributo lang ajuda leitores de tela a pronunciarem o conteúdo corretamente.',
                'Defina o idioma, por exemplo <html lang="pt-BR">.',
                [], Issue::CONFIDENCE_HIGH
            ));
        }

        // Viewport (mobile).
        if (!$doc->hasViewportMeta()) {
            $context->addIssue(new Issue(
                'A11Y-VIEWPORT-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Meta viewport ausente',
                'A página não define a meta viewport.',
                'Sem a viewport, o site não se adapta bem a dispositivos móveis, prejudicando a experiência.',
                'Adicione <meta name="viewport" content="width=device-width, initial-scale=1">.',
                [], Issue::CONFIDENCE_HIGH
            ));
        } else {
            $context->addPositive('a11y_viewport', 'Meta viewport presente (mobile)');
        }
    }
}
