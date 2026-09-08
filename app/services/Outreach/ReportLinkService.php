<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\ReportRepository;
use App\Services\AccessContext;
use App\Services\ConfigService;

/**
 * Manages the public shareable link of a commercial report.
 *
 * Responsibilities:
 * - Generate an unguessable token.
 * - Enforce expiry and revocation on public access.
 * - Track views (count + access log).
 *
 * Public access validation is centralised in resolvePublic(): a report is only
 * served when it exists, is not revoked and is not expired.
 */
final class ReportLinkService extends Service
{
    /**
     * Generate a unique, unguessable token (collision-checked).
     */
    public function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24)); // 48 hex chars
        } while ($this->reports()->tokenExists($token));

        return $token;
    }

    /**
     * Default expiry timestamp based on configuration (null = never).
     */
    public function defaultExpiry(): ?string
    {
        $days = (int) ($this->config()->get('outreach_report_default_expiry_days', '30') ?? 30);
        if ($days <= 0) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . $days . ' days') ?: time());
    }

    public function publicUrl(string $token): string
    {
        $base = rtrim((string) ($this->config()->get('app_url', '') ?? ''), '/');

        return ($base !== '' ? $base : '') . '/report/' . $token;
    }

    /**
     * Resolve a report for public viewing, or return a reason why it cannot be
     * shown. Increments views and logs access when served.
     *
     * @return array{ok:bool, report?:array<string,mixed>, reason?:string}
     */
    public function resolvePublic(string $token, ?string $ip, ?string $userAgent): array
    {
        $report = $this->reports()->findByToken($token);
        if ($report === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        if ($report['revoked_at'] !== null) {
            return ['ok' => false, 'reason' => 'revoked'];
        }
        if ($report['expires_at'] !== null && strtotime((string) $report['expires_at']) < time()) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        $id = (int) $report['id'];
        $this->reports()->incrementViews($id);
        $this->reports()->logAccess($id, $ip, $userAgent);

        return ['ok' => true, 'report' => $report];
    }

    /**
     * Revoke a report link (owner-scoped).
     */
    public function revoke(int $id): bool
    {
        $ctx = $this->context();
        $report = $this->reports()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($report === null) {
            return false;
        }
        $this->reports()->revoke($id);

        return true;
    }

    private function reports(): ReportRepository
    {
        /** @var ReportRepository $r */
        $r = $this->container->get(ReportRepository::class);

        return $r;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $c */
        $c = $this->container->get(ConfigService::class);

        return $c;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
