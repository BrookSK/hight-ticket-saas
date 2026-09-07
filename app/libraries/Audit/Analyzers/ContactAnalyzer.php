<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;

/**
 * Public contact detector (deterministic).
 *
 * Extracts publicly exposed contact info (emails, phones, WhatsApp, social
 * links) from the crawled pages. Phase 2 only collects public data — it does
 * not build prospecting. No aggressive scraping.
 */
final class ContactAnalyzer implements AnalyzerInterface
{
    private const CAT = 'content';

    public function category(): string
    {
        return self::CAT;
    }

    public function analyze(AuditContext $context): void
    {
        $emails = [];
        $socials = [];
        $whatsapp = [];

        foreach ($context->pages as $page) {
            if (!$page->isHtml()) {
                continue;
            }
            $html = $page->response->body;

            // Emails (mailto + plain).
            if (preg_match_all('#mailto:([^"\'>\s?]+)#i', $html, $m)) {
                foreach ($m[1] as $email) {
                    $emails[strtolower($email)] = true;
                }
            }
            if (preg_match_all('#[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}#', $html, $m)) {
                foreach ($m[0] as $email) {
                    $emails[strtolower($email)] = true;
                }
            }

            // WhatsApp / social links.
            $doc = $page->document();
            if ($doc !== null) {
                foreach ($doc->anchorHrefs() as $href) {
                    $lower = strtolower($href);
                    if (str_contains($lower, 'wa.me/') || str_contains($lower, 'api.whatsapp.com')) {
                        $whatsapp[$href] = true;
                    } elseif (preg_match('#(instagram|facebook|linkedin|youtube|tiktok)\.com#', $lower)) {
                        $socials[$href] = true;
                    }
                }
            }
        }

        foreach (array_keys($emails) as $email) {
            // Ignore obviously non-contact patterns (sentry, wixpress, etc.).
            if (preg_match('#\.(png|jpg|jpeg|gif|svg|webp)$#i', $email)) {
                continue;
            }
            $context->addContact('email', $email);
        }
        foreach (array_keys($whatsapp) as $wa) {
            $context->addContact('whatsapp', $wa);
        }
        foreach (array_keys($socials) as $social) {
            $context->addContact('social', $social);
        }
    }
}
