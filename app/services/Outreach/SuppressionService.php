<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\SuppressionRepository;
use App\Services\AccessContext;

/**
 * Opt-out / suppression management.
 *
 * A suppressed phone/email must never be contacted again. Opt-out is honoured
 * across all channels for the owner. Values are normalised so lookups match
 * regardless of formatting.
 */
final class SuppressionService extends Service
{
    /**
     * Register an opt-out for a recipient address on a channel.
     */
    public function optOut(string $channel, string $value, string $reason = 'opt_out'): bool
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return false;
        }
        $type = $channel === 'email' ? 'email' : 'phone';

        return $this->repository()->add($ctx->ownerType(), $ownerId, $type, $this->normalize($type, $value), $reason);
    }

    /**
     * Whether a recipient is suppressed for a channel (owner-scoped).
     */
    public function isSuppressed(string $channel, string $value): bool
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return false;
        }
        $type = $channel === 'email' ? 'email' : 'phone';

        return $this->repository()->isSuppressed($ctx->ownerType(), $ownerId, $type, $this->normalize($type, $value));
    }

    public function remove(int $id): bool
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return false;
        }
        $this->repository()->remove($id, $ctx->ownerType(), $ownerId);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(): array
    {
        $ctx = $this->context();

        return $this->repository()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * Normalise a value for consistent matching (digits for phones, lower for e-mail).
     */
    public function normalize(string $type, string $value): string
    {
        if ($type === 'phone') {
            return preg_replace('/\D+/', '', $value) ?? $value;
        }

        return strtolower(trim($value));
    }

    private function repository(): SuppressionRepository
    {
        /** @var SuppressionRepository $r */
        $r = $this->container->get(SuppressionRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
