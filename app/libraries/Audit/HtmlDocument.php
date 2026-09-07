<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parsed HTML document wrapper.
 *
 * Wraps DOMDocument/DOMXPath with convenience accessors used by the crawler and
 * analyzers. Parses defensively (errors suppressed) since real-world HTML is
 * often malformed. Never executes scripts; purely structural inspection.
 */
final class HtmlDocument
{
    private DOMDocument $dom;

    private DOMXPath $xpath;

    private string $rawHtml;

    public function __construct(string $html)
    {
        $this->rawHtml = $html;
        $this->dom = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        // Force UTF-8 handling; suppress malformed-HTML warnings.
        $prefixed = '<?xml encoding="UTF-8">' . $html;
        @$this->dom->loadHTML($prefixed, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($previous);

        $this->xpath = new DOMXPath($this->dom);
    }

    public function raw(): string
    {
        return $this->rawHtml;
    }

    public function title(): ?string
    {
        $node = $this->xpath->query('//title')->item(0);
        $text = $node?->textContent;

        return $text !== null && trim($text) !== '' ? trim($text) : null;
    }

    public function metaContent(string $name): ?string
    {
        $node = $this->xpath->query(sprintf('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="%s"]', strtolower($name)))->item(0);
        if ($node instanceof DOMElement) {
            $content = $node->getAttribute('content');

            return $content !== '' ? $content : null;
        }

        return null;
    }

    public function metaProperty(string $property): ?string
    {
        $node = $this->xpath->query(sprintf('//meta[@property="%s"]', $property))->item(0);
        if ($node instanceof DOMElement) {
            $content = $node->getAttribute('content');

            return $content !== '' ? $content : null;
        }

        return null;
    }

    public function linkRel(string $rel): ?string
    {
        $node = $this->xpath->query(sprintf('//link[@rel="%s"]', $rel))->item(0);
        if ($node instanceof DOMElement) {
            $href = $node->getAttribute('href');

            return $href !== '' ? $href : null;
        }

        return null;
    }

    /**
     * @return list<string> href values of anchor tags.
     */
    public function anchorHrefs(): array
    {
        $hrefs = [];
        foreach ($this->xpath->query('//a[@href]') as $node) {
            if ($node instanceof DOMElement) {
                $hrefs[] = $node->getAttribute('href');
            }
        }

        return $hrefs;
    }

    /**
     * @return list<array{href:string, text:string}>
     */
    public function anchors(): array
    {
        $anchors = [];
        foreach ($this->xpath->query('//a[@href]') as $node) {
            if ($node instanceof DOMElement) {
                $anchors[] = [
                    'href' => $node->getAttribute('href'),
                    'text' => trim($node->textContent),
                ];
            }
        }

        return $anchors;
    }

    public function countTags(string $tag): int
    {
        return $this->xpath->query('//' . $tag)->length;
    }

    /**
     * @return list<string> text content of the given heading tag.
     */
    public function headings(string $tag): array
    {
        $out = [];
        foreach ($this->xpath->query('//' . $tag) as $node) {
            $out[] = trim($node->textContent);
        }

        return $out;
    }

    /**
     * @return list<array{src:string, alt:string|null, width:string|null, height:string|null, loading:string|null}>
     */
    public function images(): array
    {
        $images = [];
        foreach ($this->xpath->query('//img') as $node) {
            if ($node instanceof DOMElement) {
                $images[] = [
                    'src'     => $node->getAttribute('src'),
                    'alt'     => $node->hasAttribute('alt') ? $node->getAttribute('alt') : null,
                    'width'   => $node->hasAttribute('width') ? $node->getAttribute('width') : null,
                    'height'  => $node->hasAttribute('height') ? $node->getAttribute('height') : null,
                    'loading' => $node->hasAttribute('loading') ? $node->getAttribute('loading') : null,
                ];
            }
        }

        return $images;
    }

    /**
     * @return list<string> src of external scripts.
     */
    public function scriptSrcs(): array
    {
        $srcs = [];
        foreach ($this->xpath->query('//script[@src]') as $node) {
            if ($node instanceof DOMElement) {
                $srcs[] = $node->getAttribute('src');
            }
        }

        return $srcs;
    }

    /**
     * @return list<string> href of stylesheet links.
     */
    public function stylesheetHrefs(): array
    {
        $hrefs = [];
        foreach ($this->xpath->query('//link[@rel="stylesheet"]') as $node) {
            if ($node instanceof DOMElement) {
                $hrefs[] = $node->getAttribute('href');
            }
        }

        return $hrefs;
    }

    /**
     * @return list<string> the values of all JSON-LD script blocks.
     */
    public function jsonLdBlocks(): array
    {
        $blocks = [];
        foreach ($this->xpath->query('//script[@type="application/ld+json"]') as $node) {
            $blocks[] = trim($node->textContent);
        }

        return $blocks;
    }

    public function hasViewportMeta(): bool
    {
        return $this->metaContent('viewport') !== null;
    }

    /**
     * Approximate visible text length (for thin-content detection).
     */
    public function visibleTextLength(): int
    {
        $body = $this->xpath->query('//body')->item(0);
        if ($body === null) {
            return 0;
        }

        $text = preg_replace('/\s+/', ' ', $body->textContent) ?? '';

        return mb_strlen(trim($text));
    }

    public function xpath(): DOMXPath
    {
        return $this->xpath;
    }
}
