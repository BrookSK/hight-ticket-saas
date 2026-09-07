<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

/**
 * Scoring engine (deterministic).
 *
 * Converts detected issues into 0–100 scores per category and an overall
 * weighted score. Penalties are weighted by severity and confidence, so
 * low-confidence findings never sink a score as hard as confirmed ones. Also
 * produces the score band label for the executive summary.
 */
final class ScoringEngine
{
    /** Penalty points per severity (before confidence weighting). */
    private const SEVERITY_PENALTY = [
        Issue::SEVERITY_CRITICAL => 40,
        Issue::SEVERITY_HIGH     => 20,
        Issue::SEVERITY_MEDIUM   => 10,
        Issue::SEVERITY_LOW      => 4,
        Issue::SEVERITY_INFO     => 0,
    ];

    /** Confidence multiplier applied to penalties. */
    private const CONFIDENCE_WEIGHT = [
        Issue::CONFIDENCE_HIGH   => 1.0,
        Issue::CONFIDENCE_MEDIUM => 0.6,
        Issue::CONFIDENCE_LOW    => 0.3,
    ];

    /** Category weights for the overall score. */
    private const CATEGORY_WEIGHT = [
        'performance'    => 0.25,
        'seo'            => 0.25,
        'security'       => 0.20,
        'accessibility'  => 0.15,
        'content'        => 0.10,
        'best_practices' => 0.05,
    ];

    /** Categories that receive a computed score. */
    private const SCORED_CATEGORIES = [
        'performance', 'seo', 'security', 'accessibility', 'content', 'best_practices',
    ];

    /**
     * Compute scores from a list of issues.
     *
     * @param list<Issue> $issues
     * @return array{overall:int, performance:int, seo:int, security:int, accessibility:int, technology:int|null, content:int, best_practices:int}
     */
    public function compute(array $issues): array
    {
        $penalties = array_fill_keys(self::SCORED_CATEGORIES, 0.0);

        foreach ($issues as $issue) {
            if (!isset($penalties[$issue->category])) {
                continue; // e.g. "technology" is informational, not scored via penalties
            }
            $base = self::SEVERITY_PENALTY[$issue->severity] ?? 0;
            $weight = self::CONFIDENCE_WEIGHT[$issue->confidence] ?? 1.0;
            $penalties[$issue->category] += $base * $weight;
        }

        $scores = [];
        foreach (self::SCORED_CATEGORIES as $cat) {
            $scores[$cat] = (int) max(0, min(100, round(100 - $penalties[$cat])));
        }

        // Overall: weighted average of scored categories.
        $overall = 0.0;
        foreach (self::CATEGORY_WEIGHT as $cat => $weight) {
            $overall += ($scores[$cat] ?? 100) * $weight;
        }

        return [
            'overall'        => (int) round($overall),
            'performance'    => $scores['performance'],
            'seo'            => $scores['seo'],
            'security'       => $scores['security'],
            'accessibility'  => $scores['accessibility'],
            'technology'     => null, // informational; no penalty-based score
            'content'        => $scores['content'],
            'best_practices' => $scores['best_practices'],
        ];
    }

    /**
     * Map a score to a band key (translatable label handled in the view).
     */
    public function band(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 60 => 'needs_improvement',
            $score >= 40 => 'problematic',
            default      => 'critical',
        };
    }
}
