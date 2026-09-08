<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\AuditDataRepository;
use App\Repositories\AuditRepository;
use App\Repositories\LeadRepository;
use App\Repositories\ReportRepository;
use App\Services\AccessContext;
use App\Services\LeadService;

/**
 * Builds and persists shareable commercial reports.
 *
 * A report turns an internal audit/opportunity into a client-facing diagnosis:
 * a clear summary of problems and recommendations, without exposing internal
 * scoring when hide_internal_score is on. Owner-scoped; the snapshot is stored
 * so the public page never re-queries internal tables.
 */
final class CommercialReportService extends Service
{
    public const TYPES = ['diagnosis', 'opportunity', 'performance', 'website', 'custom'];

    /**
     * Create a report from a lead (and its latest linked audit, if any).
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, token?:string, errors?:array<string,string>}
     */
    public function createFromLead(int $leadId, array $input): array
    {
        $ctx = $this->context();
        $lead = $this->leads()->find($leadId);
        if ($lead === null) {
            return ['ok' => false, 'errors' => ['lead_id' => 'errors.not_found']];
        }

        $type = in_array($input['type'] ?? '', self::TYPES, true) ? (string) $input['type'] : 'diagnosis';
        $hideScore = !empty($input['hide_internal_score']);
        $auditId = isset($input['audit_id']) ? (int) $input['audit_id'] : $this->latestAuditIdForLead($leadId);

        $snapshot = $this->buildSnapshot($lead, $auditId, $hideScore);
        $token = $this->links()->generateToken();

        $id = $this->reports()->create([
            'owner_type'          => $ctx->ownerType(),
            'owner_id'            => $ctx->ownerId(),
            'created_by'          => $ctx->userId(),
            'lead_id'             => $leadId,
            'company_id'          => $lead['company_id'] ?? null,
            'audit_id'            => $auditId ?: null,
            'type'                => $type,
            'title'               => $this->nt($input['title'] ?? null) ?? (string) ($lead['company_name'] ?? 'Diagnóstico'),
            'token'               => $token,
            'cta_label'           => $this->nt($input['cta_label'] ?? null),
            'cta_url'             => $this->nt($input['cta_url'] ?? null),
            'hide_internal_score' => $hideScore ? 1 : 0,
            'snapshot'            => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'expires_at'          => $this->resolveExpiry($input),
        ]);

        return ['ok' => true, 'id' => $id, 'token' => $token];
    }

    /**
     * Decode the stored snapshot for rendering.
     *
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    public function decodeSnapshot(array $report): array
    {
        $raw = $report['snapshot'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(): array
    {
        $ctx = $this->context();

        return $this->reports()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->reports()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * Build the client-facing snapshot from audit data.
     *
     * @param array<string, mixed> $lead
     * @return array<string, mixed>
     */
    private function buildSnapshot(array $lead, int $auditId, bool $hideScore): array
    {
        $snapshot = [
            'company'   => (string) ($lead['company_name'] ?? ''),
            'domain'    => (string) ($lead['company_domain'] ?? ''),
            'generated' => date('Y-m-d'),
            'summary'   => [],
            'highlights' => [],
            'recommendations' => [],
        ];

        if ($auditId <= 0) {
            return $snapshot;
        }

        $ctx = $this->context();
        $audit = $this->audits()->findForContext($auditId, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($audit === null) {
            return $snapshot;
        }

        $severity = $this->auditData()->issueCountsBySeverity($auditId);
        $snapshot['summary'] = [
            'critical' => $severity['critical'] ?? 0,
            'high'     => $severity['high'] ?? 0,
            'medium'   => $severity['medium'] ?? 0,
            'low'      => $severity['low'] ?? 0,
        ];

        // Only expose the overall score when not hidden.
        if (!$hideScore && isset($audit['score_overall'])) {
            $snapshot['score'] = (int) $audit['score_overall'];
        }

        // Top client-relevant issues (title + recommendation), highest severity first.
        $issues = $this->auditData()->issues($auditId);
        $count = 0;
        foreach ($issues as $issue) {
            if ($count >= 8) {
                break;
            }
            $snapshot['recommendations'][] = [
                'title'          => (string) ($issue['title'] ?? ''),
                'severity'       => (string) ($issue['severity'] ?? 'info'),
                'recommendation' => (string) ($issue['recommendation'] ?? ''),
            ];
            $count++;
        }

        // Detected technologies as a light "we understand your stack" highlight.
        foreach ($this->auditData()->technologies($auditId) as $tech) {
            $snapshot['highlights'][] = (string) ($tech['name'] ?? '');
        }
        $snapshot['highlights'] = array_values(array_unique(array_filter($snapshot['highlights'])));

        return $snapshot;
    }

    private function latestAuditIdForLead(int $leadId): int
    {
        $audits = $this->leadRepository()->auditsForLead($leadId);

        return $audits !== [] ? (int) $audits[0]['id'] : 0;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveExpiry(array $input): ?string
    {
        if (array_key_exists('expires_at', $input) && $input['expires_at'] !== null && $input['expires_at'] !== '') {
            return (string) $input['expires_at'];
        }
        if (!empty($input['never_expires'])) {
            return null;
        }

        return $this->links()->defaultExpiry();
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function reports(): ReportRepository
    {
        /** @var ReportRepository $r */
        $r = $this->container->get(ReportRepository::class);

        return $r;
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

    private function leads(): LeadService
    {
        /** @var LeadService $s */
        $s = $this->container->get(LeadService::class);

        return $s;
    }

    private function leadRepository(): LeadRepository
    {
        /** @var LeadRepository $r */
        $r = $this->container->get(LeadRepository::class);

        return $r;
    }

    private function links(): ReportLinkService
    {
        /** @var ReportLinkService $s */
        $s = $this->container->get(ReportLinkService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
