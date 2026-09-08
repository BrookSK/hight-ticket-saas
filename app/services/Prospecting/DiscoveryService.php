<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Libraries\Prospecting\ProviderRegistry;
use App\Repositories\AuditRepository;
use App\Repositories\CampaignRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\DiscoveryResultRepository;
use App\Repositories\OpportunityRuleRepository;
use App\Repositories\ProspectingJobRepository;
use App\Services\ConfigService;

/**
 * Discovery pipeline orchestrator (used by the worker).
 *
 * Split into discrete steps invoked per job type — discovery, enrichment,
 * audit, scoring — so the engine stays modular and each step is idempotent.
 * Runs in CLI context: owner is taken from the campaign row (no auth session).
 *
 * Reuses the Fase 2 audit engine (via queued audits) instead of duplicating the
 * scanner. Deduplicates against existing companies and the discovery table.
 */
final class DiscoveryService extends Service
{
    // ------------------------------------------------------------- discovery

    /**
     * Run the provider search for a campaign and create discovery_results,
     * then enqueue an enrichment job per new result. Idempotent via dedupe_hash.
     */
    public function runDiscovery(int $campaignId): void
    {
        $campaign = $this->campaigns()->findRaw($campaignId);
        if ($campaign === null) {
            return;
        }
        if (in_array((string) $campaign['status'], ['paused', 'cancelled'], true)) {
            return;
        }

        $provider = $this->registry()->get((string) $campaign['provider']);
        if ($provider === null || !$provider->isConfigured()) {
            $this->campaigns()->markFinished($campaignId, 'failed');

            return;
        }

        $this->campaigns()->setProgress($campaignId, 10, 'discovering');

        $criteria = [
            'input_payload' => $campaign['input_payload'] ?? '',
            'segment'       => $campaign['segment'] ?? null,
            'city'          => $campaign['city'] ?? null,
            'state'         => $campaign['state'] ?? null,
            'max_results'   => (int) $campaign['max_results'],
        ];

        $results = $provider->search($criteria);
        $created = 0;

        foreach ($results as $dto) {
            $hash = $dto->dedupeHash();
            if ($this->results()->existsByHash($campaignId, $hash)) {
                continue; // idempotent: skip already-discovered
            }

            $resultId = $this->results()->create([
                'owner_type'   => (string) $campaign['owner_type'],
                'owner_id'     => (int) $campaign['owner_id'],
                'campaign_id'  => $campaignId,
                'provider'     => $provider->key(),
                'external_id'  => $dto->externalId,
                'dedupe_hash'  => $hash,
                'raw_name'     => $dto->name,
                'raw_address'  => $dto->address,
                'raw_phone'    => $dto->phone,
                'raw_website'  => $dto->website,
                'raw_category' => $dto->category,
                'raw_data'     => $dto->raw !== [] ? json_encode($dto->raw, JSON_UNESCAPED_UNICODE) : null,
                'status'       => 'new',
            ]);
            $created++;
            $this->campaigns()->incrementCounter($campaignId, 'count_discovered');
            $this->jobs()->enqueue($campaignId, 'enrichment', $resultId, ['result_id' => $resultId]);
        }

        $this->campaigns()->setProgress($campaignId, 25, 'enriching');

        // If nothing was queued, the campaign is already done.
        if ($created === 0 && $this->jobs()->pendingCountForCampaign($campaignId) === 0) {
            $this->campaigns()->markFinished($campaignId, 'completed');
        }
    }

    // ------------------------------------------------------------ enrichment

    /**
     * Enrich a result; enqueue audit (if website ok + auto_audit) else scoring.
     */
    public function runEnrichment(int $resultId): void
    {
        $result = $this->results()->findRaw($resultId);
        if ($result === null) {
            return;
        }
        $campaign = $this->campaigns()->findRaw((int) $result['campaign_id']);
        if ($campaign === null || in_array((string) $campaign['status'], ['paused', 'cancelled'], true)) {
            return;
        }

        $enriched = $this->enrichment()->enrich($result);

        // Deduplicate against existing companies (owner-scoped).
        $companyId = null;
        $dedupeStatus = 'new';
        if (!empty($enriched['domain'])) {
            $dupes = $this->companies()->findPossibleDuplicates(
                (string) $campaign['owner_type'],
                (int) $campaign['owner_id'],
                $enriched['domain'],
                null,
                $enriched['email'],
                $enriched['phone']
            );
            if ($dupes !== []) {
                $companyId = (int) $dupes[0]['id'];
                $dedupeStatus = 'possible_duplicate';
                $this->campaigns()->incrementCounter((int) $result['campaign_id'], 'count_duplicated');
            }
        }

        $this->results()->applyEnrichment($resultId, [
            'name'          => $enriched['name'],
            'domain'        => $enriched['domain'],
            'website'       => $enriched['website'],
            'website_state' => $enriched['website_state'],
            'phone'         => $enriched['phone'],
            'whatsapp'      => $enriched['whatsapp'],
            'email'         => $enriched['email'],
            'city'          => $enriched['city'],
            'state'         => $enriched['state'],
            'category'      => $enriched['category'],
            'enrichment'    => json_encode($enriched['enrichment'], JSON_UNESCAPED_UNICODE),
            'dedupe_status' => $dedupeStatus,
            'company_id'    => $companyId,
            'status'        => 'enriched',
        ]);
        $this->campaigns()->incrementCounter((int) $result['campaign_id'], 'count_enriched');

        // Decide next step: audit only when it makes sense (cost control).
        $shouldAudit = (int) $campaign['auto_audit'] === 1
            && $enriched['website_state'] === 'ok'
            && (int) $campaign['count_audited'] < (int) $campaign['max_audits'];

        if ($shouldAudit) {
            $this->jobs()->enqueue((int) $result['campaign_id'], 'audit', $resultId, ['result_id' => $resultId]);
        } else {
            $this->jobs()->enqueue((int) $result['campaign_id'], 'scoring', $resultId, ['result_id' => $resultId]);
        }
    }

