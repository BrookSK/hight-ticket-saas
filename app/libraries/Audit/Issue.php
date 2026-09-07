<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

/**
 * A detected issue/recommendation produced by an analyzer.
 *
 * Structured per the spec: rule id, category, severity, confidence, title,
 * description, impact, recommendation and evidence. Never fabricated — every
 * issue is backed by evidence collected during the scan.
 */
final class Issue
{
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_INFO = 'info';

    public const CONFIDENCE_HIGH = 'high';
    public const CONFIDENCE_MEDIUM = 'medium';
    public const CONFIDENCE_LOW = 'low';

    /**
     * @param array<string, mixed> $evidence
     */
    public function __construct(
        public readonly string $ruleId,
        public readonly string $category,
        public readonly string $severity,
        public readonly string $title,
        public readonly string $description,
        public readonly string $impact,
        public readonly string $recommendation,
        public readonly array $evidence = [],
        public readonly string $confidence = self::CONFIDENCE_HIGH,
        public readonly ?string $pageUrl = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(int $auditId): array
    {
        return [
            'audit_id'       => $auditId,
            'rule_id'        => $this->ruleId,
            'category'       => $this->category,
            'severity'       => $this->severity,
            'confidence'     => $this->confidence,
            'title'          => $this->title,
            'description'    => $this->description,
            'impact'         => $this->impact,
            'recommendation' => $this->recommendation,
            'evidence'       => $this->evidence !== []
                ? json_encode($this->evidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'page_url'       => $this->pageUrl,
        ];
    }
}
