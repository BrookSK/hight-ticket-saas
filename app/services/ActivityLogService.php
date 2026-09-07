<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Request;
use App\Repositories\ActivityLogRepository;

/**
 * Records important actions into the audit log (activity_logs).
 *
 * Captures user, action, target object, result and request context (IP,
 * browser, OS, page). Called by controllers/services after meaningful events
 * such as login, logout, CRUD, config changes and lead capture.
 */
final class ActivityLogService extends Service
{
    /**
     * @param array{object_type?:string|null,object_id?:string|int|null,result?:string|null} $extra
     */
    public function record(string $action, ?int $userId = null, array $extra = []): void
    {
        /** @var Request $request */
        $request = $this->container->get('request');

        $userAgent = $request->userAgent();

        $this->repository()->create([
            'user_id'     => $userId,
            'action'      => $action,
            'object_type' => $extra['object_type'] ?? null,
            'object_id'   => isset($extra['object_id']) ? (string) $extra['object_id'] : null,
            'result'      => $extra['result'] ?? 'success',
            'page'        => $request->path(),
            'ip'          => $request->ip(),
            'user_agent'  => mb_substr($userAgent, 0, 255),
            'os'          => $this->detectOs($userAgent),
            'browser'     => $this->detectBrowser($userAgent),
        ]);
    }

    private function detectOs(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };
    }

    private function detectBrowser(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Edg')     => 'Edge',
            str_contains($ua, 'OPR'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari')  => 'Safari',
            default => null,
        };
    }

    private function repository(): ActivityLogRepository
    {
        /** @var ActivityLogRepository $repository */
        $repository = $this->container->get(ActivityLogRepository::class);

        return $repository;
    }
}
