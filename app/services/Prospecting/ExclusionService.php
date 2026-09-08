<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Repositories\ExclusionListRepository;
use App\Services\AccessContext;

/**
 * Do-not-prospect exclusion list (owner-scoped).
 *
 * A discovery result matching an excluded domain/email/phone is skipped and
 * never converted automatically.
 */
final class ExclusionService extends Service
{
    public function add(string $type, string $value, ?string $reason): bool
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        $value = trim($value);
        if ($ownerId === null || $value === '' || !in_array($type, ['domain', 'cnpj', 'email', 'phone', 'company'], true)) {
            return false;
        }
        $this->repository()->add($ctx->ownerType(), $ownerId, $type, mb_strtolower($value), $reason, $ctx->userId());

        return true;
    }

    /**
     * Whether a discovery result matches any exclusion entry.
     *
     * @param array<string, mixed> $result
     */
    public function isExcluded(array $result): bool
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return false;
        }
        $ot = $ctx->ownerType();

        $checks = [
            'domain' => $result['domain'] ?? null,
            'email'  => $result['email'] ?? null,
            'phone'  => $result['phone'] ?? null,
        ];
        foreach ($checks as $type => $value) {
            if ($value !== null && $value !== '' && $this->repository()->isExcluded($ot, $ownerId, $type, mb_strtolower((string) $value))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return [];
        }

        return $this->repository()->listForOwner($ctx->ownerType(), $ownerId);
    }

    public function remove(int $id): void
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId !== null) {
            $this->repository()->delete($id, $ctx->ownerType(), $ownerId);
        }
    }

    private function repository(): ExclusionListRepository
    {
        /** @var ExclusionListRepository $r */
        $r = $this->container->get(ExclusionListRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
