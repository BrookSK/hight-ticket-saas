<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Libraries\Http\HttpClient;
use App\Services\CompanyService;

/**
 * Enrichment: normalize a discovery result and gather public signals.
 *
 * Uses the SSRF-protected HttpClient to fetch the company website (when
 * provided/found) and extract public socials, WhatsApp links and emails.
 * Determines the website_state (none/unreachable/ok/blocked). Never invents a
 * website or private data.
 */
final class EnrichmentService extends Service
{
    /**
     * Enrich a raw discovery result row into normalized fields.
     *
     * @param array<string, mixed> $result Raw discovery_results row.
     * @return array{
     *   name:string, domain:?string, website:?string, website_state:string,
     *   phone:?string, whatsapp:?string, email:?string, city:?string, state:?string,
     *   category:?string, enrichment:array<string,mixed>
     * }
     */
    public function enrich(array $result): array
    {
        $name = trim((string) ($result['raw_name'] ?? '')) ?: 'Empresa';
        $rawWebsite = trim((string) ($result['raw_website'] ?? ''));
        $phone = $this->digits($result['raw_phone'] ?? null);

        $domain = null;
        $website = null;
        $websiteState = 'none';
        $whatsapp = null;
        $email = null;
        $socials = [];
        $sources = ['name' => 'provider'];

        if ($rawWebsite !== '') {
            /** @var CompanyService $companyService */
            $companyService = $this->container->get(CompanyService::class);
            $domain = $companyService->normalizeDomain($rawWebsite);
            $website = $rawWebsite;
            $sources['website'] = 'provider';

            // Fetch the homepage (SSRF-safe) to determine state + public signals.
            /** @var HttpClient $http */
            $http = $this->container->get('httpClient');
            $response = $http->get($rawWebsite);

            if ($response->error !== null) {
                $websiteState = str_starts_with((string) $response->error, 'blocked:') ? 'blocked' : 'unreachable';
            } elseif ($response->statusCode >= 200 && $response->statusCode < 400) {
                $websiteState = 'ok';
                [$whatsapp, $email, $socials] = $this->extractContacts($response->body);
                if ($whatsapp !== null) { $sources['whatsapp'] = 'website'; }
                if ($email !== null) { $sources['email'] = 'website'; }
            } else {
                $websiteState = 'unreachable';
            }
        }

        return [
            'name'          => $name,
            'domain'        => $domain,
            'website'       => $website,
            'website_state' => $websiteState,
            'phone'         => $phone,
            'whatsapp'      => $whatsapp,
            'email'         => $email,
            'city'          => $this->nt($result['city'] ?? null),
            'state'         => $this->nt($result['state'] ?? null),
            'category'      => $this->nt($result['raw_category'] ?? null),
            'enrichment'    => [
                'socials' => $socials,
                'sources' => $sources,
            ],
        ];
    }

    /**
     * Extract public WhatsApp, email and social links from HTML.
     *
     * @return array{0:?string,1:?string,2:list<string>}
     */
    private function extractContacts(string $html): array
    {
        $whatsapp = null;
        $email = null;
        $socials = [];

        if (preg_match('#https?://(?:wa\.me|api\.whatsapp\.com)/[^"\'\s<>]+#i', $html, $m)) {
            $whatsapp = $m[0];
        }
        if (preg_match('#[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}#', $html, $m)) {
            $candidate = strtolower($m[0]);
            if (!preg_match('#\.(png|jpg|jpeg|gif|svg|webp)$#', $candidate)) {
                $email = $candidate;
            }
        }
        if (preg_match_all('#https?://(?:www\.)?(instagram|facebook|linkedin|youtube|tiktok)\.com/[^"\'\s<>]+#i', $html, $mm)) {
            $socials = array_values(array_unique($mm[0]));
        }

        return [$whatsapp, $email, $socials];
    }

    private function digits(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $d = preg_replace('/\D+/', '', (string) $v) ?? '';

        return $d === '' ? null : $d;
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }
}
