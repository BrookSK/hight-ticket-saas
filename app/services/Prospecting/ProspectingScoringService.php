<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Libraries\Prospecting\OpportunityScoringEngine;
use App\Repositories\AuditDataRepository;
use App\Repositories\AuditRepository;
use App\Repositories\OpportunityRuleRepository;

/**
 * Builds opportunity-scoring signals from collected data and runs the engine.
 *
 * Signals are derived strictly from what was collected (enrichment + audit).
 * Site Score comes from the audit; Opportunity Score is computed here and is a
 * distinct commercial metric.
 */
final class ProspectingScoringService extends Service
{
    /**
     * @param array<string, mixed> $result Discovery result row (normalized).
     * @return array{score:int, confidence:string, priority:string, recommended_service:?string, factors:list<array<string,mixed>>, site_score:?int}
     */
    public function score(array $result, string $ownerType, int $ownerId): array
    {
        $hasWebsite = (string) ($result['website_state'] ?? 'none') === 'ok';
        $signals = [
            'has_website'     => $hasWebsite,
            'site_score'      => null,
            'seo_issues'      => 0,
            'perf_issues'     => 0,
            'security_issues' => 0,
            'has_wordpress'   => false,
            'has_elementor'   => false,
            'has_contact'     => !empty($result['email']) || !empty($result['phone']),
            'has_whatsapp'    => !empty($result['whatsapp']),
        ];

        $siteScore = null;
        $auditId = isset($result['audit_id']) ? (int) $result['audit_id'] : 0;
        if ($hasWebsite && $auditId > 0) {
            $audit = $this->audits()->findRaw($auditId);
            if ($audit !== null) {
                $siteScore = $audit['score_overall'] !== null ? (int) $audit['score_overall'] : null;
                $signals['site_score'] = $siteScore;
                $counts = $this->issueCounts($auditId);
                $signals['seo_issues'] = $counts['seo'];
                $signals['perf_issues'] = $counts['performance'];
                $signals['security_issues'] = $counts['security'];
                [$signals['has_wordpress'], $signals['has_elementor']] = $this->detectTech($auditId);
            }
        }

        $rules = $this->rules()->effectiveRules($ownerType, $ownerId);
        $engine = new OpportunityScoringEngine($rules);
        $computed = $engine->compute($signals);
        $computed['site_score'] = $siteScore;

        return $computed;
    }

    /**
     * @return array{seo:int, performance:int, security:int}
     */
    private function issueCounts(int $auditId): array
    {
        $issues = $this->auditData()->issues($auditId);
        $counts = ['seo' => 0, 'performance' => 0, 'security' => 0];
        foreach ($issues as $issue) {
            $cat = (string) $issue['category'];
            if (isset($counts[$cat])) {
                $counts[$cat]++;
            }
        }

        return $counts;
    }

    /**
     * @return array{0:bool,1:bool} [hasWordPress, hasElementor]
     */
    private function detectTech(int $auditId): array
    {
        $wp = false;
        $elementor = false;
        foreach ($this->auditData()->technologies($auditId) as $tech) {
            $name = strtolower((string) $tech['name']);
            if (str_contains($name, 'wordpress')) { $wp = true; }
            if (str_contains($name, 'elementor')) { $elementor = true; }
        }

        return [$wp, $elementor];
    }

    private function audits(): AuditRepository
    {
        /** @var AuditRepository $r */
        $r = $this->container->get(AuditRepository::class);

        return $r;
    }

    private function auditData(): AuditDataRepository
    {
        /** @var AuditDataRepository $r */
        $r = $this->container->get(AuditDataRepository::class);

        return $r;
    }

    private function rules(): OpportunityRuleRepository
    {
        /** @var OpportunityRuleRepository $r */
        $r = $this->container->get(OpportunityRuleRepository::class);

        return $r;
    }
}
