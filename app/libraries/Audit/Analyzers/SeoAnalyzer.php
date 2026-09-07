<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\HtmlDocument;
use App\Libraries\Audit\Issue;

/**
 * On-page SEO analyzer (deterministic).
 *
 * Inspects title, meta description, headings, canonical, Open Graph, Twitter
 * Cards and structured data on the homepage document. Every finding carries
 * evidence; nothing is inferred without a concrete signal.
 */
final class SeoAnalyzer implements AnalyzerInterface
{
    private const CAT = 'seo';

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

        $this->checkTitle($context, $doc);
        $this->checkMetaDescription($context, $doc);
        $this->checkHeadings($context, $doc);
        $this->checkCanonical($context, $doc);
        $this->checkOpenGraph($context, $doc);
        $this->checkSchema($context, $doc);
        $this->checkSitemapRobots($context);
    }

    private function checkTitle(AuditContext $c, HtmlDocument $doc): void
    {
        $title = $doc->title();
        if ($title === null) {
            $c->addIssue(new Issue(
                'SEO-TITLE-001', self::CAT, Issue::SEVERITY_HIGH,
                'Título (title) ausente',
                'A página inicial não possui um elemento <title> válido.',
                'O título é um dos fatores mais importantes de SEO e aparece na aba do navegador e nos resultados de busca. Sua ausência prejudica o posicionamento e a taxa de cliques.',
                'Adicione um título descritivo e único, idealmente entre 50 e 60 caracteres.',
                ['element' => '<title>']
            ));

            return;
        }

        $c->addMetric(self::CAT, 'title_length', (string) mb_strlen($title), 'chars');
        $len = mb_strlen($title);
        if ($len < 10) {
            $c->addIssue(new Issue(
                'SEO-TITLE-002', self::CAT, Issue::SEVERITY_MEDIUM,
                'Título muito curto',
                'O título da página é muito curto (' . $len . ' caracteres).',
                'Títulos curtos costumam não descrever bem o conteúdo, reduzindo a relevância nos resultados de busca.',
                'Amplie o título para descrever melhor a página (50–60 caracteres).',
                ['title' => $title], Issue::CONFIDENCE_MEDIUM
            ));
        } elseif ($len > 65) {
            $c->addIssue(new Issue(
                'SEO-TITLE-003', self::CAT, Issue::SEVERITY_LOW,
                'Título muito longo',
                'O título possui ' . $len . ' caracteres e pode ser truncado nos resultados de busca.',
                'Títulos longos são cortados pelo Google, o que pode esconder informações importantes.',
                'Reduza o título para até ~60 caracteres mantendo as palavras mais relevantes.',
                ['title' => $title], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $c->addPositive('seo_title', 'Título bem configurado');
        }
    }

    private function checkMetaDescription(AuditContext $c, HtmlDocument $doc): void
    {
        $desc = $doc->metaContent('description');
        if ($desc === null) {
            $c->addIssue(new Issue(
                'SEO-DESC-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'Meta description ausente',
                'A página não possui meta description.',
                'A meta description influencia a taxa de cliques nos resultados de busca. Sem ela, o Google gera um resumo automático, muitas vezes menos atrativo.',
                'Adicione uma meta description persuasiva de 120 a 160 caracteres.',
                ['element' => '<meta name="description">']
            ));

            return;
        }

        $len = mb_strlen($desc);
        $c->addMetric(self::CAT, 'meta_description_length', (string) $len, 'chars');
        if ($len < 50) {
            $c->addIssue(new Issue(
                'SEO-DESC-002', self::CAT, Issue::SEVERITY_LOW,
                'Meta description muito curta',
                'A meta description possui apenas ' . $len . ' caracteres.',
                'Descrições muito curtas desperdiçam espaço valioso nos resultados de busca.',
                'Amplie a descrição para 120–160 caracteres.',
                ['description' => $desc], Issue::CONFIDENCE_MEDIUM
            ));
        } elseif ($len > 170) {
            $c->addIssue(new Issue(
                'SEO-DESC-003', self::CAT, Issue::SEVERITY_LOW,
                'Meta description muito longa',
                'A meta description possui ' . $len . ' caracteres e pode ser truncada.',
                'Descrições longas são cortadas nos resultados de busca.',
                'Reduza para até ~160 caracteres.',
                ['description' => $desc], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $c->addPositive('seo_description', 'Meta description configurada');
        }
    }

    private function checkHeadings(AuditContext $c, HtmlDocument $doc): void
    {
        $h1 = $doc->headings('h1');
        $c->addMetric(self::CAT, 'h1_count', (string) count($h1), 'count');

        if (count($h1) === 0) {
            $c->addIssue(new Issue(
                'SEO-H1-001', self::CAT, Issue::SEVERITY_MEDIUM,
                'H1 ausente',
                'A página não possui um cabeçalho H1.',
                'O H1 ajuda buscadores e usuários a entenderem o tema principal da página.',
                'Adicione um único H1 descritivo com o tema principal.',
                ['h1_count' => 0]
            ));
        } elseif (count($h1) > 1) {
            $c->addIssue(new Issue(
                'SEO-H1-002', self::CAT, Issue::SEVERITY_LOW,
                'Múltiplos H1',
                'A página possui ' . count($h1) . ' elementos H1.',
                'Vários H1 podem diluir o foco temático da página.',
                'Utilize um único H1 e organize o restante como H2/H3.',
                ['h1_count' => count($h1)], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $c->addPositive('seo_h1', 'Estrutura de H1 adequada');
        }
    }

    private function checkCanonical(AuditContext $c, HtmlDocument $doc): void
    {
        $canonical = $doc->linkRel('canonical');
        if ($canonical === null) {
            $c->addIssue(new Issue(
                'SEO-CANON-001', self::CAT, Issue::SEVERITY_LOW,
                'Canonical ausente',
                'A página não define uma URL canônica.',
                'A tag canonical ajuda a evitar problemas de conteúdo duplicado.',
                'Adicione <link rel="canonical"> apontando para a URL preferida.',
                ['element' => '<link rel="canonical">'], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $c->addMetric(self::CAT, 'canonical', $canonical);
        }
    }

    private function checkOpenGraph(AuditContext $c, HtmlDocument $doc): void
    {
        $missing = [];
        foreach (['og:title', 'og:description', 'og:image'] as $prop) {
            if ($doc->metaProperty($prop) === null) {
                $missing[] = $prop;
            }
        }

        if ($missing !== []) {
            $c->addIssue(new Issue(
                'SEO-OG-001', self::CAT, Issue::SEVERITY_LOW,
                'Open Graph incompleto',
                'Faltam tags Open Graph: ' . implode(', ', $missing) . '.',
                'Sem Open Graph, o compartilhamento em redes sociais fica sem título, descrição ou imagem adequados, reduzindo cliques.',
                'Adicione as tags og:title, og:description e og:image.',
                ['missing' => $missing], Issue::CONFIDENCE_MEDIUM
            ));
        } else {
            $c->addPositive('seo_og', 'Open Graph configurado');
        }
    }

    private function checkSchema(AuditContext $c, HtmlDocument $doc): void
    {
        $blocks = $doc->jsonLdBlocks();
        if ($blocks === []) {
            $c->addIssue(new Issue(
                'SEO-SCHEMA-001', self::CAT, Issue::SEVERITY_INFO,
                'Dados estruturados não encontrados',
                'Não foram encontrados dados estruturados (JSON-LD) na página inicial.',
                'Dados estruturados podem habilitar resultados enriquecidos no Google.',
                'Considere adicionar Schema.org (Organization, WebSite, etc.) em JSON-LD.',
                [], Issue::CONFIDENCE_MEDIUM
            ));

            return;
        }

        $types = [];
        foreach ($blocks as $block) {
            $decoded = json_decode($block, true);
            if (is_array($decoded) && isset($decoded['@type'])) {
                $types[] = is_array($decoded['@type']) ? implode('/', $decoded['@type']) : (string) $decoded['@type'];
            }
        }
        $c->addMetric(self::CAT, 'schema_types', $types !== [] ? implode(', ', $types) : (string) count($blocks));
        $c->addPositive('seo_schema', 'Dados estruturados presentes');
    }

    private function checkSitemapRobots(AuditContext $c): void
    {
        if ($c->sitemap !== null && ($c->sitemap['valid'] ?? false)) {
            $c->addPositive('seo_sitemap', 'Sitemap encontrado');
            $c->addMetric(self::CAT, 'sitemap_urls', (string) count($c->sitemap['urls'] ?? []), 'count');
        } else {
            $c->addIssue(new Issue(
                'SEO-SITEMAP-001', self::CAT, Issue::SEVERITY_LOW,
                'Sitemap não encontrado',
                'Não foi possível localizar um sitemap.xml válido.',
                'O sitemap ajuda os buscadores a descobrir e indexar todas as páginas do site.',
                'Publique um sitemap.xml e referencie-o no robots.txt.',
                [], Issue::CONFIDENCE_MEDIUM
            ));
        }

        if ($c->robots !== null && $c->robots->wasFound()) {
            $c->addPositive('seo_robots', 'robots.txt encontrado');
        }
    }
}
