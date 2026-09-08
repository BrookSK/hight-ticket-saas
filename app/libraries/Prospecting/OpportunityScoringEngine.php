<?php

declare(strict_types=1);

namespace App\Libraries\Prospecting;

/**
 * Opportunity scoring engine (commercial potential — NOT site quality).
 *
 * Given a set of collected signals (site presence, site score, detected issues,
 * technologies, public contacts) and a set of configurable rules (weights),
 * produces a 0–100 opportunity score with explainable factors, a recommended
 * service and a priority. Every factor carries evidence; nothing is invented.
 *
 * Distinct from the Fase 2 Site Score: a bad/absent site can mean a HIGH
 * opportunity, while a great site usually means a LOW opportunity.
 */
final class OpportunityScoringEngine
{
    /**
     * @param array<int, array{rule_key:string,name:string,weight:int,confidence:string,recommended_service:?string,is_active:int}> $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    /**
     * Compute the opportunity score from collected signals.
     *
     * @param array<string, mixed> $signals {
     *   has_website:bool, site_score:?int, seo_issues:int, perf_issues:int,
     *   security_issues:int, has_wordpress:bool, has_elementor:bool,
     *   has_contact:bool, has_whatsapp:bool
     * }
     * @return array{score:int, confidence:string, priority:string, recommended_service:?string, factors:list<array<string,mixed>>}
     */
    public function compute(array $signals): array
    {
        $active = [];
        foreach ($this->rules as $rule) {
            if ((int) ($rule['is_active'] ?? 1) === 1) {
                $active[$rule['rule_key']] = $rule;
            }
        }

        $factors = [];
        $total = 0;
        $confidenceScores = [];
        $serviceVotes = [];

        $add = function (string $key, ?string $evidence) use (&$factors, &$total, &$confidenceScores, &$serviceVotes, $active): void {
            $rule = $active[$key] ?? null;
            if ($rule === null) {
                return;
            }
            $weight = (int) $rule['weight'];
            $total += $weight;
            $factors[] = [
                'rule_key'   => $key,
                'name'       => $rule['name'],
                'weight'     => $weight,
                'confidence' => $rule['confidence'],
                'evidence'   => $evidence,
            ];
            $confidenceScores[] = $this->confidenceValue((string) $rule['confidence']);
            if (!empty($rule['recommended_service'])) {
                $serviceVotes[(string) $rule['recommended_service']] = ($serviceVotes[(string) $rule['recommended_service']] ?? 0) + $weight;
            }
        };

        // --- Evaluate signals against rules (deterministic, evidence-backed) ---
        $hasWebsite = (bool) ($signals['has_website'] ?? false);
        if (!$hasWebsite) {
            $add('OPP-NOSITE-001', 'Nenhum website público identificado.');
        } else {
            $siteScore = $signals['site_score'] ?? null;
            if (is_int($siteScore)) {
                if ($siteScore < 40) {
                    $add('OPP-SITESCORE-001', 'Site Score = ' . $siteScore . '.');
                } elseif ($siteScore < 70) {
                    $add('OPP-SITESCORE-002', 'Site Score = ' . $siteScore . '.');
                }
            }
            if ((int) ($signals['seo_issues'] ?? 0) > 0) {
                $add('OPP-SEO-001', ($signals['seo_issues']) . ' problema(s) de SEO.');
            }
            if ((int) ($signals['perf_issues'] ?? 0) > 0) {
                $add('OPP-PERF-001', ($signals['perf_issues']) . ' problema(s) de performance.');
            }
            if ((int) ($signals['security_issues'] ?? 0) > 0) {
                $add('OPP-SEC-001', ($signals['security_issues']) . ' problema(s) de segurança.');
            }
            if ((bool) ($signals['has_wordpress'] ?? false)) {
                $add('OPP-WP-001', 'WordPress detectado.');
            }
            if ((bool) ($signals['has_elementor'] ?? false)) {
                $add('OPP-ELEMENTOR-001', 'Elementor detectado.');
            }
        }
        if ((bool) ($signals['has_contact'] ?? false)) {
            $add('OPP-CONTACT-001', 'Contato público encontrado.');
        }
        if ((bool) ($signals['has_whatsapp'] ?? false)) {
            $add('OPP-WHATSAPP-001', 'WhatsApp público encontrado.');
        }

        $score = (int) max(0, min(100, $total));
        $confidence = $this->aggregateConfidence($confidenceScores);
        $priority = match (true) {
            $score >= 70 => 'high',
            $score >= 40 => 'medium',
            default      => 'low',
        };

        arsort($serviceVotes);
        $recommendedService = $serviceVotes !== [] ? (string) array_key_first($serviceVotes) : null;

        return [
            'score'               => $score,
            'confidence'          => $confidence,
            'priority'            => $priority,
            'recommended_service' => $recommendedService,
            'factors'             => $factors,
        ];
    }

    private function confidenceValue(string $c): int
    {
        return match ($c) {
            'high'   => 3,
            'medium' => 2,
            default  => 1,
        };
    }

    /**
     * @param list<int> $scores
     */
    private function aggregateConfidence(array $scores): string
    {
        if ($scores === []) {
            return 'low';
        }
        $avg = array_sum($scores) / count($scores);

        return match (true) {
            $avg >= 2.5 => 'high',
            $avg >= 1.7 => 'medium',
            default     => 'low',
        };
    }
}
