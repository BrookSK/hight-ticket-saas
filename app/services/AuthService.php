<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Cache;
use App\Libraries\Session;
use App\Repositories\UserRepository;

/**
 * Authentication and authorization (ACL) business logic.
 *
 * Handles login/logout, the current authenticated user, granular permission
 * checks and impersonation. Super Admin bypasses permission checks (absolute
 * access). Permission sets are cached and cleared centrally.
 *
 * Prepared for future 2FA, social login and magic links without structural
 * changes (login flow is centralised here).
 */
final class AuthService extends Service
{
    private const SESSION_USER_ID = '_auth_user_id';
    private const SESSION_IMPERSONATOR = '_auth_impersonator_id';

    /** @var array<string, mixed>|null In-request memoisation of current user. */
    private ?array $currentUser = null;

    /**
     * Attempt to authenticate with email and password.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users()->findByEmail($email);

        if ($user === null || (string) ($user['status'] ?? '') !== 'active') {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $this->session()->regenerate();
        $this->session()->set(self::SESSION_USER_ID, (int) $user['id']);
        $this->currentUser = null;

        return true;
    }

    public function logout(): void
    {
        $this->session()->forget(self::SESSION_USER_ID);
        $this->session()->forget(self::SESSION_IMPERSONATOR);
        $this->currentUser = null;
        $this->session()->regenerate();
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function id(): ?int
    {
        $id = $this->session()->get(self::SESSION_USER_ID);

        return is_int($id) ? $id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $id = $this->id();
        if ($id === null) {
            return null;
        }

        return $this->currentUser = $this->users()->findById($id);
    }

    public function isSuperAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && (int) ($user['is_super_admin'] ?? 0) === 1;
    }

    /**
     * Check whether the current user has a granular permission.
     */
    public function can(string $permission): bool
    {
        if (!$this->check()) {
            return false;
        }

        // Super Admin has absolute access.
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Permission keys for the current user (cached).
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $id = $this->id();
        if ($id === null) {
            return [];
        }

        $cacheKey = 'acl.user.' . $id . '.permissions';

        /** @var list<string>|null $cached */
        $cached = $this->cache()->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $permissions = $this->users()->permissionKeysForUser($id);
        $this->cache()->set($cacheKey, $permissions, 1800);

        return $permissions;
    }

    /**
     * Start impersonating another user (Super Admin only). Returns success.
     */
    public function impersonate(int $targetUserId): bool
    {
        if (!$this->isSuperAdmin()) {
            return false;
        }

        $target = $this->users()->findById($targetUserId);
        if ($target === null) {
            return false;
        }

        $this->session()->set(self::SESSION_IMPERSONATOR, $this->id());
        $this->session()->set(self::SESSION_USER_ID, $targetUserId);
        $this->currentUser = null;

        return true;
    }

    public function isImpersonating(): bool
    {
        return $this->session()->has(self::SESSION_IMPERSONATOR);
    }

    /**
     * Return to the original account immediately. Returns success.
     */
    public function stopImpersonating(): bool
    {
        $original = $this->session()->get(self::SESSION_IMPERSONATOR);
        if (!is_int($original)) {
            return false;
        }

        $this->session()->set(self::SESSION_USER_ID, $original);
        $this->session()->forget(self::SESSION_IMPERSONATOR);
        $this->currentUser = null;

        return true;
    }

    private function users(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = $this->container->get(UserRepository::class);

        return $repository;
    }

    private function session(): Session
    {
        /** @var Session $session */
        $session = $this->container->get('session');

        return $session;
    }

    private function cache(): Cache
    {
        /** @var Cache $cache */
        $cache = $this->container->get('cache');

        return $cache;
    }
}
