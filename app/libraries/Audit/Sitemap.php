<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

/**
 * Minimal sitemap.xml parser.
 *
 * Extracts <loc> URLs from urlset and sitemapindex documents. Uses a safe XML
 * loader (external entities disabled) to avoid XXE from untrusted content.
 */
final class Sitemap
{
    /**
     * Parse a sitemap XML string and return the list of URLs found.
     *
     * @return array{urls:list<string>, sitemaps:list<string>, valid:bool}
     */
    public function parse(string $xml): array
    {
        $urls = [];
        $sitemaps = [];

        $previous = libxml_use_internal_errors(true);
        // Disable network access / external entities (XXE protection).
        $doc = simplexml_load_string(
            $xml,
            \SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOENT
        );
        libxml_use_internal_errors($previous);

        if ($doc === false) {
            return ['urls' => [], 'sitemaps' => [], 'valid' => false];
        }

        $name = strtolower($doc->getName());

        if ($name === 'sitemapindex') {
            foreach ($doc->sitemap as $entry) {
                $loc = trim((string) $entry->loc);
                if ($loc !== '') {
                    $sitemaps[] = $loc;
                }
            }
        } else {
            // urlset (or unknown root that still has url/loc children).
            foreach ($doc->url as $entry) {
                $loc = trim((string) $entry->loc);
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
        }

        return [
            'urls'     => array_values(array_unique($urls)),
            'sitemaps' => array_values(array_unique($sitemaps)),
            'valid'    => true,
        ];
    }
}