    // ----------------------------------------------------------------- audit

    /**
     * Queue (or reuse) a Fase 2 audit for the result's website, then enqueue
     * scoring. Reuses a recent audit for the same host to control cost.
     */
    public function runAudit(int $resultId): void
    {
        $result = $this->results()->findRaw($resultId);
        if ($result === null || empty($result['website'])) {
            $this->jobs()->enqueue((int) ($result['campaign_id'] ?? 0), 'scoring', $resultId, ['result_id' => $resultId]);

            return;
        }
        $campaign = $this->campaigns()->findRaw((int) $result['campaign_id']);
        if ($campaign === null) {
            return;
        }

        // Reuse a recent audit for the same host (cache window from settings).
        $cacheHours = (int) ($this->config()->get('prospecting_audit_cache_hours', '168') ?? 168);
        $recent = $this->audits()->findRecentByHost(
            (string) ($result['domain'] ?? ''),
            (string) $campaign['owner_type'],
            (int) $campaign['owner_id'],
            $cacheHours
        );

        if ($recent !== null) {
            $this->results()->setAudit($resultId, (int) $recent['id']);
        } else {
            // Create a queued audit under the campaign owner (processed by the
            // audit worker). We store the audit id for the scoring step to read.
            $auditId = $this->audits()->create([
                'owner_type'     => (string) $campaign['owner_type'],
                'owner_id'       => (int) $campaign['owner_id'],
                'created_by'     => $campaign['created_by'] !== null ? (int) $campaign['created_by'] : null,
                'url'            => (string) $result['website'],
                'normalized_url' => (string) $result['website'],
                'host'           => (string) ($result['domain'] ?? parse_url((string) $result['website'], PHP_URL_HOST)),
                'scope'          => 'homepage',
                'max_pages'      => 1,
                'max_depth'      => 0,
                'status'         => 'queued',
            ]);
            $this->results()->setAudit($resultId, $auditId);
        }

        $this->campaigns()->incrementCounter((int) $result['campaign_id'], 'count_audited');
        $this->jobs()->enqueue((int) $result['campaign_id'], 'scoring', $resultId, ['result_id' => $resultId]);
    }

    // --------------------------------------------------------------- scoring

    /**
     * Compute the opportunity score for the result and mark it qualified.
     * If it's the last pending job, finish the campaign.
     */
    public function runScoring(int $resultId): void
    {
        $result = $this->results()->findRaw($resultId);
        if ($result === null) {
            return;
        }
        $campaign = $this->campaigns()->findRaw((int) $result['campaign_id']);
        if ($campaign === null) {
            return;
        }

        $computed = $this->scoring()->score(
            $result,
            (string) $campaign['owner_type'],
            (int) $campaign['owner_id']
        );

        $meetsMin = $computed['score'] >= (int) $campaign['min_opportunity_score'];

        $this->results()->applyScore($resultId, [
            'site_score'             => $computed['site_score'],
            'opportunity_score'      => $computed['score'],
            'opportunity_confidence' => $computed['confidence'],
            'recommended_service'    => $computed['recommended_service'],
            'priority'               => $computed['priority'],
            'score_factors'          => json_encode($computed['factors'], JSON_UNESCAPED_UNICODE),
            'status'                 => $meetsMin ? 'qualified' : 'discarded',
        ]);
        if ($meetsMin) {
            $this->campaigns()->incrementCounter((int) $result['campaign_id'], 'count_qualified');
        }

        // Finish the campaign when no more jobs are pending.
        if ($this->jobs()->pendingCountForCampaign((int) $result['campaign_id']) === 0) {
            $this->campaigns()->markFinished((int) $result['campaign_id'], 'completed');
        } else {
            $this->campaigns()->setProgress((int) $result['campaign_id'], 70, 'scoring');
        }
    }

    // ------------------------------------------------------------- accessors

    private function registry(): ProviderRegistry
    {
        /** @var ProviderRegistry $r */
        $r = $this->container->get(ProviderRegistry::class);

        return $r;
    }

    private function campaigns(): CampaignRepository
    {
        /** @var CampaignRepository $r */
        $r = $this->container->get(CampaignRepository::class);

        return $r;
    }

    private function results(): DiscoveryResultRepository
    {
        /** @var DiscoveryResultRepository $r */
        $r = $this->container->get(DiscoveryResultRepository::class);

        return $r;
    }

    private function jobs(): ProspectingJobRepository
    {
        /** @var ProspectingJobRepository $r */
        $r = $this->container->get(ProspectingJobRepository::class);

        return $r;
    }

    private function companies(): CompanyRepository
    {
        /** @var CompanyRepository $r */
        $r = $this->container->get(CompanyRepository::class);

        return $r;
    }

    private function audits(): AuditRepository
    {
        /** @var AuditRepository $r */
        $r = $this->container->get(AuditRepository::class);

        return $r;
    }

    private function enrichment(): EnrichmentService
    {
        /** @var EnrichmentService $s */
        $s = $this->container->get(EnrichmentService::class);

        return $s;
    }

    private function scoring(): ProspectingScoringService
    {
        /** @var ProspectingScoringService $s */
        $s = $this->container->get(ProspectingScoringService::class);

        return $s;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $c */
        $c = $this->container->get(ConfigService::class);

        return $c;
    }
}
