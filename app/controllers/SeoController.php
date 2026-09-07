<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;

/**
 * Serves robots.txt and sitemap.xml for the institutional site.
 *
 * Controls flow only; builds simple, cache-friendly SEO artifacts.
 */
final class SeoController extends Controller
{
    private const PUBLIC_PATHS = [
        '/', '/recursos', '/como-funciona', '/planos', '/faq', '/contato',
        '/lista-de-espera', '/termos-de-uso', '/politica-de-privacidade',
    ];

    /**
     * @param array<string, string> $params
     */
    public function robots(Request $request, array $params = []): void
    {
        $base = $this->baseUrl($request);
        $body = "User-agent: *\nAllow: /\nDisallow: /app\nDisallow: /login\n\nSitemap: {$base}/sitemap.xml\n";

        header('Content-Type: text/plain; charset=UTF-8');
        echo $body;
    }

    /**
     * @param array<string, string> $params
     */
    public function sitemap(Request $request, array $params = []): void
    {
        $base = $this->baseUrl($request);
        $today = date('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (self::PUBLIC_PATHS as $path) {
            $loc = $base . ($path === '/' ? '/' : $path);
            $xml .= "  <url><loc>{$loc}</loc><lastmod>{$today}</lastmod></url>\n";
        }
        $xml .= '</urlset>' . "\n";

        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
    }

    private function baseUrl(Request $request): string
    {
        $scheme = $request->isSecure() ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }
}
