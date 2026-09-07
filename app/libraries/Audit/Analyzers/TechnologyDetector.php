<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;
use App\Libraries\Audit\Issue;

/**
 * Technology detector (deterministic, evidence + confidence based).
 *
 * Detects CMS/builders/analytics from public signals in HTML and headers.
 * Never asserts a technology without concrete evidence; uses confidence levels
 * to avoid false positives (e.g. WordPress requires strong signals).
 */
final class TechnologyDetector implements AnalyzerInterface
{
    private const CAT = 'technology';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $page = $context->homepage();
        if ($page === null || $page->response->body === '') {
            return;
        }

        $html = $page->response->body;
        $headers = $context->homepageHeaders;

        $this->detectWordPress($context, $html, $headers);
        $this->detectElementor($context, $html);
        $this->detectWooCommerce($context, $html);
        $this->detectAnalytics($context, $html);
        $this->detectServer($context, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    private function detectWordPress(AuditContext $c, string $html, array $headers): void
    {
        $signals = [];
        if (preg_match('#/wp-content/#', $html)) {
            $signals[] = 'wp-content';
        }
        if (preg_match('#/wp-includes/#', $html)) {
            $signals[] = 'wp-includes';
        }
        if (preg_match('#<link[^>]+rel=["\']https://api\.w\.org/["\']#i', $html) || str_contains($html, '/wp-json')) {
            $signals[] = 'wp-json';
        }
        if (preg_match('#<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress#i', $html)) {
            $signals[] = 'meta generator';
        }

        if ($signals === []) {
            return;
        }

        // Confidence scales with the number of independent signals.
        $confidence = count($signals) >= 2 ? Issue::CONFIDENCE_HIGH : Issue::CONFIDENCE_LOW;
        $c->addTechnology('WordPress', 'cms', $confidence, implode(', ', $signals));
    }

    private function detectElementor(AuditContext $c, string $html): void
    {
        $signals = [];
        if (str_contains($html, 'elementor-')) {
            $signals[] = 'classes elementor-*';
        }
        if (preg_match('#/plugins/elementor/#', $html)) {
            $signals[] = '/plugins/elementor/';
        }

        if ($signals === []) {
            return;
        }

        $confidence = count($signals) >= 2 ? Issue::CONFIDENCE_HIGH : Issue::CONFIDENCE_MEDIUM;
        $c->addTechnology('Elementor', 'builder', $confidence, implode(', ', $signals));
    }

    private function detectWooCommerce(AuditContext $c, string $html): void
    {
        if (str_contains($html, 'woocommerce') || preg_match('#/plugins/woocommerce/#', $html)) {
            $c->addTechnology('WooCommerce', 'ecommerce', Issue::CONFIDENCE_MEDIUM, 'referências woocommerce');
        }
    }

    private function detectAnalytics(AuditContext $c, string $html): void
    {
        $map = [
            'Google Analytics'   => ['#google-analytics\.com/analytics\.js#', '#gtag/js\?id=#', '#G-[A-Z0-9]{6,}#'],
            'Google Tag Manager' => ['#googletagmanager\.com/gtm\.js#', '#GTM-[A-Z0-9]+#'],
            'Meta Pixel'         => ['#connect\.facebook\.net/[^"\']+/fbevents\.js#', '#fbq\(#'],
            'Microsoft Clarity'  => ['#clarity\.ms/tag#'],
            'Hotjar'             => ['#static\.hotjar\.com#'],
        ];

        foreach ($map as $name => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html)) {
                    $c->addTechnology($name, 'analytics', Issue::CONFIDENCE_HIGH, 'script detectado');
                    break;
                }
            }
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function detectServer(AuditContext $c, array $headers): void
    {
        if (isset($headers['server']) && $headers['server'] !== '') {
            $c->addTechnology($headers['server'], 'server', Issue::CONFIDENCE_HIGH, 'header Server');
        }
        if (isset($headers['x-powered-by']) && $headers['x-powered-by'] !== '') {
            $c->addTechnology($headers['x-powered-by'], 'framework', Issue::CONFIDENCE_MEDIUM, 'header X-Powered-By');
        }
    }
}
