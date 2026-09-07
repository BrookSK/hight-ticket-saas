<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;

/**
 * Access context (owner/tenant abstraction).
 *
 * Represents "who owns/can see" a resource. Today the owner is the current
 * user; in the future it may become a tenant/company without rewriting modules
 * that depend on this abstraction.
 *
 * Every ownership-scoped query must pass through this context. Super Admin has
 * a global view (canSeeAll).
 */
final class AccessContext extends Service
{
    public const OWNER_TYPE_USER = 'user';

    /**
     * Owner type for new resources created in the current context.
     */
    public function ownerType(): string
    {
        // Phase 2: user-scoped. Change here (and add tenant resolution) later.
        return self::OWNER_TYPE_USER;
    }

    /**
     * Owner id for the current context (the authenticated user's id for now).
     */
    public function ownerId(): ?int
    {
        return $this->auth()->id();
    }

    /**
     * The acting user's id (who performs the action).
     */
    public function userId(): ?int
    {
        return $this->auth()->id();
    }

    /**
     * Whether the current context can see all resources (Super Admin).
     */
    public function canSeeAll(): bool
    {
        return $this->auth()->isSuperAdmin();
    }

    /**
     * Whether the current context may access a resource owned by the given
     * owner. Super Admin may access anything; others only their own.
     */
    public function owns(string $ownerType, int $ownerId): bool
    {
        if ($this->canSeeAll()) {
            return true;
        }

        return $ownerType === $this->ownerType() && $ownerId === $this->ownerId();
    }

    private function auth(): AuthService
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth;
    }
}
